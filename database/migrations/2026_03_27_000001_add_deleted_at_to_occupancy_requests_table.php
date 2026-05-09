<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupancy_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('occupancy_requests', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_requests', function (Blueprint $table) {
            if (Schema::hasColumn('occupancy_requests', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
