<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('occupancy_contracts') && Schema::hasColumn('occupancy_contracts', 'contract_url')) {
            DB::statement('ALTER TABLE occupancy_contracts MODIFY contract_url VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('occupancy_contracts')) {
            DB::statement('ALTER TABLE occupancy_contracts MODIFY contract_url VARCHAR(255) NOT NULL');
        }
    }
};
