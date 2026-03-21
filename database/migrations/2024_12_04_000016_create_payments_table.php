<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: payments
     * Artisan: php artisan make:migration create_payments_table
     * Relations: N:1 user_id/subscription_id
     * FKs: user_id constrained('users'), subscription_id constrained('subscriptions')
     * Indexes: status, stripe_charge_id
     * Constraints: unique stripe_charge_id, enum status, decimal amount
     */

    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->string('stripe_charge_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'succeeded', 'failed'])->default('pending');
            $table->timestamps();

            $table->index('status');
            $table->index('stripe_charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
