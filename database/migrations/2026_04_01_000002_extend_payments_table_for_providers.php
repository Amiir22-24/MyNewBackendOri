<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // On vérifie si la colonne n'existe pas déjà pour éviter les doublons
            if (!Schema::hasColumn('payments', 'stripe_charge_id')) {
                $table->string('stripe_charge_id')->nullable()->unique();
            }

            // Ajoute ici les autres colonnes que tu voulais ajouter
            if (!Schema::hasColumn('payments', 'payment_provider')) {
                $table->string('payment_provider')->default('stripe');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_charge_id', 'payment_provider']);
        });
    }
};