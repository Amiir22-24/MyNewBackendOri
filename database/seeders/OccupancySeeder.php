<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OccupancySeeder extends Seeder
{
    public function run(): void
    {
        // OccupancyRequest ID=1 for Property 1 by Client 4
        DB::table('occupancy_requests')->updateOrInsert([
            'id' => 1,
            'property_id' => 1,
            'client_id' => 4,
            'owner_id' => 3,
            'agent_id' => 2,
            'start_date' => now()->addDays(30),
            'end_date' => now()->addMonths(12),
            'proposed_amount' => 250000,
            'status' => 'approved',
            'rent_amount' => 250000,
            'currency' => 'XOF',
            'message' => 'Intérêt pour location appartement Cocody 12 mois',
            'created_at' => now()->subDays(1),
            'updated_at' => now(),
        ]);

        // OccupancyContract ID=1 linked to request 1
        DB::table('occupancy_contracts')->updateOrInsert([
            'id' => 1,
            'occupancy_request_id' => 1,
            'property_id' => 1,
            'tenant_id' => 4,
            'owner_id' => 3,
            'agent_id' => 2,
            'start_date' => now()->addDays(30),
            'end_date' => now()->addMonths(12),
            'monthly_rent' => 250000,
            'deposit_amount' => 500000,
            'contract_url' => 'contracts/contract_1.pdf',
            'is_active' => true,
            'signed_at' => now(),
            'created_at' => now()->subDays(1),
            'updated_at' => now(),
        ]);

        // Request 2 - Pending
        DB::table('occupancy_requests')->updateOrInsert([
            'id' => 2,
            'property_id' => 4,
            'client_id' => 4,
            'owner_id' => 3,
            'agent_id' => 2,
            'start_date' => now()->addDays(15),
            'end_date' => now()->addMonths(6),
            'proposed_amount' => 500000,
            'status' => 'pending',
            'rent_amount' => 500000,
            'currency' => 'XOF',
            'message' => 'Demande bureau Zone 4 6 mois',
            'created_at' => now()->subHours(5),
            'updated_at' => now(),
        ]);

        echo "✅ Occupancy data créée (Request 1 approved, Contract 1 active, Property 1 occupied)\n";
    }
}
