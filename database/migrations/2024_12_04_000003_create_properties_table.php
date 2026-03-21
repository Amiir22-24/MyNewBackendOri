<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: properties
     * Artisan: php artisan make:migration create_properties_table
     * Relations: N:1 owner_id (users), agent_id (users), occupied_by_user_id (users), rejected_by_admin_id (users)
     * FKs: owner_id, agent_id, occupied_by_user_id, rejected_by_admin_id all constrained('users')->onDelete('cascade' or 'set null')
     * Indexes: composite status+is_available, city, owner_id, created_at
     * Constraints: enum catalog_type/property_type/operation_type/condition/status, unique none additional, json photos/amenities validated in app
     */

    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('catalog_type', ['residential', 'commercial', 'project'])->default('residential');
            $table->enum('property_type', ['apartment','house','villa','studio','bureau','land','commercial','garage']);
            $table->enum('operation_type', ['rent','sale','lease','reservation']);
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('XOF');
            $table->string('price_period')->nullable(); // monthly, yearly, daily
            $table->enum('condition', ['new','good','average','renovation_needed'])->default('good');
            $table->string('address');
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('neighborhood')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedInteger('bedrooms')->default(0);
            $table->unsignedInteger('bathrooms')->default(0);
            $table->decimal('surface_area', 8, 2)->default(0);
            $table->unsignedInteger('floors')->default(1);
            $table->integer('star_rating')->default(1);
            $table->json('photos'); // [{"photo_url": "...", "is_main": true}]
            $table->json('amenities')->default('[]');

            // Owner/Agent
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('owner_name');
            $table->string('owner_phone')->nullable();
            $table->string('owner_matricule')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->string('agent_name')->nullable();

            // Status/Availability/Occupancy
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_occupied')->default(false);
            $table->foreignId('occupied_by_user_id')->nullable()->constrained('users');
            $table->string('occupied_by_user_name')->nullable();
            $table->timestamp('occupied_at')->nullable();
            $table->string('contract_url')->nullable();

            // Validation workflow
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->boolean('was_auto_validated')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by_admin_id')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_available']);
            $table->index('city');
            $table->index('owner_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
