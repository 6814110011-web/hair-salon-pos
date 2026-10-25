<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q = (string) ($request->validate(['q' => 'nullable|string|max:255'])['q'] ?? '');
        $customers = Customer::when($q !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%')))->latest('id')->paginate(15)->withQueryString();

        return view('customers.index', compact('customers', 'q'));
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', compact('customer'));
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'เพิ่มลูกค้าแล้ว');
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'บันทึกข้อมูลลูกค้าแล้ว');
    }

    public function show(Customer $customer): View
    {
        $sales = $customer->sales()->with('items')->latest('id')->paginate(15);

        return view('customers.show', compact('customer', 'sales'));
    }
}
