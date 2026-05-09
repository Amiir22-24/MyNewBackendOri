<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin - ID=1 pour tests
        DB::table('users')->insert([
            // Fixed ID removed for safe re-seeding
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@ori.com',
            'phone' => '+2250700000001',
            'password' => Hash::make('password123'),
            'user_type' => 'admin',
            'status' => 'validated',
            'avatar' => 'https://ui-avatars.com/api/?name=Admin&background=1e88e5&color=fff&size=128&bold=true',
'validation_notes' => 'Super Admin système',
            'matricule' => 'ADMIN-001',  // FIXED: Required for login
            'is_admin' => true,

            'updated_at' => now(),
        ]);


        echo "✅ Admin créé (ID=1): admin@ori.com / password123\n";
    }
}

