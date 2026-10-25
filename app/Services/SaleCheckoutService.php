<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\CheckoutRules;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleCheckoutService
{
    public function checkout(User $actor, array $data): Sale
    {
        $operator = $actor->fresh();
        abort_unless($operator?->is_active && $operator->hasRole('admin', 'cashier'), 403);
        $data = Validator::make($data, CheckoutRules::rules(), CheckoutRules::messages())->validate();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(fn () => $this->record($actor, $data), 3);
            } catch (UniqueConstraintViolationException $exception) {
                if (str_contains($exception->getMessage(), 'checkout_token')) {
                    $sale = Sale::where('checkout_token', $data['checkout_token'])->firstOrFail();
                    abort_unless($sale->user_id === $actor->id, 403);

                    return $sale;
                }
                if (! str_contains($exception->getMessage(), 'bill_no') || $attempt === 2) {
                    throw $exception;
                }
            }
        }
        throw new \LogicException('Unable to allocate bill number');
    }

    private function record(User $actor, array $data): Sale
    {
        $existing = Sale::where('checkout_token', $data['checkout_token'])->lockForUpdate()->first();
        if ($existing) {
            abort_unless($existing->user_id === $actor->id, 403);

            return $existing;
        }

        $staffIds = collect($data['items'])->pluck('stylist_id')->filter()->push($actor->id)->unique()->sort()->values();
        $staff = User::whereIn('id', $staffIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $operator = $staff->get($actor->id);
        abort_unless($operator?->is_active && $operator->hasRole('admin', 'cashier'), 403);
        $customer = empty($data['customer_id']) ? null : Customer::whereKey($data['customer_id'])->lockForUpdate()->first();
        if (! empty($data['customer_id']) && ! $customer) {
            $this->reject('customer_id', 'ไม่พบลูกค้า กรุณาเลือกใหม่');
        }
        $sale = Sale::forceCreate(['bill_no' => strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 20)), 'checkout_token' => $data['checkout_token'], 'user_id' => $actor->id, 'customer_id' => $customer?->id, 'status' => 'pending']);
        $productIds = collect($data['items'])->where('type', 'product')->pluck('id')->unique()->sort()->values();
        $serviceIds = collect($data['items'])->where('type', 'service')->pluck('id')->unique()->sort()->values();
        $products = Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $services = Service::whereIn('id', $serviceIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $rows = [];
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $isProduct = $item['type'] === 'product';
            $catalog = ($isProduct ? $products : $services)->get($item['id']);
            if (! $catalog || ! $catalog->is_active) {
                $this->reject('items', 'สินค้า/บริการถูกปิดใช้งานหรือไม่มีอยู่ กรุณาตรวจสอบรายการ');
            }
            $price = $this->cents($isProduct ? $catalog->sell_price : $catalog->price);
            if ($price !== $this->cents((string) $item['expected_price'])) {
                $this->reject('items', 'ราคาของ '.$catalog->name.' เปลี่ยนแล้ว กรุณาตรวจสอบและยืนยันราคาใหม่');
            }
            $stylistId = $isProduct ? null : ($item['stylist_id'] ?? null);
            if (! $isProduct && (! $stylistId || ! $staff->get($stylistId)?->is_active || ! $staff->get($stylistId)->hasRole('stylist'))) {
                $this->reject('items', 'กรุณาเลือกช่างที่เปิดใช้งานให้ทุกรายการบริการ');
            }
            $key = $item['type'].':'.$catalog->id.':'.$stylistId;
            if (! isset($rows[$key])) {
                $rows[$key] = ['catalog' => $catalog, 'is_product' => $isProduct, 'price' => $price, 'qty' => 0, 'stylist_id' => $stylistId];
            }
            $rows[$key]['qty'] += (int) $item['qty'];
            if ($rows[$key]['qty'] > 999) {
                $this->reject('items', 'จำนวนรวมต่อรายการต้องไม่เกิน 999');
            }
            $subtotal += $price * (int) $item['qty'];
            if ($subtotal > Money::MAX_CENTS) {
                $this->reject('items', 'ยอดรวมเกินขอบเขตที่ระบบรองรับ');
            }
        }
        $discount = 0;
        $promotion = null;
        if (! empty($data['promotion_id'])) {
            if (! $customer) {
                $this->reject('promotion', 'โปรโมชั่นต้องระบุลูกค้าสมาชิก');
            }
            $promotion = Promotion::whereKey($data['promotion_id'])->lockForUpdate()->first();
            if (! $promotion || ! $promotion->is_active || $promotion->visit_required < 1) {
                $this->reject('promotion', 'โปรโมชั่นไม่พร้อมใช้งาน');
            }
            $reward = $this->cents($promotion->reward_value);
            if ($promotion->reward_type === 'percent') {
                if ($reward > 10000) {
                    $this->reject('promotion', 'ส่วนลดเปอร์เซ็นต์ต้องไม่เกิน 100');
                }
                $discount = intdiv($subtotal * $reward + 5000, 10000);
            } elseif ($promotion->reward_type === 'amount') {
                $discount = $reward;
            } else {
                $this->reject('promotion', 'ประเภทส่วนลดไม่ถูกต้อง');
            }
            $discount = min($subtotal, $discount);
        }
        $total = $subtotal - $discount;
        $received = $data['method'] === 'cash' ? $this->cents((string) $data['received']) : $total;
        if ($received < $total) {
            $this->reject('received', 'เงินที่รับน้อยกว่ายอดสุทธิ');
        }
        foreach ($rows as $row) {
            $catalog = $row['catalog'];
            $qty = $row['qty'];
            if ($row['is_product']) {
                if ($catalog->stock_qty < $qty) {
                    $this->reject('items', 'สต็อก '.$catalog->name.' ไม่พอ');
                }
                $catalog->forceFill(['stock_qty' => $catalog->stock_qty - $qty])->save();
                StockMovement::forceCreate(['product_id' => $catalog->id, 'user_id' => $actor->id, 'sale_id' => $sale->id, 'type' => 'out', 'qty' => $qty, 'stock_after' => $catalog->stock_qty, 'note' => 'ขายบิล '.$sale->bill_no]);
            }
            SaleItem::forceCreate(['sale_id' => $sale->id, 'itemable_type' => $row['is_product'] ? Product::class : Service::class, 'itemable_id' => $catalog->id, 'stylist_id' => $row['stylist_id'], 'item_name' => $catalog->name, 'qty' => $qty, 'price' => Money::decimal($row['price']), 'subtotal' => Money::decimal($row['price'] * $qty)]);
        }
        if ($customer) {
            if ($customer->total_visits >= 2147483647) {
                $this->reject('customer_id', 'จำนวนครั้งใช้บริการเกินขอบเขต');
            }
            $customer->forceFill(['total_visits' => $customer->total_visits + 1, 'last_visit_at' => now()])->save();
        }
        $sale->forceFill(['subtotal' => Money::decimal($subtotal), 'discount' => Money::decimal($discount), 'total' => Money::decimal($total), 'promotion_id' => $promotion?->id, 'status' => 'paid', 'visit_counted' => $customer !== null])->save();
        if ($promotion) {
            app(PromotionRedemptionService::class)->redeem($sale->id);
        }
        Payment::forceCreate(['sale_id' => $sale->id, 'method' => $data['method'], 'amount' => Money::decimal($total), 'received' => Money::decimal($received), 'change' => Money::decimal($received - $total), 'ref_no' => $data['ref_no'] ?? null]);

        return $sale->refresh();
    }

    private function cents(string $value): int
    {
        try {
            return Money::cents($value);
        } catch (\InvalidArgumentException) {
            $this->reject('amount', 'จำนวนเงินไม่ถูกต้องหรือเกินขอบเขต');
        }
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
