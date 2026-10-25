<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionRedemptionService
{
    public function redeem(int $saleId): int
    {
        return DB::transaction(function () use ($saleId) {
            $sale = DB::table('sales')->where('id', $saleId)->lockForUpdate()->first();

            if (! $sale || $sale->status !== 'paid' || ! $sale->customer_id || ! $sale->promotion_id) {
                throw ValidationException::withMessages(['promotion' => 'ต้องเป็นบิลที่ชำระแล้ว มีลูกค้าและโปรโมชั่น']);
            }

            $customer = DB::table('customers')->where('id', $sale->customer_id)->lockForUpdate()->first();
            $promotion = DB::table('promotions')->where('id', $sale->promotion_id)->lockForUpdate()->first();

            if (! $promotion || ! $promotion->is_active || $promotion->visit_required < 1) {
                throw ValidationException::withMessages(['promotion' => 'โปรโมชั่นไม่พร้อมใช้งาน']);
            }

            $milestone = intdiv($customer->total_visits, $promotion->visit_required) * $promotion->visit_required;

            if ($milestone < $promotion->visit_required) {
                throw ValidationException::withMessages(['promotion' => 'จำนวนครั้งใช้บริการยังไม่ครบตามเงื่อนไข']);
            }

            $alreadyUsed = DB::table('promotion_redemptions')
                ->where('customer_id', $customer->id)
                ->where('promotion_id', $promotion->id)
                ->where('active_milestone', $milestone)
                ->exists();

            if ($alreadyUsed || DB::table('promotion_redemptions')->where('sale_id', $saleId)->exists()) {
                throw ValidationException::withMessages(['promotion' => 'ใช้สิทธิ์รอบนี้หรือบิลนี้ไปแล้ว']);
            }

            return DB::table('promotion_redemptions')->insertGetId([
                'customer_id' => $customer->id,
                'promotion_id' => $promotion->id,
                'sale_id' => $saleId,
                'visit_milestone' => $milestone,
                'active_milestone' => $milestone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
