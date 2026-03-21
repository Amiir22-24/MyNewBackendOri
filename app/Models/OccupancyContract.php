<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OccupancyContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'tenant_id',
        'owner_id',
        'agent_id',
        'monthly_rent',
        'start_date',
        'end_date',
        'contract_url',
        'is_active',
        'validation_notes',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
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
}
