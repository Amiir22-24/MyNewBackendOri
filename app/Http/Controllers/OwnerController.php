<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Models\OccupancyContract;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    /**
     * Dashboard stats for owner
     * GET /owner/dashboard/stats
     */
    public function dashboardStats(Request $request)
    {
        $dashboardController = new DashboardController();
        return $dashboardController->stats($request);
    }

    /**
     * Owner contracts
     * GET /owner/contracts?page=1&status=active
     * Fields: id, contract_number (use id), property_id, client_id, amount (monthly_rent), status, start_date, created_at
     */
    public function contracts(Request $request)
    {
        $user = $request->user();
        if ($user->user_type !== 'owner') {
            return response()->json(['success' => false, 'message' => 'Owner only'], 403);
        }

        $query = OccupancyContract::with(['property:id,title', 'tenant:id,name'])
            ->where('owner_id', $user->id);

        if ($request->status) {
            $query->where('status', $request->status); // if status field exists, else is_active
        }

        $contracts = $query->select('id', 'property_id', 'tenant_id', 'monthly_rent as amount', 'status', 'start_date', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $contracts
        ]);
    }
}

