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
        // Syntaxe PostgreSQL pour changer le type d'une colonne
        DB::statement("ALTER TABLE properties ALTER COLUMN catalog_type TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE properties ALTER COLUMN catalog_type SET DEFAULT 'residential'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On peut soit laisser en VARCHAR, soit remettre une contrainte si nécessaire
        DB::statement("ALTER TABLE properties ALTER COLUMN catalog_type TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE properties ALTER COLUMN catalog_type SET DEFAULT 'residential'");
    }
};