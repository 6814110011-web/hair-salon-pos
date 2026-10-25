<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'role:admin,cashier'])->group(function () {
    Route::resource('customers', CustomerController::class)->except('destroy');
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::post('refunds/{refund}/complete', [RefundController::class, 'complete'])->whereNumber('refund')->name('refunds.complete');
    Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
    Route::resource('products', ProductController::class)->except(['index', 'destroy']);
    Route::resource('services', ServiceController::class)->except(['index', 'show', 'destroy']);
    Route::post('products/{product}/stock', [StockController::class, 'store'])->name('products.stock.store');
});

Route::get('products', [ProductController::class, 'index'])->middleware(['auth', 'role:admin,cashier'])->name('products.index');
Route::get('services', [ServiceController::class, 'index'])->middleware(['auth', 'role:admin,cashier,stylist'])->name('services.index');
