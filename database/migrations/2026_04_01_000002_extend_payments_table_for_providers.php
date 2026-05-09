<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        foreach (DB::select("SHOW INDEX FROM payments WHERE Column_name = 'stripe_charge_id' AND Non_unique = 0") as $idx) {
            if ($idx->Key_name !== 'PRIMARY') {
                DB::statement('ALTER TABLE payments DROP INDEX `'.$idx->Key_name.'`');
            }
        }

        DB::statement('ALTER TABLE payments MODIFY stripe_charge_id VARCHAR(255) NULL');

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('status');
            }
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type', 64)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('payments', 'external_reference')) {
                $table->string('external_reference')->nullable()->after('payment_type');
            }
            if (! Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('external_reference');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            foreach (['metadata', 'external_reference', 'payment_type', 'payment_method'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
