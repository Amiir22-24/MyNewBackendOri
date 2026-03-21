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
        $target = User::with(['agentProfile', 'ownerProfile', 'properties'])->findOrFail($id);

        if (!$user->is_admin && $target->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
                'error_type' => 'forbidden'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $target
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

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'status' => 'sometimes|in:pending,validated,rejected|required_if_admin',
        ]);

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

    // destroy method for admin soft-delete, etc.
    public function destroy(Request $request, $id)
    {
        // similar pattern...
    }
}
