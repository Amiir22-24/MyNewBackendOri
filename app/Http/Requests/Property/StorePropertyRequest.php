<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_owner || $this->user()->is_agent;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'catalog_type' => 'required|in:residential,commercial,project',
            'property_type' => 'required|in:apartment,house,villa,studio,bureau,land,commercial,garage',
            'operation_type' => 'required|in:rent,sale,lease,reservation',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'price_period' => 'nullable|in:monthly,yearly,daily',
            'condition' => 'nullable|in:new,good,average,renovation_needed',
            'address' => 'required|string',
            'city' => 'required|string',
            'region' => 'nullable|string',
            'neighborhood' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'surface_area' => 'nullable|numeric|min:0',
            'floors' => 'nullable|integer|min:1',
            'star_rating' => 'nullable|integer|between:1,5',
            'photos' => 'nullable|array',
            'amenities' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est requis',
            'description.required' => 'La description est requise',
            'price.required' => 'Le prix est requis',
            'latitude.required' => 'La latitude est requise',
            'longitude.required' => 'La longitude est requise',
        ];
    }
}
