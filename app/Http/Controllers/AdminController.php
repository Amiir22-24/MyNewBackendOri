<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AgentProfile;
use App\Models\Property;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // ... existing methods from current AdminController (rejectProperty, getNewProperties, getRejectedProperties, getAllProperties, getPropertyDetail, dashboard, createAgent) ...

    /**
     * List all users with role filtering
     * Artisan: php artisan make:controller Api/AdminController --api
     */
    public function getUsers(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = User::with(['agentProfile', 'ownerProfile']);
        
        if ($request->type) {
            $query->where('user_type', $request->type);
        }
        
        $users = $query->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $users,
            'stats' => [
                'total_users' => User::count(),
                'agents' => User::where('user_type', 'agent')->count(),
                'owners' => User::where('user_type', 'owner')->count(),
            ]
        ]);
    }

    /**
     * Create a new owner (admin only).
     */
    public function createOwner(Request $request)
    {
        if (! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $matricule = 'OWN-' . now()->format('Y') . '-' . Str::upper(Str::random(6));

        $owner = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'user_type' => 'owner',
            'status' => 'validated',
            'matricule' => $matricule,
        ]);

        // Create default owner profile record
        $owner->ownerProfile()->create([
            'is_active' => true,
            'validation_status' => 'validated',
        ]);

        // Optionally send a welcome email (needs a mailable set up)
        // Mail::to($owner->email)->send(new \App\Mail\OwnerCreated($owner));

        return response()->json([
            'success' => true,
            'message' => 'Owner created',
            'data' => $owner->load('ownerProfile'),
        ], 201);
    }

    /**
     * List all owners (admin only).
     */
    public function listOwners(Request $request)
    {
        if (! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $owners = User::with('ownerProfile')
            ->where('user_type', 'owner')
            ->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $owners]);
    }

    /**
     * Update user status (validate/reject/deactivate)
     */
    public function updateUserStatus(Request $request, $id)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:validated,rejected,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);
        $oldStatus = $user->status;
        $user->update([
            'status' => $validator->validated()['status'],
            'validation_notes' => $validator->validated()['notes'],
            'validated_at' => now(),
        ]);

        // Notify user
        Notification::create([
            'user_id' => $user->id,
            'type' => 'account_status_changed',
            'title' => 'Status mis à jour',
            'message' => "Votre compte a été {$user->status}",
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated',
            'data' => $user->fresh(),
            'change' => ['from' => $oldStatus, 'to' => $user->status]
        ]);
    }

    public function validateAgent(Request $request, $id)
    {
        if (! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $agent = User::where('id', $id)->where('user_type', 'agent')->firstOrFail();
        $agent->update([
            'status' => 'validated',
            'validation_notes' => $request->input('notes'),
            'validated_at' => now(),
        ]);

        Notification::create([
            'user_id' => $agent->id,
            'type' => 'account_status_changed',
            'title' => 'Compte validé',
            'message' => 'Votre compte agent a été validé',
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'data' => $agent]);
    }

    public function rejectAgent(Request $request, $id)
    {
        if (! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $agent = User::where('id', $id)->where('user_type', 'agent')->firstOrFail();
        $agent->update([
            'status' => 'rejected',
            'validation_notes' => $request->input('notes'),
            'validated_at' => now(),
        ]);

        Notification::create([
            'user_id' => $agent->id,
            'type' => 'account_status_changed',
            'title' => 'Compte rejeté',
            'message' => 'Votre compte agent a été rejeté',
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'data' => $agent]);
    }

    public function validateOwner(Request $request, $id)
    {
        if (! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $owner = User::where('id', $id)->where('user_type', 'owner')->firstOrFail();
        $owner->update([
            'status' => 'validated',
            'validation_notes' => $request->input('notes'),
            'validated_at' => now(),
        ]);

        Notification::create([
            'user_id' => $owner->id,
            'type' => 'account_status_changed',
            'title' => 'Compte validé',
            'message' => 'Votre compte propriétaire a été validé',
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'data' => $owner]);
    }

    public function rejectOwner(Request $request, $id)
    {
        if (! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $owner = User::where('id', $id)->where('user_type', 'owner')->firstOrFail();
        $owner->update([
            'status' => 'rejected',
            'validation_notes' => $request->input('notes'),
            'validated_at' => now(),
        ]);

        Notification::create([
            'user_id' => $owner->id,
            'type' => 'account_status_changed',
            'title' => 'Compte rejeté',
            'message' => 'Votre compte propriétaire a été rejeté',
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'data' => $owner]);
    }

    // Enhanced createAgent (already exists, minor enhancements if needed)
    // ... existing createAgent method ...

    /**
     * List agents with commissions stats
     */
    public function getAgents(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $agents = User::with(['agentProfile', 'properties', 'commissions'])
            ->where('user_type', 'agent')
            ->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $agents]);
    }

    // Include all existing methods here for complete file...
    // (Full code would paste all existing + new)
}
