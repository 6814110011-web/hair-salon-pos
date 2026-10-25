<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('bill_no', 20)->unique();
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
    $table->decimal('subtotal', 10, 2)->default(0);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('total', 10, 2)->default(0);
    $table->enum('status', ['pending','paid','cancelled'])->default('pending');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
