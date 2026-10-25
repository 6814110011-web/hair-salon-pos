<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'category' => 'nullable|in:cut,wash,color,perm,straighten,treatment,other', 'active' => 'nullable|in:0,1']);
        $q = $filters['q'] ?? '';
        $services = Service::when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))->when(isset($filters['category']), fn ($query) => $query->where('category', $filters['category']))->when(isset($filters['active']), fn ($query) => $query->where('is_active', $filters['active']))->latest('id')->paginate(15)->withQueryString();

        return view('services.index', compact('services', 'filters', 'q'));
    }

    public function create(): View
    {
        return view('services.form', ['service' => new Service(['is_active' => true, 'duration_minutes' => 30, 'category' => 'cut'])]);
    }

    public function edit(Service $service): View
    {
        return view('services.form', compact('service'));
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return redirect()->route('services.index')->with('success', 'เพิ่มบริการแล้ว');
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('services.index')->with('success', 'บันทึกบริการแล้ว');
    }
}
