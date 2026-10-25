<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\CancelSaleRequest;
use App\Http\Requests\Pos\CheckoutRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\Service;
use App\Models\User;
use App\Services\SaleCancellationService;
use App\Services\SaleCheckoutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'status' => 'nullable|in:paid,pending,cancelled']);
        $sales = Sale::with(['customer', 'user'])->when(! $request->user()->hasRole('admin'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when(! empty($filters['q']), fn ($query) => $query->where('bill_no', 'like', '%'.$filters['q'].'%'))
            ->when(! empty($filters['from']), fn ($query) => $query->where('created_at', '>=', $filters['from'].' 00:00:00'))
            ->when(! empty($filters['to']), fn ($query) => $query->where('created_at', '<', Carbon::parse($filters['to'])->addDay()->startOfDay()))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))->latest('id')->paginate(15)->withQueryString();

        return view('sales.index', compact('sales', 'filters'));
    }

    public function create(): View
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'sell_price', 'stock_qty', 'unit']);
        $services = Service::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price', 'duration_minutes']);
        $catalogue = $products->map(fn ($p) => ['id' => $p->id, 'type' => 'product', 'name' => $p->name, 'sku' => $p->sku, 'price' => $p->sell_price, 'stock' => $p->stock_qty, 'unit' => $p->unit])->concat($services->map(fn ($s) => ['id' => $s->id, 'type' => 'service', 'name' => $s->name, 'price' => $s->price, 'minutes' => $s->duration_minutes]))->values();
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone', 'total_visits']);
        $stylists = User::where('role', 'stylist')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $promotions = Promotion::where('is_active', true)->get(['id', 'name', 'visit_required', 'reward_type', 'reward_value']);
        $token = Str::isUuid(old('checkout_token')) ? old('checkout_token') : (string) Str::uuid();
        $oldItems = old('items', []);
        $oldItems = is_array($oldItems) ? array_values(array_filter($oldItems, 'is_array')) : [];

        return view('sales.create', compact('catalogue', 'customers', 'stylists', 'promotions', 'token', 'oldItems'));
    }

    public function store(CheckoutRequest $request, SaleCheckoutService $service): RedirectResponse
    {
        $sale = $service->checkout($request->user(), $request->validated());

        return redirect()->route('sales.show', $sale)->with('success', 'ชำระเงินและบันทึกบิลแล้ว');
    }

    public function show(Request $request, Sale $sale): View
    {
        abort_unless($request->user()->hasRole('admin') || $sale->user_id === $request->user()->id, 403);
        $sale->load(['customer', 'user', 'items.stylist', 'payments']);
        $refunds = DB::table('payment_refunds')->leftJoin('users', 'users.id', '=', 'payment_refunds.completed_by')
            ->whereIn('payment_refunds.payment_id', $sale->payments->pluck('id'))->select('payment_refunds.*', 'users.name as completed_by_name')->get();

        return view('sales.show', compact('sale', 'refunds'));
    }

    public function cancel(CancelSaleRequest $request, Sale $sale, SaleCancellationService $service): RedirectResponse
    {
        $service->cancel($sale->id, $request->user(), $request->validated('reason'));

        return redirect()->route('sales.show', $sale)->with('success', 'ยกเลิกบิลแล้ว กรุณาตรวจสอบรายการรอคืนเงิน');
    }
}
