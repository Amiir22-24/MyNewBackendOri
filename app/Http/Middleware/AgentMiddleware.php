<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AgentMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->is_agent) {
            return response()->json([
                'success' => false,
                'message' => 'Accès agent seulement',
            ], 403);
        }

        return $next($request);
    }
}