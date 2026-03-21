<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: receipts
     * Artisan: php artisan make:migration create_receipts_table
     * Relations: N:1 user_id/transaction_id
     * FKs: user_id constrained('users'), transaction_id constrained('transactions')
     * Indexes: user_id, receipt_number
     * Constraints: unique receipt_number, string pdf_url, decimal amount
     */

    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->string('receipt_number')->unique();
            $table->string('pdf_url'); // S3 path to PDF receipt
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
