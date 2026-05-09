<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('occupancy_requests', function (Blueprint $table) {
            $table->text('owner_notes')->nullable()->after('agent_validated_at');
            $table->text('owner_rejection_reason')->nullable()->after('owner_notes');
            $table->timestamp('owner_reviewed_at')->nullable()->after('owner_rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupancy_requests', function (Blueprint $table) {
            //
        });
    }
};
