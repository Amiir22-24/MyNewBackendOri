<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: occupancy_contracts
     * Artisan: php artisan make:migration create_occupancy_contracts_table
     * Relations: N:1 property_id/tenant_id/owner_id/agent_id
     * FKs: all constrained('users' or 'properties')->cascade/set null
     * Indexes: is_active, property_id
     * Constraints: boolean is_active default true, dates validation in app
     */

    public function up(): void
    {
        Schema::create('occupancy_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->decimal('monthly_rent', 12, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('contract_url'); // S3/PDF path
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupancy_contracts');
    }
};
