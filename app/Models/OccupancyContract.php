<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OccupancyContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'occupancy_request_id',
        'property_id',
        'tenant_id',
        'owner_id',
        'agent_id',
        'monthly_rent',
        'deposit_amount',
        'signed_at',
        'start_date',
        'end_date',
        'contract_url',
        'is_active',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRemainingDaysAttribute()
    {
        return $this->end_date->diffInDays(now());
    }

    public function getContractUrlAttribute($value)
    {
        if (!$value) return null;
        if (preg_match('#^https?://#i', $value)) return $value;
        return asset('storage/' . ltrim($value, '/'));
    }
}
