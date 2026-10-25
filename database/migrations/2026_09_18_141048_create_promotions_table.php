<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('promotions', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->integer('visit_required');
    $table->enum('reward_type', ['percent','amount']);
    $table->decimal('reward_value', 10, 2);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
