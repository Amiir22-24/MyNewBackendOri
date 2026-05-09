<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyFavorite;
use Illuminate\Http\Request;

class PropertyFavoriteController extends Controller
{
    /**
     * Liste des biens favoris du client (avec relations pour les cartes UI).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = PropertyFavorite::query()
            ->where('user_id', $user->id)
            ->with(['property' => fn ($q) => $q->with(['owner', 'agent'])])
            ->latest()
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    public function store(Request $request, int $propertyId)
    {
        $user = $request->user();
        $property = Property::query()->findOrFail($propertyId);

        PropertyFavorite::firstOrCreate([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajouté aux favoris',
            'data' => ['property_id' => $property->id],
        ], 201);
    }

    public function destroy(Request $request, int $propertyId)
    {
        $user = $request->user();

        PropertyFavorite::where('user_id', $user->id)
            ->where('property_id', $propertyId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Retiré des favoris',
        ]);
    }
}
