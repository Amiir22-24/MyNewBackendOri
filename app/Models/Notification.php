<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'action_route',
        'is_read',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'type' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Le boot "écoute" la création d'une nouvelle notification.
     * Si elle n'est pas pour un admin, on en crée une copie pour tous les admins.
     */
    protected static function booted()
    {
        static::created(function ($notification) {
            // Eviter une boucle infinie et les envois inutiles
            // On vérifie si la notification est déjà destinée à un admin
            $user = \App\Models\User::find($notification->user_id);
            
            // Si le destinataire est introuvable ou si le type est déjà admin_alert, on stoppe
            if (!$user || $notification->type === 'admin_alert') {
                return;
            }
            
            // Si le destinataire est lui-même un admin (ex: un admin qui fait une action et reçoit une notif directe)
            if ($user->user_type === 'admin') {
                return;
            }

            // Récupérer tous les admins
            $admins = \App\Models\User::where('user_type', 'admin')->get();

            foreach ($admins as $admin) {
                // withoutEvents empêche le redéclenchement de static::created et évite une boucle infinie
                static::withoutEvents(function () use ($admin, $notification) {
                    static::create([
                        'user_id' => $admin->id,
                        'type' => 'admin_alert',
                        'title' => '[Admin] ' . $notification->title,
                        'message' => $notification->message,
                        'data' => $notification->data,
                        'action_route' => $notification->action_route,
                        'is_read' => false,
                    ]);
                });
            }
        });
    }
}
