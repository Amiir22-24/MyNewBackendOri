<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: transactions
     * Artisan: php artisan make:migration create_transactions_table
     * Relations: N:1 user_id, property_id nullable
     * FKs: user_id constrained('users')->onDelete('cascade'), property_id nullable constrained('properties')
     * Indexes: status
     * Constraints: decimal amount, unique stripe_payment_intent_id nullable, enum status default 'pending'
     */

    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XOF');
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->enum('status', ['pending', 'succeeded', 'failed'])->default('pending');
            $table->string('type'); // rent_payment, commission, deposit
            $table->timestamps();

            $table->index('status');
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
