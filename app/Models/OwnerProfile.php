<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OwnerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'owner_type',
        'company_name',
        'validation_status',
        'validation_notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'owner_type' => 'string',
        'validation_status' => 'string',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id', 'user_id');
    }

    public function occupancyContracts()
    {
        return $this->hasMany(OccupancyContract::class, 'owner_id', 'user_id');
    }

    // Scopes & Methods
    public function scopeValidated($query)
    {
        return $query->where('validation_status', 'validated');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
