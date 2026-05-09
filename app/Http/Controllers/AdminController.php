<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Property;
use App\Models\OccupancyRequest;
use App\Models\Notification;
use App\Models\AgentProfile;
use App\Models\OwnerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{


    /**
     * List all users with role filtering
     * Artisan: php artisan make:controller Api/AdminController --api
     */
    public function getUsers(Request $request) {
        if (!$request->user()->is_admin) return response()->json(['success' => false], 403);

        $query = User::query()->orderBy('created_at', 'desc');
        if ($request->has('type')) $query->where('user_type', $request->type);
        if ($request->has('status')) $query->where('status', $request->status);

        return response()->json([
            'success' => true,
            'data' => ['users' => $query->paginate($request->get('per_page', 20))],
            'stats' => [
                'total' => User::count(),
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

        $year = now()->format('Y');
        $count = User::where('matricule', 'LIKE', "OWN-{$year}%")->count();
        $matricule = "OWN-{$year}-" . str_pad($count + 1, 6, '0', STR_PAD_LEFT);

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
     * Activer ou bloquer un compte utilisateur (consultation profil admin).
     */
    public function updateUserStatus(Request $request, $id)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:validated,inactive',
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
        $newStatus = $validator->validated()['status'];
        $user->update([
            'status' => $newStatus,
            'validation_notes' => $validator->validated()['notes'],
            'validated_at' => $newStatus === 'validated' ? now() : $user->validated_at,
        ]);

        $msg = $newStatus === 'inactive'
            ? 'Votre compte a été désactivé'
            : 'Votre compte a été réactivé';

        Notification::create([
            'user_id' => $user->id,
            'type' => 'account_status_changed',
            'title' => $newStatus === 'inactive' ? 'Compte désactivé' : 'Compte réactivé',
            'message' => $msg,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut utilisateur mis à jour',
            'data' => $user->fresh(),
            'change' => ['from' => $oldStatus, 'to' => $user->status]
        ]);
    }

    /**
     * Create a new agent (admin only).
     */
    public function createAgent(Request $request)
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

        $year = now()->format('Y');
        $count = User::where('matricule', 'LIKE', "AGT-{$year}%")->count();
        $matricule = "AGT-{$year}-" . str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        $agent = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'user_type' => 'agent',
            'status' => 'validated',
            'matricule' => $matricule,
        ]);

        // Create default agent profile
        $agent->agentProfile()->create([
            'validation_status' => 'validated',
            'is_active' => true,
        ]);

        // Send matricule email
        Mail::to($agent->email)->send(new \App\Mail\MatriculeMail($agent, $matricule));

        return response()->json([
            'success' => true,
            'message' => 'Agent created successfully',
            'data' => $agent->load('agentProfile'),
        ], 201);
    }

    /**
     * Get all agents (paginated)
     */
    public function getAgents(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $query = User::with(['agentProfile'])
                ->where('user_type', 'agent')
                ->orderBy('created_at', 'desc');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $agents = $query->paginate($request->get('per_page', 20));

            // Calcul sécurisé des statistiques pour l'interface Admin
            $totalAgents = User::where('user_type', 'agent')->count();
            $validatedAgents = User::where('user_type', 'agent')->where('status', 'validated')->count();
            $pendingAgents = User::where('user_type', 'agent')->where('status', 'pending')->count();

            return response()->json([
                'success' => true, 
                'data' => $agents,
                'stats' => [
                    'total_agents' => $totalAgents,
                    'validated_agents' => $validatedAgents,
                    'pending_agents' => $pendingAgents,
                ]
            ]);
        } catch (\Exception $e) {
            // Log de l'erreur pour le débogage serveur
            \Log::error('Erreur getAgents Admin: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des agents',
                'error' => $e->getMessage() // Retourne l'erreur exacte pour faciliter le diagnostic
            ], 500);
        }
    }

    /**
     * File d'attente admin : biens et demandes à traiter (hors validation des comptes).
     */
    public function getPendingValidations(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $perPage = $request->get('per_page', 20);

        // Propriétés en attente de validation manuelle (celles rejetées automatiquement)
        $pendingProperties = Property::where('status', 'pending')
            ->orWhere('status', 'rejected')
            ->with(['owner', 'agent'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $pendingProperties,
            'stats' => [
                'total_pending' => Property::where('status', 'pending')->count(),
                'total_rejected' => Property::where('status', 'rejected')->count(),
                'auto_validated_today' => Property::where('was_auto_validated', true)
                    ->whereDate('updated_at', today())->count(),
            ]
        ]);
    }

    public function dashboardStats(Request $request) {
        if (!$request->user()->is_admin) return response()->json(['success' => false], 403);
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::count(),
                'total_agents' => User::where('user_type', 'agent')->count(),
                'total_owners' => User::where('user_type', 'owner')->count(),
                'total_properties' => Property::count(),
                'total_validated_properties' => Property::where('status', 'validated')->count(),
            ]
        ]);
    }

    public function dashboard(Request $request)
    {
        if (! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'total_agents' => User::where('user_type', 'agent')->count(),
            'total_owners' => User::where('user_type', 'owner')->count(),
            'total_properties' => Property::count(),
            'total_validated_properties' => Property::where('status', 'validated')->count(),
            'total_pending_properties' => Property::where('status', 'pending')->count(),
            'total_rejected_properties' => Property::where('status', 'rejected')->count(),
            'total_auto_validated' => Property::where('was_auto_validated', true)->count(),
            'total_inactive_users' => User::where('status', 'inactive')->count(),
            'total_pending_requests' => OccupancyRequest::where('status', 'pending')->count(),
            'average_property_rating' => Property::avg('star_rating'),
            'properties_by_quality' => [
                'luxury' => Property::where('star_rating', 5)->count(),
                'premium' => Property::where('star_rating', 4)->count(),
                'comfortable' => Property::where('star_rating', 3)->count(),
                'standard' => Property::where('star_rating', 2)->count(),
                'basic' => Property::where('star_rating', 1)->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    public function getAllProperties(Request $request)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'data' => Property::with(['owner', 'agent'])->latest()->paginate($request->per_page ?? 20)]);
    }

    public function getPropertyDetail(Request $request, $id)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'data' => Property::with(['owner', 'agent'])->findOrFail($id)]);
    }

    public function rejectProperty(Request $request, $id)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        $property = Property::findOrFail($id);
        $property->update(['status' => 'rejected', 'validation_notes' => $request->notes]);
        return response()->json(['success' => true, 'data' => $property, 'message' => 'Property rejected']);
    }

    public function getNewProperties(Request $request)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'data' => Property::with(['owner', 'agent'])->where('status', 'pending')->orWhereNull('status')->latest()->paginate($request->per_page ?? 20)]);
    }

    public function getRejectedProperties(Request $request)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'data' => Property::with(['owner', 'agent'])->where('status', 'rejected')->latest()->paginate($request->per_page ?? 20)]);
    }

    public function getPropertyNotifications(Request $request)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'data' => Notification::where('type', 'property_related')->latest()->paginate($request->per_page ?? 20)]);
    }

    public function getWithdrawals(Request $request)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'data' => []]);
    }

    public function approveWithdrawal(Request $request, $id)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'message' => 'Approved']);
    }

    public function rejectWithdrawal(Request $request, $id)
    {
        if (!$request->user()->is_admin) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        return response()->json(['success' => true, 'message' => 'Rejected']);
    }
}

