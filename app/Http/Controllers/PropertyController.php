<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use App\Models\Notification;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Services\PropertyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    protected PropertyService $propertyService;

    public function __construct(PropertyService $propertyService)
    {
        $this->propertyService = $propertyService;
    }

    /**
     * List published properties with filters
     */
    public function index(Request $request)
    {
        $filters = $request->only(['city', 'operation_type', 'catalog_type', 'property_type', 'sort']);
        $perPage = (int) $request->get('per_page', 15);

        $properties = $this->propertyService->getPublishedProperties($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => PropertyResource::collection($properties),
            'pagination' => [
                'total' => $properties->total(),
                'count' => $properties->count(),
                'per_page' => $properties->perPage(),
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
            ],
        ]);
    }

    /**
     * Search properties
     */
    public function search(Request $request)
    {
        $criteria = $request->only(['q', 'city', 'min_price', 'max_price', 'operation_type', 'catalog_type', 'property_type']);
        $perPage = (int) $request->get('per_page', 15);

        $properties = $this->propertyService->searchProperties($criteria, $perPage);

        return response()->json([
            'success' => true,
            'data' => PropertyResource::collection($properties),
            'pagination' => [
                'total' => $properties->total(),
                'count' => $properties->count(),
                'per_page' => $properties->perPage(),
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
            ],
        ]);
    }

    /**
     * Get featured properties
     */
    public function featured(Request $request)
    {
        $limit = (int) $request->get('limit', 12);
        $properties = $this->propertyService->getFeaturedProperties($limit);

        return response()->json([
            'success' => true,
            'data' => PropertyResource::collection($properties),
        ]);
    }

    /**
     * Get property details
     */
    public function show(Property $id)
    {
        $property = $id->load(['owner', 'agent']);

        return response()->json([
            'success' => true,
            'data' => PropertyResource::make($property),
        ]);
    }

    public function store(Request $request)
    {
        // LOGGING EN HAUT DE LA MÉTHODE (AVANT VALIDATION)
        \Illuminate\Support\Facades\Log::info('Requête PropertyController@store reçue', [
            'raw_input' => $request->all(),
            'has_files' => count($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
        ]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'catalog_type' => 'required|in:residential,commercial,project', 
            'property_type' => 'required|in:apartment,appartment,house,villa,studio,bureau,land,commercial,local_commercial,garage,duplex',
            'operation_type' => 'required|in:rent,sale,lease,reservation',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'condition' => 'nullable|string|in:new,good,average,to_renovate',
            'address' => 'required|string',
            'city' => 'required|string',
            'region' => 'nullable|string',
            'neighborhood' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
            'surface_area' => 'sometimes|numeric|min:0',
            'amenities' => 'sometimes',
            'photos' => 'sometimes',
            'images' => 'sometimes',
            'owner_matricule' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
                'error_type' => 'validation_error',
            ], 422);
        }

        $validated = $validator->validated();
        
        // CORRECTION ORTHOGRAPHE "appartment" => "apartment"
        if (isset($validated['property_type']) && $validated['property_type'] === 'appartment') {
            $validated['property_type'] = 'apartment';
        }

        $user = $request->user();
        $owner = $user;

        if (!empty($validated['owner_matricule'])) {
            $owner = User::where('matricule', $validated['owner_matricule'])->first();
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Matricule propriétaire non trouvé',
                    'error_type' => 'owner_not_found',
                ], 422);
            }
            if (!$user->is_admin && $user->user_type !== 'agent') {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé à utiliser ce matricule propriétaire',
                    'error_type' => 'unauthorized_owner',
                ], 403);
            }
        }

        $photoPaths = [];
        
        // Combiner tous les fichiers (photos[] et images[])
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

        // Fusion avec d'éventuels liens string existants (si déjà uploadés ou cloud)
        $stringPaths = array_merge(
            \Illuminate\Support\Arr::wrap($request->get('photos')),
            \Illuminate\Support\Arr::wrap($request->get('images'))
        );

        foreach ($stringPaths as $p) {
            if (is_string($p) && !empty($p)) {
                $photoPaths[] = ['photo_url' => $p, 'is_main' => false];
            }
        }

        // Création avec status = 'validated' (VALIDATION AUTOMATIQUE)
        $property = Property::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'catalog_type' => $validated['catalog_type'], 
            'property_type' => $validated['property_type'],
            'operation_type' => $validated['operation_type'],
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'XOF',
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
            'amenities' => $validated['amenities'] ?? [],
            'photos' => $photoPaths,
            'owner_id' => $owner->id,
            'owner_name' => $owner->full_name,
            'owner_phone' => $owner->phone,
            'owner_matricule' => $owner->matricule,
            'agent_id' => $user->user_type === 'agent' ? $user->id : null,
            'agent_name' => $user->user_type === 'agent' ? $user->full_name : null,
            'status' => 'validated', 
            'was_auto_validated' => true,
            'star_rating' => $this->calculateStarRating($validated),
            'is_available' => true,
        ]);

        // Notifications aux Admins
        $admins = User::where('user_type', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_property_created',
                'title' => 'Nouvelle propriété créée',
                'message' => "{$user->full_name} a créé \"{$property->title}\"",
                'data' => [
                    'property_id' => $property->id,
                    'property_title' => $property->title,
                    'owner_name' => $owner->full_name,
                ],
                'action_route' => "/admin/properties/{$property->id}",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Propriété créée et validée automatiquement',
            'data' => $property->fresh()->load(['owner', 'agent']),
        ], 201);
    }

    private function calculateStarRating($data)
    {
        $property = new Property($data);
        return $property->calculateStarRating();
    }

    /**
     * Update property
     */
    public function update(UpdatePropertyRequest $request, Property $id)
    {
        $property = $this->propertyService->updateProperty($id, $request->validated());
        // Recalculer la qualité et la note après mise à jour
        $property->updateStarRating();

        // Re-valider automatiquement si elle était rejetée
        if ($property->status === 'rejected') {
            $property->autoValidate();
        }
        return response()->json([
            'success' => true,
            'message' => 'Bien mis à jour',
            'data' => PropertyResource::make($property->fresh()->load(['owner', 'agent'])),
        ]);
    }

    /**
     * Delete property
     */
    public function destroy(Request $request, Property $id)
    {
        $user = $request->user();

        if (!$this->canManageProperty($user, $id)) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $this->propertyService->deleteProperty($id);

        return response()->json([
            'success' => true,
            'message' => 'Bien supprimé',
        ]);
    }

    /**
     * Mark property as occupied
     */
    public function occupy(Request $request, Property $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Utilisez POST /api/occupancy/requests pour une demande d\'occupation.',
        ], 422);
    }

    /**
     * Create an inquiry for property
     */
    public function createInquiry(Request $request, Property $id)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $this->propertyService->createInquiry($id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé',
        ], 201);
    }

    /**
     * Check if user can manage property
     */
    protected function canManageProperty(User $user, Property $property): bool
    {
        if ($user->is_admin) {
            return true;
        }
        if ($user->is_agent && $property->agent_id === $user->id) {
            return true;
        }
        if ($user->is_owner && $property->owner_id === $user->id) {
            return true;
        }

        return false;
    }
}
