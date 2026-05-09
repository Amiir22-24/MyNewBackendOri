<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/UserController --api
     * List users (admin only, role filtered)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Admin seulement.',
                'error_type' => 'unauthorized'
            ], 403);
        }

        $query = User::with(['agentProfile', 'ownerProfile', 'properties'])->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->type) {
            $query->where('user_type', $request->type);
        }

        $users = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        ]);
    }

    /**
     * Show user details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $target = User::with(['agentProfile', 'ownerProfile'])->findOrFail($id);

        if ($user->is_admin || (int) $target->id === (int) $user->id) {
            $target->loadCount('properties');

            return response()->json([
                'success' => true,
                'data' => $target,
            ]);
        }

        if (! in_array($target->user_type, ['owner', 'agent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
                'error_type' => 'forbidden',
            ], 403);
        }

        $payload = $target->only([
            'id', 'first_name', 'last_name', 'avatar', 'city', 'region', 'address',
            'phone', 'user_type', 'matricule',
        ]);

        if ($target->user_type === 'owner') {
            $payload['owner_profile'] = $target->ownerProfile;
        }
        if ($target->user_type === 'agent') {
            $payload['agent_profile'] = $target->agentProfile;
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
            'visibility' => 'public',
        ]);
    }

    /**
     * Update user (self or admin)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $target = User::findOrFail($id);

        if (!$user->is_admin && $target->id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $rules = [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ];
        if ($user->is_admin) {
            $rules['status'] = 'sometimes|in:validated,inactive';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur validation',
                'errors' => $validator->errors(),
                'error_type' => 'validation_error'
            ], 422);
        }

        $target->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour',
            'data' => $target->fresh()
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Admin seulement.',
            ], 403);
        }

        $target = User::findOrFail($id);
        if ((int) $target->id === (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer votre propre compte.',
            ], 403);
        }

        $target->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé',
        ]);
    }
}
