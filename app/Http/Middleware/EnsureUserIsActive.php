<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->status !== 'validated') {
            \Illuminate\Support\Facades\Log::warning('Accès bloqué par EnsureUserIsActive', [
                'user_id' => $user->id,
                'status' => $user->status,
                'url' => $request->fullUrl(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $user->status === 'inactive'
                    ? 'Compte désactivé'
                    : 'Compte non autorisé',
            ], 401);
        }

        return $next($request);
    }
}
