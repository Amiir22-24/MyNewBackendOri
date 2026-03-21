<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: occupancy_requests
     * Artisan: php artisan make:migration create_occupancy_requests_table
     * Relations: N:1 property_id/client_id/agent_id
     * FKs: property_id ('properties'), client_id/agent_id ('users')
     * Indexes: status, property_id
     * Constraints: enum status, dates start_date < end_date (app validation)
     */

    public function up(): void
    {
        Schema::create('occupancy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('proposed_amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupancy_requests');
    }
};
