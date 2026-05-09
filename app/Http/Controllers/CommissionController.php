<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    /**
     * Get agent commissions
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

        $commissions = Commission::with(['transaction.property'])
            ->where('agent_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $commissions,
        ]);
    }

    /**
     * Get commission summary for agent
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $summary = [
            'total_earned' => Commission::where('agent_id', $user->id)->whereNull('deleted_at')->sum('amount'),
            'pending_amount' => Commission::where('agent_id', $user->id)->where('status', 'pending')->whereNull('deleted_at')->sum('amount'),
            'paid_amount' => Commission::where('agent_id', $user->id)->where('status', 'paid')->whereNull('deleted_at')->sum('amount'),
            'total_commissions' => Commission::where('agent_id', $user->id)->whereNull('deleted_at')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}