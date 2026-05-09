<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $property = $this->route('property');
        return $this->user()->id === $property->owner_id || $this->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'catalog_type' => 'sometimes|required|in:residential,commercial,project',
            'property_type' => 'sometimes|required|in:apartment,house,villa,studio,bureau,land,commercial,garage',
            'operation_type' => 'sometimes|required|in:rent,sale,lease,reservation',
            'price' => 'sometimes|required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'price_period' => 'nullable|in:monthly,yearly,daily',
            'condition' => 'nullable|in:new,good,average,renovation_needed',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string',
            'region' => 'nullable|string',
            'neighborhood' => 'nullable|string',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'surface_area' => 'nullable|numeric|min:0',
            'floors' => 'nullable|integer|min:1',
            'star_rating' => 'nullable|integer|between:1,5',
            'photos' => 'nullable|array',
            'amenities' => 'nullable|array',
            'is_featured' => 'nullable|boolean',
        ];
    }
}
