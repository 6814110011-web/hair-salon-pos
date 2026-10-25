<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('phone', 15)->unique();
    $table->string('name')->nullable();
    $table->integer('total_visits')->default(0);
    $table->timestamp('last_visit_at')->nullable();
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
