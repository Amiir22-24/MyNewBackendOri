<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'stripe_charge_id',
        'amount',
        'status',
        'payment_method',
        'payment_type',
        'external_reference',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'string',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }
}
