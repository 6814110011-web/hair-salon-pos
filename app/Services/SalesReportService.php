<?php

namespace App\Services;

use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class SalesReportService
{
    public function summary(string $from, string $to, ?int $sellerId = null): array
    {
        Validator::make(compact('from', 'to'), ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from'])->validate();
        $start = CarbonImmutable::parse($from, config('app.timezone'))->startOfDay()->toDateTimeString();
        $end = CarbonImmutable::parse($to, config('app.timezone'))->addDay()->startOfDay()->toDateTimeString();
        $sales = Sale::with(['items', 'payments'])->where('status', 'paid')->where('created_at', '>=', $start)->where('created_at', '<', $end)->when($sellerId !== null, fn ($q) => $q->where('user_id', $sellerId))->get();
        $revenue = 0;
        $discount = 0;
        $items = ['product' => 0, 'service' => 0];
        $payments = ['cash' => 0, 'promptpay' => 0, 'transfer' => 0, 'card' => 0];
        foreach ($sales as $sale) {
            $revenue += Money::cents($sale->total);
            $discount += Money::cents($sale->discount);
            foreach ($sale->items as $item) {
                $type = match ($item->itemable_type) {
                    Product::class, 'product' => 'product', Service::class, 'service' => 'service', default => null
                };
                if ($type) {
                    $items[$type] += Money::cents($item->subtotal);
                }
            }
            foreach ($sale->payments as $payment) {
                $payments[$payment->method] += Money::cents($payment->amount);
            }
        }
        $cancelledIds = Sale::where('status', 'cancelled')->where('cancelled_at', '>=', $start)->where('cancelled_at', '<', $end)->when($sellerId !== null, fn ($q) => $q->where('user_id', $sellerId))->pluck('id');
        $refunds = PaymentRefund::where('status', 'pending')->whereHas('payment', fn ($q) => $q->whereIn('sale_id', $cancelledIds))->get();
        $pending = $refunds->sum(fn ($refund) => Money::cents($refund->amount));
        $count = $sales->count();
        $average = $count ? intdiv($revenue + intdiv($count, 2), $count) : 0;

        return ['revenue' => Money::decimal($revenue), 'bill_count' => $count, 'discount' => Money::decimal($discount), 'average_bill' => Money::decimal($average),
            'item_totals' => array_map(fn ($v) => Money::decimal($v), $items), 'payment_totals' => array_map(fn ($v) => Money::decimal($v), $payments),
            'cancelled_count' => $cancelledIds->count(), 'pending_refunds' => Money::decimal($pending)];
    }
}
