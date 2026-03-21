<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: notifications
     * Artisan: php artisan make:migration create_notifications_table
     * Relations: N:1 user_id
     * FKs: user_id constrained('users')->onDelete('cascade')
     * Indexes: user_id + is_read + created_at
     * Constraints: boolean is_read default false, json data nullable
     */

    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // new_property, rejected, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('action_route')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
