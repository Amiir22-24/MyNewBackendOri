<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminSeeder extends Seeder
{
    // Database/Seeders/AdminSeeder.php

    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@ori.com'], // On vérifie si cet email existe déjà
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+2250700000001',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'status' => 'validated',
                'avatar' => 'https://ui-avatars.com/api/?name=Admin&background=1e88e5&color=fff&size=128&bold=true',
                'validation_notes' => 'Super Admin système',
                'matricule' => 'ADMIN-001',
                'is_admin' => true,
                'updated_at' => now(),
            ]
        );

        echo "✅ Admin synchronisé: admin@ori.com / password123\n";
    }
}

