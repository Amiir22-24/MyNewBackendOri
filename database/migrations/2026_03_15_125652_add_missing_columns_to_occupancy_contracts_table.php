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
        Schema::table('occupancy_contracts', function (Blueprint $table) {
            $table->foreignId('occupancy_request_id')->after('id')->constrained('occupancy_requests')->onDelete('cascade');
            $table->decimal('deposit_amount', 12, 2)->after('monthly_rent');
            $table->timestamp('signed_at')->after('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_contracts', function (Blueprint $table) {
            $table->dropForeign(['occupancy_request_id']);
            $table->dropColumn(['occupancy_request_id', 'deposit_amount', 'signed_at']);
        });
    }
};
