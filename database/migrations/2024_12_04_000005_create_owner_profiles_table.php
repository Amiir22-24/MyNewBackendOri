<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: owner_profiles
     * Artisan: php artisan make:migration create_owner_profiles_table
     * Relations: 1:1 user_id (users where user_type='owner')
     * FKs: user_id constrained('users')->unique()->onDelete('cascade')
     * Indexes: validation_status
     * Constraints: enum owner_type/validation_status
     */

    public function up(): void
    {
        Schema::create('owner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->unique()->onDelete('cascade');
            $table->enum('owner_type', ['individual', 'company'])->default('individual');
            $table->string('company_name')->nullable();
            $table->enum('validation_status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->text('validation_notes')->nullable();
            $table->timestamps();

            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};
