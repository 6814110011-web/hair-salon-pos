<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku',30)->unique();
            $table->string('name');
            $table->decimal('cost_price',10, 2)->default(0);
            $table->decimal('sell_price', 10, 2);
            $table->integer('stock_qty')->default(0);
            $table->integer('min_stock')->default(5);
            $table->string('unit', 20)->default('ชิ้น');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
