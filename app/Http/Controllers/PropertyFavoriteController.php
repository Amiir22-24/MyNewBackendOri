<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyFavorite;
use App\Models\Notification;
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

        $favorite = PropertyFavorite::firstOrCreate([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        if ($favorite->wasRecentlyCreated) {
            // Notification au client
            Notification::create([
                'user_id' => $user->id,
                'type' => 'favorite_added',
                'title' => 'Favori ajouté',
                'message' => 'La propriété ' . $property->title . ' a été ajoutée à vos favoris.',
                'data' => ['property_id' => $property->id],
                'is_read' => false,
            ]);

            // Notification à l'agent
            if ($property->agent_id) {
                Notification::create([
                    'user_id' => $property->agent_id,
                    'type' => 'property_favorited_agent',
                    'title' => 'Nouvel intérêt',
                    'message' => 'Un client a ajouté votre propriété ' . $property->title . ' à ses favoris.',
                    'data' => ['property_id' => $property->id, 'client_id' => $user->id],
                    'is_read' => false,
                ]);
            }

            // Notification au propriétaire
            if ($property->owner_id) {
                Notification::create([
                    'user_id' => $property->owner_id,
                    'type' => 'property_favorited_owner',
                    'title' => 'Nouvel intérêt',
                    'message' => 'Un client a ajouté votre propriété ' . $property->title . ' à ses favoris.',
                    'data' => ['property_id' => $property->id],
                    'is_read' => false,
                ]);
            }
        }

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
