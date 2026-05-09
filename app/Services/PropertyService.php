<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyService
{
    /**
     * Get all published properties with optional filters
     */
    public function getPublishedProperties(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Property::with(['owner', 'agent'])
                        ->where('status', 'validated');

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    /**
     * Search properties by various criteria
     */
    public function searchProperties(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Property::with(['owner', 'agent'])
                        ->where('status', 'validated');

        if (isset($criteria['q']) && $criteria['q']) {
            $q = $criteria['q'];
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%')
                    ->orWhere('city', 'like', '%'.$q.'%')
                    ->orWhere('address', 'like', '%'.$q.'%');
            });
        }

        if (isset($criteria['city']) && $criteria['city']) {
            $query->where('city', 'like', '%'.$criteria['city'].'%');
        }

        if (isset($criteria['min_price']) && $criteria['min_price']) {
            $query->where('price', '>=', $criteria['min_price']);
        }

        if (isset($criteria['max_price']) && $criteria['max_price']) {
            $query->where('price', '<=', $criteria['max_price']);
        }

        if (isset($criteria['operation_type']) && $criteria['operation_type']) {
            $query->where('operation_type', $criteria['operation_type']);
        }

        if (isset($criteria['catalog_type']) && $criteria['catalog_type']) {
            $query->where('catalog_type', $criteria['catalog_type']);
        }

        if (isset($criteria['property_type']) && $criteria['property_type']) {
            $type = $criteria['property_type'] === 'appartment' ? 'apartment' : $criteria['property_type'];
            $query->where('property_type', $type);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get featured properties
     */
    public function getFeaturedProperties(int $limit = 12): \Illuminate\Database\Eloquent\Collection
    {
        return Property::with(['owner', 'agent'])
            ->where('status', 'validated')
            ->where('is_featured', true)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new property
     */
    public function createProperty(array $data, int $userId): Property
    {
        $data['owner_id'] = $userId;
        $data['status'] = 'pending_validation';
        
        return Property::create($data);
    }

    /**
     * Update existing property
     */
    public function updateProperty(Property $property, array $data): Property
    {
        $property->update($data);
        return $property;
    }

    /**
     * Delete property
     */
    public function deleteProperty(Property $property): bool
    {
        return $property->delete();
    }

    /**
     * Apply common filters to property query
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['city']) && $filters['city']) {
            $query->where('city', 'like', '%'.$filters['city'].'%');
        }

        if (isset($filters['operation_type']) && $filters['operation_type']) {
            $query->where('operation_type', $filters['operation_type']);
        }

        if (isset($filters['catalog_type']) && $filters['catalog_type']) {
            $query->where('catalog_type', $filters['catalog_type']);
        }

        if (isset($filters['property_type']) && $filters['property_type']) {
            $type = $filters['property_type'] === 'appartment' ? 'apartment' : $filters['property_type'];
            $query->where('property_type', $type);
        }

        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $query->orderBy('price');
                    break;
                case 'price_desc':
                    $query->orderByDesc('price');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    /**
     * Mark property as occupied
     */
    public function occupyProperty(Property $property, int $userId, string $userName): void
    {
        $property->update([
            'is_occupied' => true,
            'occupied_by_user_id' => $userId,
            'occupied_by_user_name' => $userName,
            'occupied_at' => now(),
        ]);
    }

    /**
     * Create inquiry notification for property
     */
    public function createInquiry(Property $property, int $userId): Notification
    {
        return Notification::create([
            'user_id' => $property->owner_id,
            'title' => 'Nouvelle demande d\'information',
            'message' => "Un utilisateur a demandé des informations sur: {$property->title}",
            'type' => 'inquiry',
            'reference_id' => $property->id,
            'reference_type' => 'property',
        ]);
    }
}
