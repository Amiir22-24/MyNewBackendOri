<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        // Subscription for Agent 2
        DB::table('subscriptions')->insert([
            // Fixed ID removed for safe re-seeding
            'user_id' => 2,
            'stripe_subscription_id' => 'sub_test_12345',
            'amount' => 50000.00,
            'status' => 'active',
            'ends_at' => now()->addMonths(10),
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);
        $subscriptionId = DB::getPdo()->lastInsertId();

        // Payment for subscription
        DB::table('payments')->insert([
            // Fixed ID removed
            'user_id' => 2,
            'subscription_id' => $subscriptionId,
            'stripe_charge_id' => 'ch_test_12345',
            'amount' => 50000.00,
            'status' => 'succeeded',
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);
        $paymentId = DB::getPdo()->lastInsertId();

        // Transaction - Commission for Agent
        DB::table('transactions')->insert([
            // Fixed ID removed
            'user_id' => 2,
            'property_id' => 1,
            'amount' => 25000.00,
            'currency' => 'XOF',
            'stripe_payment_intent_id' => null,
            'status' => 'succeeded',
            'type' => 'commission',
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);
        $transactionId = DB::getPdo()->lastInsertId();

        // Commission 
        DB::table('commissions')->insert([
            // Fixed ID removed
            'agent_id' => 2,
            'property_id' => 1,
            'transaction_id' => $transactionId,
            'amount' => 25000.00,
            'rate' => 10.00,
            'status' => 'paid',
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        // Receipt 
        DB::table('receipts')->insert([
            // Fixed ID removed
            'user_id' => 2,
            'transaction_id' => $transactionId,
            'receipt_number' => 'REC-2025-001',
            'pdf_url' => 'receipts/REC-2025-001.pdf',
            'amount' => 25000.00,
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        echo "✅ Paiements/Transactions créés (Sub:{$subscriptionId}, Trans:{$transactionId})\n";
    }
}
