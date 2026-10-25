<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentRefundService
{
    public function complete(int $refundId, User $actor, string $reference): void
    {
        $operator = $actor->fresh();
        abort_unless($operator?->is_active && $operator->hasRole('admin'), 403);
        if (trim($reference) === '' || mb_strlen($reference) > 255) {
            throw ValidationException::withMessages(['ref_no' => 'กรุณาระบุหลักฐานคืนเงินไม่เกิน 255 ตัวอักษร']);
        }
        DB::transaction(function () use ($refundId, $actor, $reference) {
            $refund = DB::table('payment_refunds')->where('id', $refundId)->lockForUpdate()->first();
            abort_unless($refund, 404);
            if ($refund->status === 'completed') {
                return;
            }
            DB::table('payment_refunds')->where('id', $refundId)->update(['status' => 'completed', 'completed_by' => $actor->id, 'completed_at' => now(), 'ref_no' => trim($reference), 'updated_at' => now()]);
        });
    }
}
