<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Syntaxe PostgreSQL pour autoriser les valeurs NULL
        DB::statement("ALTER TABLE receipts ALTER COLUMN pdf_url DROP NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pour revenir en arrière et interdire le NULL
        DB::statement("ALTER TABLE receipts ALTER COLUMN pdf_url SET NOT NULL");
    }
};