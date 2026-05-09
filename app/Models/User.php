<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'user_type',
        'status',
        'matricule',
        'avatar',
        'address',
        'city',
        'region',
        'validation_notes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'validated_at' => 'datetime',
        'password' => 'hashed',
        'user_type' => 'string',
        'status' => 'string',
    ];

    // Relations
    public function agentProfile()
    {
        return $this->hasOne(AgentProfile::class);
    }

    public function ownerProfile()
    {
        return $this->hasOne(OwnerProfile::class);
    }

    public function propertiesAsOwner()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function propertiesAsAgent()
    {
        return $this->hasMany(Property::class, 'agent_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function occupancyRequests()
    {
        return $this->hasMany(OccupancyRequest::class, 'client_id');
    }

    public function propertyFavorites()
    {
        return $this->hasMany(PropertyFavorite::class);
    }

    public function favoriteProperties()
    {
        return $this->belongsToMany(Property::class, 'property_favorites')->withTimestamps();
    }

    public function occupancyContractsAsTenant()
    {
        return $this->hasMany(OccupancyContract::class, 'tenant_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function conversationParticipants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function adminActivitiesAsAdmin()
    {
        return $this->hasMany(AdminActivity::class, 'admin_id');
    }

    public function adminActivitiesAsTarget()
    {
        return $this->morphMany(AdminActivity::class, 'target');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'agent_id');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    // Scopes & Methods
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeAgents($query)
    {
        return $query->where('user_type', 'agent');
    }

    public function scopeOwners($query)
    {
        return $query->where('user_type', 'owner');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'validated')->orWhere('status', 'active');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'validated' => 'Actif',
            'rejected' => 'Rejeté',
            'inactive' => 'Désactivé',
            default => 'Inconnu'
        };
    }

    public function getIsAdminAttribute()
    {
        return $this->user_type === 'admin';
    }

    public function getIsAgentAttribute()
    {
        return $this->user_type === 'agent';
    }

    public function getIsOwnerAttribute()
    {
        return $this->user_type === 'owner';
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'validated';
    }

    public function properties()
    {
        return $this->propertiesAsOwner()->or($this->propertiesAsAgent());
    }

}
