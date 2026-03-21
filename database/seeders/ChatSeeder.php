<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        // Conversation ID=1: Agent 2 <-> Client 4 (Property 1)
        DB::table('conversations')->insert([
            'id' => 1,
            'property_id' => 1,
            'subject' => 'Discussion Appartement Cocody - Paul Durand',
            'created_at' => now()->subDays(2),
            'updated_at' => now(),
        ]);

        // Participants
        DB::table('conversation_participants')->insert([
            ['conversation_id' => 1, 'user_id' => 2, 'is_admin' => false, 'created_at' => now()->subDays(2), 'updated_at' => now()], // Agent
            ['conversation_id' => 1, 'user_id' => 4, 'is_admin' => false, 'created_at' => now()->subDays(2), 'updated_at' => now()], // Client
        ]);

        // Messages (5 messages)
        $messages = [
            ['id' => 1, 'conversation_id' => 1, 'sender_id' => 4, 'message' => 'Bonjour, intéressé par votre appartement Cocody. Disponible pour visite?', 'is_read' => true, 'created_at' => now()->subDays(2)->addHours(10)],
            ['id' => 2, 'conversation_id' => 1, 'sender_id' => 2, 'message' => 'Bonjour Paul, oui disponible samedi 14h. Propriétaire d\'accord.', 'is_read' => true, 'created_at' => now()->subDays(2)->addHours(11)],
            ['id' => 3, 'conversation_id' => 1, 'sender_id' => 4, 'message' => 'Parfait, j\'y serai. Merci!', 'is_read' => true, 'created_at' => now()->subDays(2)->addHours(12)],
            ['id' => 4, 'conversation_id' => 1, 'sender_id' => 2, 'message' => 'Visite OK, demande occupancy soumise et approuvée. Contrat prêt.', 'is_read' => false, 'created_at' => now()->subDay()->addHours(15)],
            ['id' => 5, 'conversation_id' => 1, 'sender_id' => 4, 'message' => 'Super, merci pour votre réactivité!', 'is_read' => false, 'created_at' => now()->subDay()->addHours(16)],
        ];

        foreach ($messages as $msg) {
            $msg['updated_at'] = $msg['created_at'];
            DB::table('messages')->insert($msg);
        }

        echo "✅ Chat créé: Conversation 1 (Agent2-Client4), 5 messages (2 unread)\n";
    }
}
