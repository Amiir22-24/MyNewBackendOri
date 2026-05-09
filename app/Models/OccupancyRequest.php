<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OccupancyRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'client_id',
        'owner_id',
        'agent_id',
        'proposed_amount',
        'rent_amount',
        'currency',
        'message',
        'start_date',
        'end_date',
        'status',
        'rejection_reason',
        'rejected_by',
        'agent_notes',
        'agent_validated_at',
        'contract_url',
    ];

    protected $casts = [
        'proposed_amount'    => 'decimal:2',
        'rent_amount'        => 'decimal:2',
        'start_date'         => 'date',
        'end_date'           => 'date',
        'agent_validated_at' => 'datetime',
        'status'             => 'string',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            default => $this->status
        };
    }

    public function getContractUrlAttribute($value)
    {
        if (!$value) return null;
        if (preg_match('#^https?://#i', $value)) return $value;
        return asset('storage/' . ltrim($value, '/'));
    }
}
