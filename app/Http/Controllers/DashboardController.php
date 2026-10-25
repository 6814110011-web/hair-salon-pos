<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SalesReportService $service): View
    {
        $user = $request->user();
        $start = now()->startOfDay();
        $end = now()->addDay()->startOfDay();
        if ($user->hasRole('stylist')) {
            $assigned = SaleItem::select(['id', 'sale_id', 'stylist_id', 'item_name', 'qty'])->where('stylist_id', $user->id)->whereHas('sale', fn ($q) => $q->where('status', 'paid')->where('created_at', '>=', $start)->where('created_at', '<', $end))->with('sale:id,bill_no,created_at')->latest('id')->get();

            return view('dashboard', compact('assigned'));
        }
        $isAdmin = $user->hasRole('admin');
        $report = $service->summary(now()->toDateString(), now()->toDateString(), $isAdmin ? null : $user->id);
        $recent = Sale::with('customer')->when(! $isAdmin, fn ($q) => $q->where('user_id', $user->id))->latest('id')->limit(6)->get();
        $lowStock = $isAdmin ? Product::where('is_active', true)->whereColumn('stock_qty', '<=', 'min_stock')->orderBy('stock_qty')->limit(6)->get(['id', 'name', 'stock_qty', 'unit']) : collect();

        return view('dashboard', compact('report', 'recent', 'lowStock', 'isAdmin'));
    }
}
