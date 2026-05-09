<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'catalog_type' => $this->catalog_type,
            'property_type' => $this->property_type,
            'operation_type' => $this->operation_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'price_period' => $this->price_period,
            'condition' => $this->condition,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'neighborhood' => $this->neighborhood,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'surface_area' => $this->surface_area,
            'floors' => $this->floors,
            'star_rating' => $this->star_rating,
            'quality_score' => $this->quality_score,
            'quality_label' => $this->quality_label,
            'was_auto_validated' => $this->was_auto_validated,
            'photos' => $this->photos_with_urls,
            'amenities' => $this->amenities,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'agent' => new UserResource($this->whenLoaded('agent')),
            'status' => $this->status,
            'is_available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'is_occupied' => $this->is_occupied,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
