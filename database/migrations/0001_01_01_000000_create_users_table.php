<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: users
     * Artisan: php artisan make:migration create_users_table
     * Relations: 1:1 agent_profiles/owner_profiles; N properties (owner/agent); N notifications/transactions/etc.
     * FKs: N/A (parent)
     * Indexes: email, phone, matricule, user_type+status
     * Constraints: unique email/phone/matricule, enum user_type/status, required first_name/last_name/email/phone/password
     */

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->unique();
            $table->string('password');
            $table->enum('user_type', ['admin', 'agent', 'owner', 'user'])->default('user');
            $table->enum('status', ['pending', 'validated', 'rejected', 'inactive'])->default('pending');
            $table->string('matricule')->nullable()->unique(); // PROP-YYYY-XXXXXX or AGT-YYYY-XXXXXX
            $table->string('avatar')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_type', 'status']);
            $table->index('status');
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
