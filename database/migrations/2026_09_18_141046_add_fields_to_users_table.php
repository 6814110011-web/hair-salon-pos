<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role',['admin','cashier','stylist'])->default('cashier')->after('email');
            $table->string('phone',15)->nullable()->after('role');
            $table->decimal('commission_rate', 5,2)->default(0)->after('phone');
            $table->boolean('is_activate')->default(true)->after('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'commission_rate', 'is_activate']);
        });
    }
};
