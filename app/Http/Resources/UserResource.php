<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'status' => $this->status,
            'matricule' => $this->matricule,
            'avatar' => $this->avatar,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
