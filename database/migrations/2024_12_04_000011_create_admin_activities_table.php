<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: admin_activities
     * Artisan: php artisan make:migration create_admin_activities_table
     * Relations: N:1 admin_id; polymorphic target_id/target_type
     * FKs: admin_id constrained('users')
     * Indexes: admin_id + created_at
     * Constraints: json details nullable
     */

    public function up(): void
    {
        Schema::create('admin_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // property_validated, user_rejected etc.
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_type')->nullable(); // 'App\Models\Property', 'App\Models\User'
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activities');
    }
};
