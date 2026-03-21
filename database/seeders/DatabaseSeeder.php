<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            UserSeeder::class,
            ProfileSeeder::class,
            PropertySeeder::class,
            OccupancySeeder::class,
            NotificationSeeder::class,
            ChatSeeder::class,
            PaymentTransactionSeeder::class,
        ]);

        echo "\n🎉 TOUS SEEDERS TERMINÉS! Utilisez ces IDs dans frontend:\n";
        echo "Admin: ID=1 (admin@ori.com/password123)\n";
        echo "Agent: ID=2 (agent@ori.com/password123)\n";
        echo "Owner: ID=3 (owner@ori.com/password123)\n";
        echo "Client: ID=4 (client@ori.com/password123)\n";
        echo "Property exemple: /api/properties/1\n";
        echo "Occupancy: Request1/Contract1\n";
        echo "Chat: Conversation1\n";
        echo "Payments: Sub1/Payment1/Transaction1\n";
    }
}
