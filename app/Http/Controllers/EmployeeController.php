<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\EmployeeRequest;
use App\Models\User;
use App\Services\StaffAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'active' => 'nullable|in:0,1', 'role' => 'nullable|in:admin,cashier,stylist']);
        $q = $filters['q'] ?? '';
        $employees = User::when($q !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')->orWhere('role', 'like', '%'.$q.'%')))
            ->when(isset($filters['active']), fn ($query) => $query->where('is_active', $filters['active']))
            ->when(isset($filters['role']), fn ($query) => $query->where('role', $filters['role']))->latest('id')->paginate(15)->withQueryString();

        return view('employees.index', compact('employees', 'filters', 'q'));
    }

    public function create(): View
    {
        return view('employees.form', ['employee' => new User(['name' => '', 'email' => ''])]);
    }

    public function edit(User $employee): View
    {
        return view('employees.form', compact('employee'));
    }

    public function store(EmployeeRequest $request, StaffAccountService $service): RedirectResponse
    {
        $service->create($request->user(), $request->validated());

        return redirect()->route('employees.index')->with('success', 'เพิ่มพนักงานแล้ว');
    }

    public function update(EmployeeRequest $request, User $employee, StaffAccountService $service): RedirectResponse
    {
        $service->update($request->user(), $employee, $request->validated());

        return redirect()->route('employees.index')->with('success', 'บันทึกพนักงานแล้ว');
    }
}
