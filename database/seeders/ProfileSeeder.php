<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        // AgentProfile for Agent ID=2
        DB::table('agent_profiles')->updateOrInsert([
            'user_id' => 2,
            'registration_number' => 'AGT-2025-000001',
            'commission_rate' => 10.0,
            'validation_status' => 'validated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // OwnerProfile for Owner ID=3
        DB::table('owner_profiles')->updateOrInsert([
            'user_id' => 3,
            'owner_type' => 'individual',
            'company_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Profiles créés: AgentProfile(2), OwnerProfile(3)\n";
    }
}
