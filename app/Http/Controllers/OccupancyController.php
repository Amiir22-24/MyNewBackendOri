<?php

namespace App\Http\Controllers;

use App\Models\OccupancyRequest;
use App\Models\OccupancyContract;
use App\Models\Property;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OccupancyController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/OccupancyController --api
     * Create occupancy request (client -> property owner/agent)
     */
    public function storeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $property = Property::findOrFail($validator->validated()['property_id']);
        if (!$property->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Propriété non disponible',
                'error_type' => 'property_unavailable'
            ], 400);
        }

        $requestData = $validator->validated();
        $requestData['client_id'] = $this->user()->id;
        $requestData['status'] = 'pending';

        $occupancyRequest = OccupancyRequest::create($requestData);

        // Notify owner/agent
        Notification::create([
            'user_id' => $property->owner_id,
            'type' => 'occupancy_request',
            'title' => 'Nouvelle demande d\'occupation',
            'data' => ['request_id' => $occupancyRequest->id]
        ]);

        if ($property->agent_id) {
            Notification::create([
                'user_id' => $property->agent_id,
                'type' => 'occupancy_request_agent',
                'title' => 'Nouvelle demande d\'occupation',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande créée',
            'data' => $occupancyRequest->load('property')
        ], 201);
    }

    /**
     * List my requests/contracts
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $requests = OccupancyRequest::with(['property', 'client'])
            ->where('client_id', $user->id)
            ->orWhereHas('property', fn($q) => $q->where('owner_id', $user->id))
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Approve request -> create contract
     */
    public function approveRequest(Request $request, $requestId)
    {
        $user = $request->user();
        $occupancyRequest = OccupancyRequest::with('property')->findOrFail($requestId);

        if (!$user->is_admin && $occupancyRequest->property->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $contract = OccupancyContract::create([
            'occupancy_request_id' => $requestId,
            'property_id' => $occupancyRequest->property_id,
            'tenant_id' => $occupancyRequest->client_id,
            'owner_id' => $occupancyRequest->property->owner_id,
            'agent_id' => $occupancyRequest->property->agent_id,
            'start_date' => $occupancyRequest->start_date,
            'end_date' => $occupancyRequest->end_date,
            'status' => 'active',
            'monthly_rent' => $occupancyRequest->property->price,
        ]);

        $occupancyRequest->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'data' => $contract
        ]);
    }

    /**
     * List occupancy contracts for current user (tenant/owner/agent/admin).
     */
    public function contracts(Request $request)
    {
        $user = $request->user();

        $query = OccupancyContract::with(['property', 'tenant', 'owner', 'agent']);

        if ($user->is_admin) {
            // Admin sees all
        } elseif ($user->is_agent) {
            $query->where('agent_id', $user->id);
        } elseif ($user->is_owner) {
            $query->where('owner_id', $user->id);
        } else {
            $query->where('tenant_id', $user->id);
        }

        $contracts = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    public function contractShow(Request $request, $id)
    {
        $user = $request->user();

        $contract = OccupancyContract::with(['property', 'tenant', 'owner', 'agent'])->findOrFail($id);

        if (! $user->is_admin && $contract->tenant_id !== $user->id && $contract->owner_id !== $user->id && $contract->agent_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json(['success' => true, 'data' => $contract]);
    }

    public function signContract(Request $request, $id)
    {
        $user = $request->user();
        $contract = OccupancyContract::findOrFail($id);

        if (! $user->is_admin && $contract->tenant_id !== $user->id && $contract->owner_id !== $user->id && $contract->agent_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        // Placeholder: implement actual signature workflow.
        return response()->json([
            'success' => true,
            'message' => 'Signature reçue (non implémentée)',
            'data' => $contract,
        ]);
    }

    public function downloadContract(Request $request, $id)
    {
        $user = $request->user();
        $contract = OccupancyContract::findOrFail($id);

        if (! $user->is_admin && $contract->tenant_id !== $user->id && $contract->owner_id !== $user->id && $contract->agent_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if (! $contract->contract_url) {
            return response()->json(['success' => false, 'message' => 'Aucun contrat disponible'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => ['contract_url' => $contract->contract_url],
        ]);
    }
}
