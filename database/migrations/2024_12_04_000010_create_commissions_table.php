<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: commissions
     * Artisan: php artisan make:migration create_commissions_table
     * Relations: N:1 agent_id/property_id/transaction_id
     * FKs: constrained cascade/set null
     * Indexes: status, agent_id
     * Constraints: decimal amount/rate, enum status
     */

    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->decimal('amount', 12, 2);
            $table->decimal('rate', 5, 2); // percentage
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
