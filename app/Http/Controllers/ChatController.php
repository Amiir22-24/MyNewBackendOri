<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ConversationParticipant;
use App\Models\Property;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get user conversations
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = Conversation::with(['participants.user', 'lastMessage'])
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Get conversation messages
     */
    public function messages(Request $request, $conversationId) {
        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $request->user()->id))
            ->orWhere(fn($q) => $request->user()->is_admin ? $q->whereNotNull('id') : $q->where('id', 0))
            ->with('messages.user')
            ->findOrFail($conversationId);

        return response()->json(['success' => true, 'data' => $conversation->messages()->latest()->paginate(50)]);
    }

    // Nouvelle méthode pour l'admin
    public function all(Request $request) {
        if (!$request->user()->is_admin) return response()->json(['success' => false], 403);
        
        $conversations = Conversation::with(['participants.user', 'lastMessage', 'property'])
            ->orderBy('updated_at', 'desc')->paginate(50);

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'type' => 'nullable|string|in:text,image,file,visit_request,property_confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($conversationId);

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $request->user()->id,
            'message' => $validator->validated()['message'],
            'type' => $request->type ?? 'text',
        ]);

        // Trigger touch to update updated_at on conversation
        $conversation->touch();

        // Notify other participants
        $otherParticipants = $conversation->participants->where('user_id', '!=', $request->user()->id);
        foreach ($otherParticipants as $participant) {
            Notification::create([
                'user_id' => $participant->user_id,
                'type' => 'new_message',
                'title' => 'Nouveau message',
                'message' => "Vous avez reçu un nouveau message",
                'data' => ['conversation_id' => $conversationId],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé',
            'data' => $message->load('sender')
        ], 201);
    }

    /**
     * Create or Get existing conversation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Le champ property_id est requis.',
                'errors' => $validator->errors()
            ], 422);
        }

        $property = Property::find($request->property_id);
        if (!$property) {
            return response()->json(['success' => false, 'message' => 'Propriété introuvable'], 404);
        }

        $client = $request->user();
        // PRIORITÉ : Agent, sinon Propriétaire
        $targetUserId = $property->agent_id ?? $property->owner_id;

        if (!$targetUserId) {
            return response()->json(['success' => false, 'message' => 'Aucun responsable trouvé pour discuter'], 404);
        }

        // Vérifier si une conversation existe déjà entre ces deux-là pour ce bien spécifique
        $existing = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $client->id))
            ->whereHas('participants', fn($q) => $q->where('user_id', $targetUserId))
            ->where('property_id', $property->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => $existing->load('participants.user')
            ]);
        }

        return DB::transaction(function () use ($property, $client, $targetUserId) {
            // Créer la conversation
            $conversation = Conversation::create([
                'subject' => "Propriété : " . $property->title,
                'property_id' => $property->id,
            ]);

            // Ajouter les participants
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $client->id
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $targetUserId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation créée avec succès',
                'data' => $conversation->load('participants.user')
            ], 201);
        });
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(Request $request, $id)
    {
        Message::where('conversation_id', $id)
            ->where('sender_id', '!=', $request->user()->id)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Marquer un message spécifique comme lu
     */
    public function markMessageRead(Request $request, $id) {
        $message = Message::findOrFail($id);
        
        if ($message->sender_id != $request->user()->id) {
            $message->update(['is_read' => true]);
        }
               
        return response()->json(['success' => true]);
    }

    /**
     * Archiver la conversation
     */
    public function close(Request $request, $id) {
        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($id);
            
        $conversation->update(['is_archived' => true]);
               
        return response()->json(['success' => true, 'message' => 'Conversation archivée']);
    }

    /**
     * Get specific conversation
     */
    public function show(Request $request, $id)
    {
        $conversation = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['participants.user', 'property'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $conversation,
        ]);
    }
}
