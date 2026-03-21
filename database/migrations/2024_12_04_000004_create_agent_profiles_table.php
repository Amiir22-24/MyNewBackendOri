<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: agent_profiles
     * Artisan: php artisan make:migration create_agent_profiles_table
     * Relations: 1:1 user_id (users where user_type='agent')
     * FKs: user_id constrained('users')->unique()->onDelete('cascade')
     * Indexes: user_id, validation_status
     * Constraints: unique registration_number, decimal commission_rate (0-100), enum validation_status
     */

    public function up(): void
    {
        Schema::create('agent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->unique()->onDelete('cascade');
            $table->string('registration_number')->unique(); // AGT-2024-000001
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->enum('validation_status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->text('validation_notes')->nullable();
            $table->timestamps();

            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_profiles');
    }
};
