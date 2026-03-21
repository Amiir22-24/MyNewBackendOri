<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AgentProfile;
use App\Models\OwnerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/ProfileController --api
     * Update agent profile (agent or admin)
     */
    public function updateAgent(Request $request)
    {
        $user = $request->user();
        if (!$user->is_admin && !$user->is_agent) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement ou admin',
                'error_type' => 'unauthorized'
            ], 403);
        }

        $targetUserId = $request->user_id ?? $user->id;
        $target = User::where('id', $targetUserId)->where('user_type', 'agent')->firstOrFail();

        $validator = Validator::make($request->all(), [
            'commission_rate' => 'required|numeric|min:0|max:50',
            'phone' => 'sometimes|string|max:20',
            'validation_status' => 'sometimes|in:pending,validated,rejected', // admin only
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $target->agentProfile()->updateOrCreate(
            ['user_id' => $target->id],
            $validator->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil agent mis à jour',
            'data' => $profile->load('user')
        ]);
    }

    /**
     * Update owner profile
     */
    public function updateOwner(Request $request)
    {
        $user = $request->user();
        if (!$user->is_admin && !$user->is_owner) {
            return response()->json([
                'success' => false,
                'message' => 'Accès propriétaire seulement ou admin',
                'error_type' => 'unauthorized'
            ], 403);
        }

        $targetUserId = $request->user_id ?? $user->id;
        $target = User::where('id', $targetUserId)->where('user_type', 'owner')->firstOrFail();

        $validator = Validator::make($request->all(), [
            'owner_type' => 'required|string|max:100',
            'company_name' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $target->ownerProfile()->updateOrCreate(
            ['user_id' => $target->id],
            $validator->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil propriétaire mis à jour',
            'data' => $profile->load('user')
        ]);
    }
}
