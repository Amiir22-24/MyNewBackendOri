<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Property;
use App\Models\OccupancyRequest;
use App\Models\OccupancyContract;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/DashboardController --api
     * Role-specific dashboard stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $role = $user->user_type;

        $stats = [];

        if ($role === 'admin') {
            $stats = [
                'total_properties' => Property::count(),
                'validated_properties' => Property::where('status', 'validated')->count(),
                'total_users' => User::count(),
                'total_revenue' => Transaction::sum('amount'),
                'pending_requests' => OccupancyRequest::where('status', 'pending')->count(),
            ];
        } elseif ($role === 'agent') {
            $stats = [
                'my_properties' => Property::where('agent_id', $user->id)->count(),
                'commissions_earned' => \App\Models\Commission::where('agent_id', $user->id)->sum('amount'),
                'pending_requests' => OccupancyRequest::whereHas('property', fn($q) => $q->where('agent_id', $user->id))->where('status', 'pending')->count(),
            ];
        } elseif ($role === 'owner') {
            $stats = [
                'my_properties' => Property::where('owner_id', $user->id)->count(),
                'active_contracts' => OccupancyContract::where('owner_id', $user->id)->where('status', 'active')->count(),
                'total_revenue' => Transaction::whereHas('property', fn($q) => $q->where('owner_id', $user->id))->sum('amount'),
            ];
        } else {
            // client
            $stats = [
                'saved_properties' => 0, // implement saved
                'my_requests' => OccupancyRequest::where('client_id', $user->id)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
