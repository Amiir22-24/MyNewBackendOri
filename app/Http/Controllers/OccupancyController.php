<?php

namespace App\Http\Controllers;

use App\Models\OccupancyRequest;
use App\Models\OccupancyContract;
use App\Models\Property;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class OccupancyController extends Controller
{
    /**
     * Create occupancy request (client -> property owner/agent)
     * MODIFICATION: start_date, end_date et message sont désormais optionnels.
     * Le contrat est uploadé directement et agent_id/owner_id sont auto-détectés.
     */
    public function storeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id'   => 'required|exists:properties,id',
            'message'       => 'nullable|string|max:1000',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after:start_date',
            'contract_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $property = Property::findOrFail($request->property_id);
        if (!$property->is_available) {
            return response()->json([
                'success'    => false,
                'message'    => 'Propriété non disponible',
                'error_type' => 'property_unavailable'
            ], 400);
        }

        // Upload du fichier contrat si présent
        $contractUrl = null;
        if ($request->hasFile('contract_file')) {
            $file = $request->file('contract_file');
            $path = $file->store('contracts', 'public');
            $contractUrl = Storage::url($path);
        }

        $occupancyRequest = OccupancyRequest::create([
            'property_id'     => $request->property_id,
            'client_id'       => $request->user()->id,
            'agent_id'        => $property->agent_id,
            'owner_id'        => $property->owner_id,
            'status'          => $property->agent_id ? 'pending_agent' : 'pending_owner',
            'message'         => $request->message ?? '', // Évite l'erreur si message est nul
            'rent_amount'     => $property->price,
            'proposed_amount' => $property->price, // Requis par la DB
            'start_date'      => $request->start_date ?? now()->addDay(),
            'end_date'        => $request->end_date ?? now()->addYear(),
            'contract_url'    => $contractUrl,
        ]);

        // Notifier l'agent si la propriété en a un
        if ($property->agent_id) {
            Notification::create([
                'user_id' => $property->agent_id,
                'type'    => 'occupancy_request_agent',
                'title'   => 'Nouvelle demande d\'occupation',
                'message' => "Le client a soumis une demande pour la propriété: {$property->title}",
                'data'    => ['request_id' => $occupancyRequest->id, 'property_id' => $property->id],
                'is_read' => false,
            ]);
        }

        // Notifier le propriétaire
        Notification::create([
            'user_id' => $property->owner_id,
            'type'    => 'occupancy_request',
            'title'   => 'Nouvelle demande d\'occupation',
            'message' => "Un client a demandé à occuper votre propriété: {$property->title}",
            'data'    => ['request_id' => $occupancyRequest->id],
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande créée',
            'data'    => $occupancyRequest->load(['property', 'client'])
        ], 201);
    }

    /**
     * List occupancy requests with filtering (générique)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = OccupancyRequest::with(['property', 'client']);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        } elseif ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        } elseif ($request->has('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        } else {
            if (!$user->is_admin) {
                $query->where(function ($q) use ($user) {
                    $q->where('client_id', $user->id)
                      ->orWhere('agent_id', $user->id)
                      ->orWhere('owner_id', $user->id);
                });
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 20))
        ]);
    }

    /**
     * Client's own requests (alias with client_id forced)
     */
    public function getClientRequests(Request $request)
    {
        $user = $request->user();

        $requests = OccupancyRequest::with('property')
            ->where('client_id', $user->id)
            ->latest()
            ->paginate((int) $request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /**
     * NOUVEAU: Demandes en attente pour un agent (statut = pending_agent)
     */
    public function agentPendingIndex(Request $request)
    {
        $user    = $request->user();
        $agentId = $request->get('agent_id', $user->id);

        $requests = OccupancyRequest::with(['property', 'client'])
            ->where('agent_id', $agentId)
            ->where('status', 'pending_agent')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $requests,
            'count'   => $requests->total(),
        ]);
    }

    /**
     * NOUVEAU: Demandes en attente pour un propriétaire (statut = pending_owner)
     */
    public function ownerPendingIndex(Request $request)
    {
        $user    = $request->user();
        $ownerId = $request->get('owner_id', $user->id);

        $requests = OccupancyRequest::with(['property', 'client'])
            ->where('owner_id', $ownerId)
            ->where('status', 'pending_owner')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $requests,
            'count'   => $requests->total(),
        ]);
    }

    /**
     * Agent's pending requests (alias for backward compat)
     */
    public function getAgentPendingRequests(Request $request)
    {
        return $this->agentPendingIndex($request);
    }

    /**
     * Show single request
     */
    public function show($id)
    {
        $occRequest = OccupancyRequest::with(['property', 'client'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $occRequest]);
    }

    /**
     * Agent approuve → passe en pending_owner
     */
    public function agentApprove(Request $request, $id)
    {
        $occRequest = OccupancyRequest::findOrFail($id);

        $occRequest->update([
            'status'             => 'pending_owner',
            'agent_notes'        => $request->notes,
            'agent_validated_at' => now(),
        ]);

        // Notifier le propriétaire
        Notification::create([
            'user_id' => $occRequest->owner_id,
            'type'    => 'occupancy_request_owner',
            'title'   => 'Demande d\'occupation validée par l\'agent',
            'message' => 'L\'agent a approuvé une demande d\'occupation. Votre validation est requise.',
            'data'    => ['request_id' => $occRequest->id],
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Demande validée par l\'agent']);
    }

    /**
     * Agent refuse
     */
    public function agentReject(Request $request, $id)
    {
        $occRequest = OccupancyRequest::findOrFail($id);
        $occRequest->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
            'rejected_by'      => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Demande rejetée par l\'agent']);
    }

    /**
     * Owner approuve → crée le contrat
     */
    public function ownerApprove(Request $request, $id)
    {
        return $this->approveRequest($request, $id);
    }

    /**
     * Owner refuse
     */
    public function ownerReject(Request $request, $id)
    {
        $occRequest = OccupancyRequest::findOrFail($id);
        $user = $request->user();
        if ($occRequest->owner_id !== $user->id && !$user->is_admin) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }
        $occRequest->update([
            'status' => 'rejected',
            'owner_rejection_reason' => $request->reason,
            'owner_reviewed_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Demande rejetée par le propriétaire']);
    }

    /**
     * Client annule sa demande
     */
    public function cancel(Request $request, $id)
    {
        $occRequest = OccupancyRequest::findOrFail($id);
        if ($occRequest->client_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $occRequest->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Demande annulée']);
    }

    /**
     * Approbation finale → crée contrat + marque propriété comme occupée
     */
    public function approveRequest(Request $request, $requestId)
    {
        $user             = $request->user();
        $occupancyRequest = OccupancyRequest::with('property')->findOrFail($requestId);

        if (!$user->is_admin && $occupancyRequest->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $contract = OccupancyContract::create([
            'occupancy_request_id' => $requestId,
            'property_id'          => $occupancyRequest->property_id,
            'tenant_id'            => $occupancyRequest->client_id,
            'owner_id'             => $occupancyRequest->owner_id,
            'agent_id'             => $occupancyRequest->agent_id,
            'start_date'           => $occupancyRequest->start_date ?? now(),
            'end_date'             => $occupancyRequest->end_date ?? now()->addYear(),
            'is_active'            => true,
            'monthly_rent'         => $occupancyRequest->property->price,
            'deposit_amount'       => 0,
            'signed_at'            => now(), // Requis par la DB
        ]);

        $occupancyRequest->update(['status' => 'approved']);

        // Marquer la propriété comme occupée
        $occupancyRequest->property->update([
            'is_available' => false,
            'is_occupied'  => true,
            'status'       => 'occupied',
        ]);

        // Notifier le client
        Notification::create([
            'user_id' => $occupancyRequest->client_id,
            'type'    => 'occupancy_approved',
            'title'   => 'Demande d\'occupation approuvée !',
            'message' => 'Votre demande a été approuvée. Votre contrat est disponible.',
            'data'    => ['contract_id' => $contract->id],
            'is_read' => false,
        ]);

        // Notifier l'agent s'il y en a un
        if ($occupancyRequest->agent_id) {
            Notification::create([
                'user_id' => $occupancyRequest->agent_id,
                'type'    => 'property_occupied_agent',
                'title'   => 'Contrat finalisé !',
                'message' => 'Une de vos propriétés a été validée et le contrat a été créé.',
                'data'    => ['contract_id' => $contract->id, 'property_id' => $occupancyRequest->property_id],
                'is_read' => false,
            ]);
        }

        // Retourner avec status textuel pour les badges Flutter
        $contractData = $contract->load(['property', 'tenant', 'owner', 'agent'])->toArray();
        $contractData['status'] = 'active';

        return response()->json([
            'success' => true,
            'message' => 'Demande approuvée et contrat créé',
            'data'    => $contractData,
        ]);
    }

    /**
     * Reject request (alias générique)
     */
    public function rejectRequest(Request $request, $requestId)
    {
        return $this->agentReject($request, $requestId);
    }

    /**
     * Owner occupancy contracts
     * GET /owner/occupancy-contracts?page=1
     */
    public function ownerContracts(Request $request)
    {
        $user = $request->user();
        $query = OccupancyContract::with(['property:id,title', 'client:id,name'])
            ->where('owner_id', $user->id);

        $contracts = $query->select('id', 'property_id', 'client_id', 'amount', 'status', 'contract_url', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $contracts
        ]);
    }

    /**
     * Contract PDF URL
     * GET /occupancy-contracts/{id}/pdf
     */
    public function contractPdf($id)
    {
        $user = request()->user();
        $contract = OccupancyContract::findOrFail($id);

        if (!$user->is_admin && $contract->tenant_id !== $user->id && $contract->owner_id !== $user->id && $contract->agent_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if (!$contract->contract_url) {
            return response()->json(['success' => false, 'message' => 'Aucun PDF disponible'], 404);
        }

        return response()->json([
            'success' => true, 
            'data' => ['pdf_url' => $contract->contract_url]
        ]);
    }

    // ==================== CONTRACTS ====================

    public function contracts(Request $request)
    {
        $user  = $request->user();
        $query = OccupancyContract::with(['property', 'tenant', 'owner', 'agent']);

        if ($user->is_admin) {
            // tous
        } elseif ($user->is_agent) {
            $query->where('agent_id', $user->id);
        } elseif ($user->is_owner) {
            $query->where('owner_id', $user->id);
        } else {
            $query->where('tenant_id', $user->id);
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    public function contractShow(Request $request, $id)
    {
        $user     = $request->user();
        $contract = OccupancyContract::with(['property', 'tenant', 'owner', 'agent'])->findOrFail($id);

        if (!$user->is_admin && $contract->tenant_id !== $user->id && $contract->owner_id !== $user->id && $contract->agent_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json(['success' => true, 'data' => $contract]);
    }

    public function signContract(Request $request, $id)
    {
        $user     = $request->user();
        $contract = OccupancyContract::findOrFail($id);

        if (!$user->is_admin && $contract->tenant_id !== $user->id && $contract->owner_id !== $user->id && $contract->agent_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Signature enregistrée.',
            'data'    => $contract,
        ]);
    }

    public function downloadContract(Request $request, $id)
    {
        $user     = $request->user();
        $contract = OccupancyContract::findOrFail($id);

        if (!$user->is_admin && $contract->tenant_id !== $user->id && $contract->owner_id !== $user->id && $contract->agent_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if (!$contract->contract_url) {
            return response()->json(['success' => false, 'message' => 'Aucun contrat disponible'], 404);
        }

        return response()->json(['success' => true, 'data' => ['contract_url' => $contract->contract_url]]);
    }
}
