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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE properties MODIFY catalog_type ENUM('residential', 'commercial', 'project', 'land') DEFAULT 'residential'");
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE properties MODIFY catalog_type ENUM('residential', 'commercial', 'project') DEFAULT 'residential'");
    }
};
