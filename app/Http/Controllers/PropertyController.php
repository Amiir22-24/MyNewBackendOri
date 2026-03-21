<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Property;
use App\Models\User;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties.
     */
    public function index(Request $request)
    {
        return response()->json(['message' => 'TODO: List all properties']);
    }

    /**
     * Search properties.
     */
    public function search(Request $request)
    {
        return response()->json(['message' => 'TODO: Search properties']);
    }

    /**
     * Featured properties.
     */
    public function featured(Request $request)
    {
        return response()->json(['message' => 'TODO: Featured properties']);
    }

    /**
     * Display specific property.
     */
    public function show(Property $id)
    {
        return response()->json(['message' => 'TODO: Show property ' . $id->id]);
    }

    /**
     * Store new property.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'catalog_type' => 'required|string',
            'property_type' => 'required|string',
            'operation_type' => 'required|string',
            'price' => 'required|numeric',
            'currency' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photos' => 'required|array|min:1',
            'photos.*.photo_url' => 'required|string',
            'owner_matricule' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $owner = $user;
        $ownerId = $user->id;
        $ownerMatricule = $user->matricule;

        if (!empty($validated['owner_matricule'])) {
            if (! $user->is_admin && ! $user->is_agent) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $owner = User::where('matricule', $validated['owner_matricule'])->firstOrFail();
            $ownerId = $owner->id;
            $ownerMatricule = $owner->matricule;
        }

        $payload = $validated;
        $payload['owner_id'] = $ownerId;
        $payload['owner_matricule'] = $ownerMatricule;
        $payload['owner_name'] = $owner->full_name;
        $payload['owner_phone'] = $owner->phone;
        $payload['agent_id'] = $user->is_agent ? $user->id : null;
        $payload['agent_name'] = $user->is_agent ? $user->full_name : null;

        $property = Property::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Property created',
            'data' => $property,
        ], 201);
    }

    /**
     * Update property.
     */
    public function update(Request $request, Property $id)
    {
        return response()->json(['message' => 'TODO: Update property ' . $id->id]);
    }

    /**
     * Delete property.
     */
    public function destroy(Property $id)
    {
        return response()->json(['message' => 'TODO: Delete property ' . $id->id]);
    }

    /**
     * Occupy property.
     */
    public function occupy(Request $request, Property $id)
    {
        return response()->json(['message' => 'TODO: Occupy property ' . $id->id]);
    }

    /**
     * Create inquiry for property.
     */
    public function createInquiry(Request $request, Property $id)
    {
        return response()->json(['message' => 'TODO: Create inquiry for property ' . $id->id]);
    }
}

