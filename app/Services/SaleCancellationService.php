<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleCancellationService
{
    public function cancel(int $saleId, User $actor, string $reason): void
    {
        $actor = $actor->fresh();
        abort_unless($actor && $actor->is_active && $actor->role === 'admin', 403);

        if (trim($reason) === '' || mb_strlen($reason) > 200) {
            throw ValidationException::withMessages(['reason' => 'กรุณาระบุเหตุผลยกเลิกบิล']);
        }

        DB::transaction(function () use ($saleId, $actor, $reason) {
            $sale = DB::table('sales')->where('id', $saleId)->lockForUpdate()->first();
            abort_unless($sale, 404);

            if ($sale->status === 'cancelled') {
                return;
            }

            $customer = $sale->customer_id
                ? DB::table('customers')->where('id', $sale->customer_id)->lockForUpdate()->first()
                : null;

            $movements = DB::table('stock_movements')->where('sale_id', $saleId)
                ->whereNull('reversal_of_id')->orderBy('product_id')->orderBy('id')->get();

            if ($sale->status === 'paid' || $movements->isNotEmpty()) {
                $expected = DB::table('sale_items')->where('sale_id', $saleId)
                    ->whereIn('itemable_type', [Product::class, 'product'])
                    ->selectRaw('itemable_id, SUM(qty) as qty')->groupBy('itemable_id')
                    ->get()->mapWithKeys(fn ($item) => [(int) $item->itemable_id => (int) $item->qty])->sortKeys()->all();
                $recorded = $movements->groupBy('product_id')
                    ->map(fn ($rows) => (int) $rows->sum('qty'))->sortKeys()->all();

                if ($expected !== $recorded || $movements->contains(fn ($row) => $row->type !== 'out' || $row->qty <= 0)) {
                    throw ValidationException::withMessages(['sale' => 'ประวัติตัดสต็อกไม่ตรงกับบิล ต้องตรวจสอบก่อนยกเลิก']);
                }
            }

            foreach ($movements as $movement) {
                $product = DB::table('products')->where('id', $movement->product_id)->lockForUpdate()->first();
                $stockAfter = $product->stock_qty + $movement->qty;
                if ($stockAfter > 2147483647) {
                    throw ValidationException::withMessages(['sale' => 'คืนสต็อกไม่ได้ เพราะยอดคงเหลือจะเกินขอบเขต กรุณาตรวจสอบสต็อกก่อนยกเลิก']);
                }
                DB::table('products')->where('id', $product->id)->update(['stock_qty' => $stockAfter, 'updated_at' => now()]);
                DB::table('stock_movements')->insert([
                    'product_id' => $product->id, 'user_id' => $actor->id, 'sale_id' => $saleId,
                    'type' => 'in', 'qty' => $movement->qty, 'stock_after' => $stockAfter,
                    'reversal_of_id' => $movement->id, 'note' => 'คืนสต็อกจากการยกเลิกบิล: '.$reason,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $points = DB::table('point_transactions')->where('sale_id', $saleId)
                ->whereNull('reversal_of_id')->get();

            if ($points->isNotEmpty()) {
                if (! $customer || $points->contains(fn ($row) => $row->customer_id !== $customer->id)) {
                    throw ValidationException::withMessages(['sale' => 'ข้อมูลแต้มไม่ตรงกับลูกค้าในบิล']);
                }

                $adjustments = $points->map(function ($row) {
                    $effect = match ($row->type) {
                        'earn' => abs($row->points),
                        'redeem', 'expire' => -abs($row->points),
                        'adjust' => $row->points,
                    };

                    return ['original' => $row, 'points' => -$effect];
                })->sortByDesc('points');
                $balance = (int) (DB::table('point_transactions')->where('customer_id', $customer->id)
                    ->orderByDesc('id')->value('balance_after') ?? 0);

                if ($balance + $adjustments->sum('points') < 0) {
                    throw ValidationException::withMessages(['sale' => 'แต้มจากบิลนี้ถูกใช้แล้ว ต้องแก้ไขยอดแต้มก่อนยกเลิก']);
                }

                foreach ($adjustments as $adjustment) {
                    $balance += $adjustment['points'];
                    DB::table('point_transactions')->insert([
                        'customer_id' => $customer->id, 'sale_id' => $saleId, 'type' => 'adjust',
                        'points' => $adjustment['points'], 'balance_after' => $balance,
                        'reversal_of_id' => $adjustment['original']->id, 'note' => 'ยกเลิกบิล: '.$reason,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            DB::table('promotion_redemptions')->where('sale_id', $saleId)->whereNull('cancelled_at')
                ->update(['active_milestone' => null, 'cancelled_at' => now(), 'updated_at' => now()]);

            if ($sale->status === 'paid') {
                foreach (DB::table('payments')->where('sale_id', $saleId)->get() as $payment) {
                    DB::table('payment_refunds')->insert([
                        'payment_id' => $payment->id, 'requested_by' => $actor->id,
                        'amount' => $payment->amount, 'status' => 'pending',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            if ($customer && $sale->visit_counted) {
                $lastVisit = DB::table('sales')->where('customer_id', $customer->id)
                    ->where('status', 'paid')->where('id', '!=', $saleId)->max('created_at');
                DB::table('customers')->where('id', $customer->id)->update([
                    'total_visits' => max(0, $customer->total_visits - 1),
                    'last_visit_at' => $lastVisit, 'updated_at' => now(),
                ]);
            }

            DB::table('sales')->where('id', $saleId)->update([
                'status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $actor->id,
                'cancellation_reason' => trim($reason), 'visit_counted' => false, 'updated_at' => now(),
            ]);
        });
    }
}
