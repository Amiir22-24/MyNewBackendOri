<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class OwnerManagementController extends Controller
{
    /**
     * Check if owner exists
     */
    public function checkOwner(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $matricule = $request->query('matricule');
        if (!$matricule) {
            return response()->json([
                'success' => false,
                'message' => 'Matricule requis',
            ], 400);
        }

        $owner = User::where('user_type', 'owner')
            ->where('matricule', $matricule)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $owner !== null,
                'owner' => $owner ? $owner->only(['id', 'name', 'matricule', 'phone']) : null,
            ],
        ]);
    }

    /**
     * Register new owner
     */
    public function registerOwner(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|unique:users,email',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
        ]);

        // Generate matricule
        $matricule = 'OWN' . strtoupper(substr(md5(time() . $validated['email']), 0, 6));

        $owner = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'user_type' => 'owner',
            'matricule' => $matricule,
            'password' => bcrypt('password123'), // Temporary password
            'is_active' => true,
        ]);

        // Create owner profile
        $owner->ownerProfile()->create([
            'owner_type' => 'individual',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Propriétaire enregistré',
            'data' => [
                'owner' => $owner->only(['id', 'name', 'matricule', 'email', 'phone']),
                'matricule' => $matricule,
            ],
        ], 201);
    }

    /**
     * Get owners for selection
     */
    public function getForSelection(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $owners = User::where('user_type', 'owner')
            ->select(['id', 'name', 'matricule', 'phone', 'city'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $owners,
        ]);
    }

    /**
     * Get owner by matricule
     */
    public function getByMatricule(Request $request, $matricule)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $owner = User::where('user_type', 'owner')
            ->where('matricule', $matricule)
            ->with('ownerProfile')
            ->first();

        if (!$owner) {
            return response()->json([
                'success' => false,
                'message' => 'Propriétaire non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $owner,
        ]);
    }

    /**
     * Get agent owners
     */
    public function getOwners(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        // Get owners who have properties managed by this agent
        $owners = User::where('user_type', 'owner')
            ->whereHas('properties', function ($query) use ($user) {
                $query->where('agent_id', $user->id);
            })
            ->with(['properties' => function ($query) use ($user) {
                $query->where('agent_id', $user->id);
            }])
            ->with('ownerProfile')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $owners,
        ]);
    }
}