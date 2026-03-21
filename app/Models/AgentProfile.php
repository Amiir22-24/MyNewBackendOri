<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AgentProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'registration_number',
        'commission_rate',
        'validation_status',
        'validation_notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'validation_status' => 'string',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'agent_id', 'user_id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'agent_id', 'user_id');
    }

    public function occupancyContracts()
    {
        return $this->hasMany(OccupancyContract::class, 'agent_id', 'user_id');
    }

    // Scopes & Methods
    public function scopeValidated($query)
    {
        return $query->where('validation_status', 'validated');
    }

    public function getStatusLabelAttribute()
    {
        return match($this->validation_status) {
            'pending' => 'En attente',
            'validated' => 'Validé',
            'rejected' => 'Rejeté',
            default => 'Inconnu'
        };
    }
}
