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
            $table->foreignId('owner_id')->after('client_id')->constrained('users')->onDelete('cascade');
            $table->decimal('rent_amount', 12, 2)->after('end_date');
            $table->string('currency', 3)->after('rent_amount')->default('XOF');
            $table->text('message')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_requests', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn(['owner_id', 'rent_amount', 'currency', 'message']);
        });
    }
};
