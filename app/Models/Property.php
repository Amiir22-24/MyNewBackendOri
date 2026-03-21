<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'catalog_type',
        'property_type',
        'operation_type',
        'price',
        'currency',
        'price_period',
        'condition',
        'address',
        'city',
        'region',
        'neighborhood',
        'latitude',
        'longitude',
        'bedrooms',
        'bathrooms',
        'surface_area',
        'owner_id',
        'agent_id',
        'owner_matricule',
        'occupied_by_user_id',
        'rejected_by_admin_id',
        'status',
        'is_featured',
        'images',
        'amenities',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'surface_area' => 'decimal:2',
        'is_featured' => 'boolean',
        'images' => 'array',
        'amenities' => 'array',
        'catalog_type' => 'string',
        'property_type' => 'string',
        'operation_type' => 'string',
        'condition' => 'string',
        'status' => 'string',
    ];

    // Relations
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function occupiedBy()
    {
        return $this->belongsTo(User::class, 'occupied_by_user_id');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by_admin_id');
    }

    public function occupancyRequests()
    {
        return $this->hasMany(OccupancyRequest::class);
    }

    public function occupancyContracts()
    {
        return $this->hasMany(OccupancyContract::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function adminActivities()
    {
        return $this->morphMany(AdminActivity::class, 'target');
    }

    // Scopes & Methods
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeForRent($query)
    {
        return $query->where('operation_type', 'rent');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getFullAddressAttribute()
    {
        return $this->address . ', ' . $this->city . ($this->region ? ', ' . $this->region : '');
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'validated' => 'Validée',
            'rejected' => 'Rejetée',
            default => $this->status
        };
    }

    public function isAvailable()
    {
        return !$this->occupied_by_user_id || $this->occupancyContracts()->where('is_active', true)->doesntExist();
    }
}
