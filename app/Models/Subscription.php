<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'stripe_subscription_id',
        'type',
        'amount',
        'status',
        'ends_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'ends_at' => 'datetime',
        'type' => 'string',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isExpired()
    {
        return $this->ends_at && $this->ends_at->isPast();
    }
}
