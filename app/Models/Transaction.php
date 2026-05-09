<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_id',
        'type',
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => 'string',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'En cours',
            'succeeded' => 'Réussie',
            'failed' => 'Échouée',
            default => $this->status
        };
    }
}
