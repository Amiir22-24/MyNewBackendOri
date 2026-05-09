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
        'floors',
        'owner_id',
        'owner_name',
        'owner_phone',
        'owner_matricule',
        'agent_id',
        'agent_name',
        'occupied_by_user_id',
        'occupied_by_user_name',
        'occupied_at',
        'contract_url',
        'rejected_by_admin_id',
        'rejection_reason',
        'status',
        'is_featured',
        'is_available',
        'is_occupied',
        'star_rating',
        'was_auto_validated',
        'photos',
        'amenities',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'surface_area' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_available' => 'boolean',
        'is_occupied' => 'boolean',
        'was_auto_validated' => 'boolean',
        'photos' => 'array',
        'amenities' => 'array',
        'catalog_type' => 'string',
        'property_type' => 'string',
        'operation_type' => 'string',
        'condition' => 'string',
        'status' => 'string',
        'occupied_at' => 'datetime',
    ];

    // Constantes pour le système de notation qualité
    const AMENITY_WEIGHTS = [
        // Équipements essentiels (poids élevé)
        'water' => 15,           // Eau courante
        'electricity' => 15,     // Électricité
        'wifi' => 10,            // Internet/WiFi
        'security' => 10,        // Sécurité
        'parking' => 8,          // Parking/Garage
        'generator' => 8,        // Groupe électrogène

        // Confort (poids moyen)
        'air_conditioning' => 6, // Climatisation
        'heating' => 6,          // Chauffage
        'kitchen' => 6,          // Cuisine équipée
        'washing_machine' => 5,  // Lave-linge
        'dryer' => 4,            // Sèche-linge
        'dishwasher' => 4,       // Lave-vaisselle

        // Commodités (poids faible)
        'pool' => 7,             // Piscine
        'gym' => 5,              // Salle de sport
        'garden' => 5,           // Jardin
        'balcony' => 4,          // Balcon/Terrasse
        'elevator' => 4,         // Ascenseur
        'concierge' => 4,        // Concierge
        'terrace' => 3,          // Terrasse
        'storage' => 3,          // Local de stockage
    ];

    const QUALITY_THRESHOLDS = [
        1 => ['min' => 0, 'max' => 25, 'label' => 'Basique'],
        2 => ['min' => 26, 'max' => 45, 'label' => 'Standard'],
        3 => ['min' => 46, 'max' => 65, 'label' => 'Confortable'],
        4 => ['min' => 66, 'max' => 85, 'label' => 'Premium'],
        5 => ['min' => 86, 'max' => 100, 'label' => 'Luxe'],
    ];

    protected $appends = [
        'photos_with_urls',
        'quality_score',
        'quality_label',
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

    // Méthodes pour le système de notation qualité

    /**
     * Calcule automatiquement le score de qualité basé sur les équipements
     */
    public function calculateQualityScore()
    {
        $score = 0;
        $amenities = $this->amenities ?? [];

        foreach ($amenities as $amenity) {
            if (isset(self::AMENITY_WEIGHTS[$amenity])) {
                $score += self::AMENITY_WEIGHTS[$amenity];
            }
        }

        // Bonus pour les caractéristiques de base de la propriété
        if ($this->bedrooms > 0) $score += min($this->bedrooms * 2, 10);
        if ($this->bathrooms > 0) $score += min($this->bathrooms * 3, 15);
        if ($this->surface_area > 50) $score += min(($this->surface_area / 50) * 2, 20);

        // Bonus selon l'état général
        switch ($this->condition) {
            case 'new': $score += 10; break;
            case 'good': $score += 5; break;
            case 'average': $score += 0; break;
            case 'renovation_needed': $score -= 5; break;
        }

        // Limiter le score entre 0 et 100
        return max(0, min(100, $score));
    }

    /**
     * Détermine la note en étoiles (1-5) basée sur le score
     */
    public function calculateStarRating()
    {
        $score = $this->calculateQualityScore();

        foreach (self::QUALITY_THRESHOLDS as $stars => $threshold) {
            if ($score >= $threshold['min'] && $score <= $threshold['max']) {
                return $stars;
            }
        }

        return 1; // Par défaut 1 étoile
    }

    /**
     * Met à jour automatiquement la note en étoiles
     */
    public function updateStarRating()
    {
        $this->star_rating = $this->calculateStarRating();
        $this->save();
    }

    /**
     * Validation automatique de la propriété
     */
    public function autoValidate()
    {
        // Critères de validation automatique
        $isValid = true;
        $reasons = [];

        // Vérifications de base
        if (empty($this->title)) {
            $isValid = false;
            $reasons[] = 'Titre manquant';
        }

        if (empty($this->description) || strlen($this->description) < 50) {
            $isValid = false;
            $reasons[] = 'Description trop courte (minimum 50 caractères)';
        }

        if (empty($this->address) || empty($this->city)) {
            $isValid = false;
            $reasons[] = 'Adresse incomplète';
        }

        if ($this->price <= 0) {
            $isValid = false;
            $reasons[] = 'Prix invalide';
        }

        if (empty($this->photos) || count($this->photos) < 1) {
            $isValid = false;
            $reasons[] = 'Au moins une photo requise';
        }

        // Vérifications d'équipements essentiels
        $amenities = $this->amenities ?? [];
        $essentialAmenities = ['water', 'electricity'];
        $hasEssential = false;

        foreach ($essentialAmenities as $essential) {
            if (in_array($essential, $amenities)) {
                $hasEssential = true;
                break;
            }
        }

        if (!$hasEssential) {
            $isValid = false;
            $reasons[] = 'Équipements essentiels manquants (eau ou électricité)';
        }

        // Calcul de la qualité et mise à jour de la note
        $this->star_rating = $this->calculateStarRating();

        if ($isValid) {
            $this->status = 'validated';
            $this->was_auto_validated = true;
            $this->save();

            // Notification à l'owner/agent
            $this->notifyValidation(true, 'Propriété validée automatiquement');
        } else {
            $this->status = 'rejected';
            $this->rejection_reason = implode(', ', $reasons);
            $this->save();

            // Notification à l'owner/agent
            $this->notifyValidation(false, 'Propriété rejetée automatiquement: ' . implode(', ', $reasons));
        }

        return $isValid;
    }

    /**
     * Notifie l'owner/agent du résultat de validation
     */
    protected function notifyValidation($approved, $message)
    {
        $userId = $this->agent_id ?? $this->owner_id;

        if ($userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => $approved ? 'property_validated' : 'property_rejected',
                'title' => $approved ? 'Propriété validée' : 'Propriété rejetée',
                'message' => $message,
                'is_read' => false,
                'data' => ['property_id' => $this->id]
            ]);
        }
    }

    // Accessors pour les attributs calculés

    public function getQualityScoreAttribute()
    {
        return $this->calculateQualityScore();
    }

    public function getQualityLabelAttribute()
    {
        $score = $this->calculateQualityScore();

        foreach (self::QUALITY_THRESHOLDS as $stars => $threshold) {
            if ($score >= $threshold['min'] && $score <= $threshold['max']) {
                return $threshold['label'];
            }
        }

        return 'Basique';
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'property_favorites')->withTimestamps();
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

    /**
     * URLs absolues pour affichage (http(s) inchangé, chemins relatifs → /storage/...).
     */
    public function getPhotosWithUrlsAttribute(): array
    {
        $photos = $this->photos;
        if (! is_array($photos)) {
            return [];
        }

        return array_values(array_map(function ($photo) {
            if (! is_array($photo)) {
                return [
                    'photo_url' => $this->resolvePhotoUrl(is_string($photo) ? $photo : ''),
                    'is_main' => false,
                ];
            }
            $raw = $photo['photo_url'] ?? $photo['url'] ?? '';

            return [
                'photo_url' => $this->resolvePhotoUrl(is_string($raw) ? $raw : ''),
                'is_main' => (bool) ($photo['is_main'] ?? false),
            ];
        }, array_filter($photos ?: [])));
    }

    protected function resolvePhotoUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return asset('storage/'.ltrim($url, '/'));
    }

    public function isAvailable()
    {
        return !$this->occupied_by_user_id || $this->occupancyContracts()->where('is_active', true)->doesntExist();
    }
}
