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
        Schema::table('messages', function (Blueprint $table) {
            // Renommer content en message seulement s'il existe
            if (Schema::hasColumn('messages', 'content') && !Schema::hasColumn('messages', 'message')) {
                $table->renameColumn('content', 'message');
            }
            
            // Ajouter type s'il n'existe pas
            if (!Schema::hasColumn('messages', 'type')) {
                $table->string('type')->default('text')->after('is_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'message') && !Schema::hasColumn('messages', 'content')) {
                $table->renameColumn('message', 'content');
            }
            if (Schema::hasColumn('messages', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
