<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        // Conversation: Agent-Client (Property 1)
        DB::table('conversations')->updateorInsert([
            'property_id' => 1,
            'subject' => 'Discussion Appartement Cocody - Paul Durand',
            'created_at' => now()->subDays(2),
            'updated_at' => now(),
        ]);
        $conversationId = DB::getPdo()->lastInsertId();

        // Participants
        DB::table('conversation_participants')->insert([
            ['conversation_id' => $conversationId, 'user_id' => 2, 'created_at' => now()->subDays(2), 'updated_at' => now()], // Agent
            ['conversation_id' => $conversationId, 'user_id' => 4, 'created_at' => now()->subDays(2), 'updated_at' => now()], // Client
        ]);

        // Messages (5 messages)
        $messages = [
            ['conversation_id' => $conversationId, 'sender_id' => 4, 'content' => 'Bonjour, intéressé par votre appartement Cocody. Disponible pour visite?', 'is_read' => true, 'created_at' => now()->subDays(2)->addHours(10)],
            ['conversation_id' => $conversationId, 'sender_id' => 2, 'content' => 'Bonjour Paul, oui disponible samedi 14h. Propriétaire d\'accord.', 'is_read' => true, 'created_at' => now()->subDays(2)->addHours(11)],
            ['conversation_id' => $conversationId, 'sender_id' => 4, 'content' => 'Parfait, j y serai. Merci!', 'is_read' => true, 'created_at' => now()->subDays(2)->addHours(12)],
            ['conversation_id' => $conversationId, 'sender_id' => 2, 'content' => 'Visite OK, demande occupancy soumise et approuvée. Contrat prêt.', 'is_read' => false, 'created_at' => now()->subDay()->addHours(15)],
            ['conversation_id' => $conversationId, 'sender_id' => 4, 'content' => 'Super, merci pour votre réactivité!', 'is_read' => false, 'created_at' => now()->subDay()->addHours(16)],
        ];

        foreach ($messages as $msg) {
            $msg['updated_at'] = $msg['created_at'];
            DB::table('messages')->insert($msg);
        }

        echo "✅ Chat créé (Conv ID: {$conversationId}), 5 messages (2 unread)\n";
    }
}

