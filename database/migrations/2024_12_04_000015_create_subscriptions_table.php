<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: subscriptions
     * Artisan: php artisan make:migration create_subscriptions_table
     * Relations: N:1 user_id; 1:N payments
     * FKs: user_id constrained('users')
     * Indexes: status, ends_at
     * Constraints: unique stripe_subscription_id, enum status
     */

    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
