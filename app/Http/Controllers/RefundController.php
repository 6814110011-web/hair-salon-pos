<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\RefundRequest;
use App\Services\PaymentRefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function complete(RefundRequest $request, int $refund, PaymentRefundService $service): RedirectResponse
    {
        $record = DB::table('payment_refunds')->join('payments', 'payments.id', '=', 'payment_refunds.payment_id')->where('payment_refunds.id', $refund)->select('payments.sale_id')->first();
        abort_unless($record, 404);
        $service->complete($refund, $request->user(), $request->validated('ref_no'));

        return redirect()->route('sales.show', $record->sale_id)->with('success', 'บันทึกการคืนเงินจริงแล้ว');
    }
}
