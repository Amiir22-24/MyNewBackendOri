<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('occupancy_requests', function (Blueprint $table) {
            // Colonnes manquantes pour le workflow multi-étapes
            $table->timestamp('agent_validated_at')->nullable()->after('agent_notes');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejection_reason');
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
        });

        // Élargir l'enum status pour inclure les nouveaux statuts du workflow
        DB::statement("ALTER TABLE occupancy_requests MODIFY COLUMN status ENUM('pending', 'pending_agent', 'pending_owner', 'approved', 'rejected', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupancy_requests', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['agent_validated_at', 'rejected_by']);
        });

        DB::statement("ALTER TABLE occupancy_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};
