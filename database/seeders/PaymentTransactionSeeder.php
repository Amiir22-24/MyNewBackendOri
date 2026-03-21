<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        // Subscription ID=1 for Agent 2
        DB::table('subscriptions')->insert([
            'id' => 1,
            'user_id' => 2,
            'plan_name' => 'Premium Agent',
            'price' => 50000,
            'currency' => 'XOF',
            'status' => 'active',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(10),
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);

        // Payment ID=1 for subscription
        DB::table('payments')->insert([
            'id' => 1,
            'user_id' => 2,
            'subscription_id' => 1,
            'transaction_id' => 'TXN-2025-001',
            'amount' => 50000,
            'currency' => 'XOF',
            'payment_method' => 'mobile_money',
            'status' => 'success',
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);

        // Transaction ID=1 - Commission for Agent
        DB::table('transactions')->insert([
            'id' => 1,
            'user_id' => 2,
            'property_id' => 1,
            'type' => 'commission',
            'amount' => 25000,
            'currency' => 'XOF',
            'status' => 'completed',
            'description' => 'Commission 10% location Property 1',
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        // Commission ID=1
        DB::table('commissions')->insert([
            'id' => 1,
            'agent_id' => 2,
            'property_id' => 1,
            'occupancy_contract_id' => 1,
            'amount' => 25000,
            'currency' => 'XOF',
            'rate' => 10.0,
            'status' => 'paid',
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        // Receipt ID=1
        DB::table('receipts')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'user_id' => 2,
            'amount' => 25000,
            'currency' => 'XOF',
            'receipt_number' => 'REC-2025-001',
            'status' => 'issued',
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        echo "✅ Paiements/Transactions: Sub1(Agent2), Payment1, Transaction1, Commission1, Receipt1\n";
    }
}
