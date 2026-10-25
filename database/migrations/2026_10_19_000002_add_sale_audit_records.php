<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('visit_counted')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->constrained('sales')->restrictOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->unique()
                ->constrained('stock_movements')->restrictOnDelete();
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->foreignId('reversal_of_id')->nullable()->unique()
                ->constrained('point_transactions')->restrictOnDelete();
        });

        Schema::create('promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('promotion_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('visit_milestone');

            $table->unsignedInteger('active_milestone')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'promotion_id', 'active_milestone'], 'promotion_customer_milestone_unique');
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->string('ref_no')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('promotion_redemptions');

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_id']);
            $table->dropUnique(['reversal_of_id']);
            $table->dropColumn('reversal_of_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_id']);
            $table->dropForeign(['sale_id']);
            $table->dropUnique(['reversal_of_id']);
            $table->dropColumn(['reversal_of_id', 'sale_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['visit_counted', 'cancelled_at', 'cancelled_by', 'cancellation_reason']);
        });
    }
};
