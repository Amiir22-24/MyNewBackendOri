<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login user with email/matricule and password
     */
    public function login(LoginRequest $request)
    {
        $user = $this->authService->login(
            $request->validated()['email'],
            $request->validated()['password']
        );

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => $user === null ? 'Identifiant ou mot de passe incorrect' : 'Compte non accessible',
            ], 401);
        }

        $user = $this->authService->loadUserProfile($user);
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => UserResource::make($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => UserResource::make($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Get current user profile
     */
    public function me(Request $request)
    {
        $user = $this->authService->loadUserProfile($request->user());

        return response()->json([
            'success' => true,
            'data' => UserResource::make($user),
        ]);
    }

    /**
     * Refresh authentication token
     */
    public function refreshToken(Request $request)
    {
        // On récupère le token depuis le header Authorization (Bearer <token>)
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token absente',
            ], 401);
        }

        // On cherche le token dans la base (Sanctum::findToken)
        // Cela permet de retrouver l'utilisateur même si la route n'est pas protégée
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou introuvable',
            ], 401);
        }

        $user = $accessToken->tokenable;
        
        // Supprimer tous les tokens précédents (comportement d'origine)
        $user->tokens()->delete();
        
        // Générer un nouveau token
        $newToken = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $newToken,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'avatar' => 'nullable|string|max:2048',
        ]);

        $user = $this->authService->updateProfile($request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour',
            'data' => UserResource::make($user),
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $validated = $request->validated();
        $success = $this->authService->changePassword(
            $request->user(),
            $validated['current_password'],
            $validated['new_password']
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié',
        ]);
    }

    /**
     * Request password reset
     */
    public function forgotPassword(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Non implémenté : configurer l\'envoi d\'email (lien de réinitialisation).',
        ], 501);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Non implémenté : utiliser un token de reset valide.',
        ], 501);
    }
}

