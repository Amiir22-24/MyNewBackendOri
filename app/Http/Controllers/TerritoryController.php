<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TerritoryController extends Controller
{
    /**
     * Get agent territories
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        // TODO: Implement when Territory model exists
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Fonctionnalité territoires à implémenter',
        ]);
    }

    /**
     * Create territory
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        // TODO: Implement when Territory model exists
        return response()->json([
            'success' => false,
            'message' => 'Fonctionnalité territoires à implémenter',
        ], 501);
    }

    /**
     * Update territory
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        // TODO: Implement when Territory model exists
        return response()->json([
            'success' => false,
            'message' => 'Fonctionnalité territoires à implémenter',
        ], 501);
    }

    /**
     * Delete territory
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        // TODO: Implement when Territory model exists
        return response()->json([
            'success' => false,
            'message' => 'Fonctionnalité territoires à implémenter',
        ], 501);
    }
}