<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ConversationParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;

class ChatController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/ChatController --api
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
    public function messages(Request $request, $conversationId)
    {
        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $request->user()->id))
            ->with('messages.user')
            ->findOrFail($conversationId);

        $messages = $conversation->messages()->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:2000',
            'type' => 'string|in:text,image,file',
        ]);

        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($conversationId);

        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $request->user()->id,
            'content' => $validator->validated()['content'],
            'type' => $request->type ?? 'text',
        ]);

        // Notify other participants
        $otherParticipants = $conversation->participants->where('user_id', '!=', $request->user()->id);
        foreach ($otherParticipants as $participant) {
            Notification::create([
                'user_id' => $participant->user_id,
                'type' => 'new_message',
                'title' => 'Nouveau message',
                'data' => ['conversation_id' => $conversationId],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé',
            'data' => $message->load('user')
        ], 201);
    }

    // createConversation method...
}
