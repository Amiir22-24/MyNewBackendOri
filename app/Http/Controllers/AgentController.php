<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    /**
     * Get agent properties
     */
    public function properties(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $properties = Property::with(['owner', 'agent'])
            ->where('agent_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    /**
     * Create property for agent
     */
    public function storeProperty(Request $request)
    {
        $user = $request->user();

        // LOGGING EN HAUT DE LA MÉTHODE (AVANT VALIDATION)
        \Illuminate\Support\Facades\Log::info('Requête storeProperty reçue', [
            'raw_input' => $request->all(),
            'has_files' => count($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
        ]);

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'catalog_type' => 'required|string',
            'property_type' => 'required|string',
            'operation_type' => 'required|string',
            'price' => 'required|numeric',
            'currency' => 'required|string',
            'price_period' => 'sometimes|string',
            'condition' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'region' => 'nullable|string',
            'neighborhood' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'bedrooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'surface_area' => 'nullable|numeric',
            'floors' => 'nullable|integer',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'owner_id' => 'required|exists:users,id',
            'photos' => 'sometimes',
            'images' => 'sometimes',
            'amenities' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // CORRECTION ORTHOGRAPHE "appartment" => "apartment"
        if (isset($validated['property_type']) && $validated['property_type'] === 'appartment') {
            $validated['property_type'] = 'apartment';
        }

        $owner = User::findOrFail($validated['owner_id']);

        $photoPaths = [];
        
        // Combiner tous les fichiers potentiels (photos[] et images[])
        $allFiles = array_merge(
            \Illuminate\Support\Arr::wrap($request->file('photos')),
            \Illuminate\Support\Arr::wrap($request->file('images'))
        );

        foreach ($allFiles as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $path = $file->store('properties', 'public');
                $photoPaths[] = ['photo_url' => $path, 'is_main' => false];
            }
        }

        // Fusionner avec d'éventuels liens string existants (si déjà uploadés ou cloud)
        $stringPaths = array_merge(
            \Illuminate\Support\Arr::wrap($request->get('photos')),
            \Illuminate\Support\Arr::wrap($request->get('images'))
        );

        foreach ($stringPaths as $p) {
            if (is_string($p) && !empty($p)) {
                $photoPaths[] = ['photo_url' => $p, 'is_main' => false];
            }
        }

        $property = Property::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'catalog_type' => $validated['catalog_type'],
            'property_type' => $validated['property_type'],
            'operation_type' => $validated['operation_type'],
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'XOF',
            'price_period' => $validated['price_period'] ?? 'monthly',
            'condition' => $validated['condition'] ?? 'good',
            'address' => $validated['address'],
            'city' => $validated['city'],
            'region' => $validated['region'] ?? '',
            'neighborhood' => $validated['neighborhood'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'bedrooms' => $validated['bedrooms'] ?? 0,
            'bathrooms' => $validated['bathrooms'] ?? 0,
            'surface_area' => $validated['surface_area'] ?? 0,
            'floors' => $validated['floors'] ?? 0,
            'owner_id' => $owner->id,
            'owner_name' => $owner->full_name,
            'owner_phone' => $owner->phone,
            'owner_matricule' => $owner->matricule,
            'agent_id' => $user->id,
            'agent_name' => $user->full_name,
            'status' => 'validated',
            'was_auto_validated' => true,
            'is_available' => true,
            'star_rating' => $validated['star_rating'] ?? 0,
            'photos' => $photoPaths,
            'amenities' => $validated['amenities'] ?? [],
        ]);

        // Notifications aux Admins
        $admins = User::where('user_type', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_property_created',
                'title' => 'Nouvelle propriété créée (Agent)',
                'message' => "L'agent {$user->full_name} a créé \"{$property->title}\"",
                'data' => [
                    'property_id' => $property->id,
                    'property_title' => $property->title,
                    'agent_name' => $user->full_name,
                ],
                'action_route' => "/admin/properties/{$property->id}",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Propriété créée et validée automatiquement',
            'data' => $property->load(['owner', 'agent']),
        ], 201);
    }

    /**
     * Update agent property
     */
    public function updateProperty(Request $request, $id)
    {
        $user = $request->user();
        $property = Property::findOrFail($id);

        if (!$user->is_admin && $property->agent_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string',
            'bedrooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'surface_area' => 'nullable|numeric',
        ]);

        $property->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Propriété mise à jour',
            'data' => $property->load(['owner', 'agent']),
        ]);
    }

    /**
     * Delete agent property
     */
    public function destroyProperty(Request $request, $id)
    {
        $user = $request->user();
        $property = Property::findOrFail($id);

        if (!$user->is_admin && $property->agent_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $property->delete();

        return response()->json([
            'success' => true,
            'message' => 'Propriété supprimée',
        ]);
    }

    /**
     * Get agent performance stats
     */
    public function performance(Request $request)
    {
        $user = $request->user();

        if (!$user->is_agent && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        $performance = [
            'total_properties' => Property::where('agent_id', $user->id)->count(),
            'active_properties' => Property::where('agent_id', $user->id)->where('status', 'validated')->count(),
            'pending_properties' => Property::where('agent_id', $user->id)->where('status', 'pending_validation')->count(),
            'total_commissions' => \App\Models\Commission::where('agent_id', $user->id)->whereNull('deleted_at')->sum('amount'),
            'pending_requests' => \App\Models\OccupancyRequest::whereHas('property', fn($q) => $q->where('agent_id', $user->id))->where('status', 'pending')->count(),
            'approved_requests' => \App\Models\OccupancyRequest::whereHas('property', fn($q) => $q->where('agent_id', $user->id))->where('status', 'approved')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }
}