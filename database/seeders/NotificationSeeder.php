<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $notifications = [
            // For Admin ID=1
            ['id' => 1, 'user_id' => 1, 'title' => 'Nouvelle demande agent', 'message' => 'Jean Dupont demande validation', 'type' => 'agent_pending', 'is_read' => false],
            ['id' => 2, 'user_id' => 1, 'title' => 'Propriété rejetée', 'message' => 'Studio Treichville rejeté', 'type' => 'property_rejected', 'is_read' => true],
            // For Agent ID=2
            ['id' => 3, 'user_id' => 2, 'title' => 'Nouvelle occupancy request', 'message' => 'Paul Durand pour Appart Cocody', 'type' => 'occupancy_request', 'is_read' => false],
            ['id' => 4, 'user_id' => 2, 'title' => 'Commission reçue', 'message' => 'Commission Property 1: 25000 XOF', 'type' => 'commission_paid', 'is_read' => true],
            // For Owner ID=3
            ['id' => 5, 'user_id' => 3, 'title' => 'Property approved', 'message' => 'Appart Cocody validé par admin', 'type' => 'property_validated', 'is_read' => false],
            ['id' => 6, 'user_id' => 3, 'title' => 'Occupancy approved', 'message' => 'Paul Durand approuvé Property 1', 'type' => 'occupancy_approved', 'is_read' => true],
            // For Client ID=4
            ['id' => 7, 'user_id' => 4, 'title' => 'Contract signed', 'message' => 'Contrat Appart Cocody signé', 'type' => 'contract_signed', 'is_read' => false],
        ];

        foreach ($notifications as $notif) {
            $notif['created_at'] = now()->subMinutes(rand(1, 1440));
            $notif['updated_at'] = $notif['created_at'];
            DB::table('notifications')->updateOrInsert($notif);
        }

        echo "✅ 7 notifications créées (mix read/unread pour tous users)\n";
    }
}
