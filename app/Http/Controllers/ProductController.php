<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'active' => 'nullable|in:0,1', 'low_stock' => 'nullable|in:1']);
        $q = $filters['q'] ?? '';
        $isAdmin = $request->user()->hasRole('admin');
        $query = Product::query();
        if (! $isAdmin) {
            $query->select(['id', 'sku', 'name', 'sell_price', 'stock_qty', 'min_stock', 'unit', 'is_active'])->where('is_active', true);
        }
        $products = $query->when($q !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('sku', 'like', '%'.$q.'%')))
            ->when($isAdmin && isset($filters['active']), fn ($query) => $query->where('is_active', $filters['active']))
            ->when(isset($filters['low_stock']), fn ($query) => $query->whereColumn('stock_qty', '<=', 'min_stock'))->latest('id')->paginate(15)->withQueryString();

        return view('products.index', compact('products', 'filters', 'q', 'isAdmin'));
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product(['is_active' => true, 'min_stock' => 5, 'unit' => 'ชิ้น', 'cost_price' => 0])]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', compact('product'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()->route('products.show', $product)->with('success', 'เพิ่มสินค้าแล้ว รับสินค้าเข้าสต็อกได้ด้านล่าง');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('products.show', $product)->with('success', 'บันทึกสินค้าแล้ว');
    }

    public function show(Product $product): View
    {
        $movements = $product->stockMovements()->with('user')->latest('id')->paginate(20);

        return view('products.show', compact('product', 'movements'));
    }
}
