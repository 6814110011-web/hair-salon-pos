<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\StockRequest;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;

class StockController extends Controller
{
    public function store(StockRequest $request, Product $product, StockService $service): RedirectResponse
    {
        $data = $request->validated();
        if ($data['mode'] === 'receive') {
            $service->receive($request->user(), $product, (int) $data['quantity'], $data['reason']);
        } else {
            $service->adjust($request->user(), $product, (int) $data['target_quantity'], $data['reason']);
        }

        return redirect()->route('products.show', $product)->with('success', 'บันทึกสต็อกและประวัติแล้ว');
    }
}
