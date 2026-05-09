<?php

/*
|--------------------------------------------------------------------------
| API Routes - ORIZON Real Estate Management System
|--------------------------------------------------------------------------|
| Complete RESTful API pour application Flutter immobilière
| Auth: JWT (Tymon\JWTAuth) - compatible Sanctum si migration souhaitée
| Middlewares: auth:api (JWT), admin (custom role check)
| Public: auth/register (owner|user), login. Agents: POST /api/admin/agents (admin).
|--------------------------------------------------------------------------|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * PUBLIC ROUTES - Auth sans token
 */
Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/auth/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/auth/refresh', [App\Http\Controllers\AuthController::class, 'refreshToken']);

/*
 * PROTECTED ROUTES - Toutes les API nécessitent auth:api (JWT)
 */
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // =============== CLIENT (locataire / utilisateur — aligné features/client Flutter) ===============
    Route::prefix('client')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'client']);
        Route::get('/favorites', [App\Http\Controllers\PropertyFavoriteController::class, 'index']);
        Route::post('/favorites/{propertyId}', [App\Http\Controllers\PropertyFavoriteController::class, 'store'])
            ->whereNumber('propertyId');
        Route::delete('/favorites/{propertyId}', [App\Http\Controllers\PropertyFavoriteController::class, 'destroy'])
            ->whereNumber('propertyId');
        
        // Requêtes d'occupation client
        Route::get('/occupancy-requests', [App\Http\Controllers\OccupancyController::class, 'index']);
        Route::post('/occupancy-requests', [App\Http\Controllers\OccupancyController::class, 'storeRequest']);
    });

    // =============== USER & AUTH ===============
    Route::prefix('auth')->group(function () {
        Route::get('/me', [App\Http\Controllers\AuthController::class, 'me']);
        Route::get('/user', [App\Http\Controllers\AuthController::class, 'me']); // alias for /me
        Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);
        Route::put('/profile', [App\Http\Controllers\AuthController::class, 'updateProfile']);
        Route::post('/change-password', [App\Http\Controllers\AuthController::class, 'changePassword']);
    });

    Route::prefix('password')->group(function () {
        Route::post('/forgot', [App\Http\Controllers\AuthController::class, 'forgotPassword']);
        Route::post('/reset', [App\Http\Controllers\AuthController::class, 'resetPassword']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [App\Http\Controllers\UserController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\UserController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\UserController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\UserController::class, 'destroy']);
    });

    // =============== PROPERTIES (CRUD REST complet) ===============
    Route::prefix('properties')->group(function () {
        // Public-like search (auth but any user)
        Route::get('/', [App\Http\Controllers\PropertyController::class, 'index']);
        Route::get('/search', [App\Http\Controllers\PropertyController::class, 'search']);
        Route::get('/featured', [App\Http\Controllers\PropertyController::class, 'featured']);
        Route::get('/{id}', [App\Http\Controllers\PropertyController::class, 'show']);
        
        // Owner/Agent only
        Route::post('/', [App\Http\Controllers\PropertyController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\PropertyController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\PropertyController::class, 'destroy']);
        Route::post('/{id}/occupy', [App\Http\Controllers\PropertyController::class, 'occupy']);
        Route::post('/{id}/inquiry', [App\Http\Controllers\PropertyController::class, 'createInquiry']);
        Route::post('/{id}/release', [App\Http\Controllers\OccupancyController::class, 'cancel']); // Client cancels
    });

    // =============== PROFILES ===============
    Route::prefix('profiles')->group(function () {
        Route::get('/me', [App\Http\Controllers\ProfileController::class, 'me']);
        Route::put('/agent', [App\Http\Controllers\ProfileController::class, 'updateAgent']);
        Route::put('/owner', [App\Http\Controllers\ProfileController::class, 'updateOwner']);
    });

    // =============== ADMIN (middleware admin requis) ===============
    Route::prefix('admin')->middleware(\App\Http\Middleware\AdminMiddleware::class)->group(function () {
        // Users & Validations
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'getUsers']);
        Route::put('/users/{id}/status', [App\Http\Controllers\AdminController::class, 'updateUserStatus']);

        // Owner management
        Route::post('/owners', [App\Http\Controllers\AdminController::class, 'createOwner']);
        Route::get('/owners', [App\Http\Controllers\AdminController::class, 'listOwners']);

        // Agent creation by admin
        Route::post('/agents', [App\Http\Controllers\AdminController::class, 'createAgent']);

        // Agent management
        Route::get('/agents', [App\Http\Controllers\AdminController::class, 'getAgents']);
        
        // Properties Admin Sub-prefix
        Route::prefix('properties')->group(function () {
            Route::get('/', [App\Http\Controllers\AdminController::class, 'getAllProperties']); // fallback or same as all
            Route::get('/all', [App\Http\Controllers\AdminController::class, 'getAllProperties']);
            Route::get('/new', [App\Http\Controllers\AdminController::class, 'getNewProperties']);
            Route::get('/rejected', [App\Http\Controllers\AdminController::class, 'getRejectedProperties']);
            Route::get('/notifications', [App\Http\Controllers\AdminController::class, 'getPropertyNotifications']);
            Route::get('/{id}', [App\Http\Controllers\AdminController::class, 'getPropertyDetail']);
            Route::post('/{id}/reject', [App\Http\Controllers\AdminController::class, 'rejectProperty']);
        });
        
        // Withdrawals & Activities
        Route::get('/pending-validations', [App\Http\Controllers\AdminController::class, 'getPendingValidations']);
        Route::get('/withdrawals', [App\Http\Controllers\AdminController::class, 'getWithdrawals']);
        Route::post('/withdrawals/{id}/approve', [App\Http\Controllers\AdminController::class, 'approveWithdrawal']);
        Route::post('/withdrawals/{id}/reject', [App\Http\Controllers\AdminController::class, 'rejectWithdrawal']);
        
        Route::get('/dashboard/stats', [App\Http\Controllers\AdminController::class, 'dashboardStats']);
        Route::get('/dashboard/detailed', [App\Http\Controllers\AdminController::class, 'dashboard']); // preserve old one
    });

    // =============== CHAT & CONVERSATIONS ===============
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [App\Http\Controllers\ChatController::class, 'index']);
        Route::post('/conversations', [App\Http\Controllers\ChatController::class, 'store']); // Création
        Route::post('/conversations/get-or-create', [App\Http\Controllers\ChatController::class, 'getOrCreate']); // Sécurité doublons
        Route::get('/conversations/{id}', [App\Http\Controllers\ChatController::class, 'show']);
        Route::post('/conversations/{id}/read', [App\Http\Controllers\ChatController::class, 'markAsRead']);
        Route::post('/conversations/{id}/close', [App\Http\Controllers\ChatController::class, 'close']);
        
        // Messages
        Route::get('/conversations/{id}/messages', [App\Http\Controllers\ChatController::class, 'messages']);
        Route::post('/conversations/{id}/messages', [App\Http\Controllers\ChatController::class, 'sendMessage']);
        Route::post('/messages/{id}/read', [App\Http\Controllers\ChatController::class, 'markMessageRead']);
    });

    Route::prefix('receipts')->group(function () {
        Route::get('/', [App\Http\Controllers\ReceiptController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\ReceiptController::class, 'show'])->whereNumber('id');
    });

    // =============== FAVORITES (alias for /client/favorites) ===============
    Route::get('/favorites', [App\Http\Controllers\PropertyFavoriteController::class, 'index']);
    Route::post('/favorites/{propertyId}', [App\Http\Controllers\PropertyFavoriteController::class, 'store'])
        ->whereNumber('propertyId');
    Route::delete('/favorites/{propertyId}', [App\Http\Controllers\PropertyFavoriteController::class, 'destroy'])
        ->whereNumber('propertyId');

    // =============== NOTIFICATIONS ===============
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('/unread', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [App\Http\Controllers\NotificationController::class, 'markAllRead']);
    });

    // =============== OCCUPANCY REQUESTS ===============
    Route::prefix('occupancy-requests')->group(function () {
        Route::get('/', [App\Http\Controllers\OccupancyController::class, 'index']); // New
        Route::post('/', [App\Http\Controllers\OccupancyController::class, 'storeRequest']); // Centralized
        Route::post('/{id}/agent-approve', [App\Http\Controllers\OccupancyController::class, 'agentApprove']);
        Route::post('/{id}/agent-reject', [App\Http\Controllers\OccupancyController::class, 'agentReject']);
        Route::post('/{id}/owner-approve', [App\Http\Controllers\OccupancyController::class, 'approveRequest']);
    });

    // =============== PAYMENTS & TRANSACTIONS ===============
    Route::prefix('payments')->group(function () {
        Route::get('/', [App\Http\Controllers\PaymentController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\PaymentController::class, 'show']);
        Route::post('/', [App\Http\Controllers\PaymentController::class, 'store']);
        Route::post('/verify', [App\Http\Controllers\PaymentController::class, 'verify']);
        Route::post('/mobile-money/initiate', [App\Http\Controllers\PaymentController::class, 'initiateMobileMoney']);
        Route::post('/mobile-money/verify', [App\Http\Controllers\PaymentController::class, 'verifyPayment']);
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\TransactionController::class, 'show']);
    });

    // =============== SUBSCRIPTIONS ===============
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [App\Http\Controllers\SubscriptionController::class, 'index']);
        Route::get('/plans', [App\Http\Controllers\SubscriptionController::class, 'plans']);
        Route::post('/', [App\Http\Controllers\SubscriptionController::class, 'store']);
        Route::post('/{id}/renew', [App\Http\Controllers\SubscriptionController::class, 'renew']);
    });

    // =============== DASHBOARD (Role-based stats) ===============
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [App\Http\Controllers\DashboardController::class, 'stats']);
        Route::get('/admin', [App\Http\Controllers\DashboardController::class, 'admin']);
        Route::get('/agent', [App\Http\Controllers\DashboardController::class, 'agent']);
        Route::get('/owner', [App\Http\Controllers\DashboardController::class, 'owner']);
        Route::get('/client', [App\Http\Controllers\DashboardController::class, 'client']);
    });

    // =============== AGENT SPECIFIC ROUTES ===============
    Route::prefix('agent')->middleware('auth:sanctum')->group(function () {
        // Properties management
        Route::get('/properties', [App\Http\Controllers\AgentController::class, 'properties']);
        Route::post('/properties', [App\Http\Controllers\AgentController::class, 'storeProperty']);
        Route::put('/properties/{id}', [App\Http\Controllers\AgentController::class, 'updateProperty']);
        Route::delete('/properties/{id}', [App\Http\Controllers\AgentController::class, 'destroyProperty']);

        // Commissions
        Route::get('/commissions', [App\Http\Controllers\CommissionController::class, 'index']);
        Route::get('/commissions/summary', [App\Http\Controllers\CommissionController::class, 'summary']);

        // Performance
        Route::get('/performance', [App\Http\Controllers\AgentController::class, 'performance']);

        // Conversations (alias to chat routes)
        Route::get('/conversations', [App\Http\Controllers\ChatController::class, 'index']);
        Route::post('/conversations', [App\Http\Controllers\ChatController::class, 'store']);
        Route::get('/conversations/{id}', [App\Http\Controllers\ChatController::class, 'show']);
        Route::get('/conversations/{id}/messages', [App\Http\Controllers\ChatController::class, 'messages']);
        Route::post('/conversations/{id}/messages', [App\Http\Controllers\ChatController::class, 'sendMessage']);

        // Territories (placeholder)
        Route::get('/territories', [App\Http\Controllers\TerritoryController::class, 'index']);
        Route::post('/territories', [App\Http\Controllers\TerritoryController::class, 'store']);
        Route::put('/territories/{id}', [App\Http\Controllers\TerritoryController::class, 'update']);
        Route::delete('/territories/{id}', [App\Http\Controllers\TerritoryController::class, 'destroy']);

        // Owner management
        Route::get('/owners/check', [App\Http\Controllers\OwnerManagementController::class, 'checkOwner']);
        Route::post('/owners/register', [App\Http\Controllers\OwnerManagementController::class, 'registerOwner']);
        Route::get('/owners', [App\Http\Controllers\OwnerManagementController::class, 'getOwners']);
        Route::get('/owners/for-selection', [App\Http\Controllers\OwnerManagementController::class, 'getForSelection']);
        Route::get('/owners/by-matricule/{matricule}', [App\Http\Controllers\OwnerManagementController::class, 'getByMatricule']);

        // Occupancy Requests Agent
        Route::get('/occupancy-requests/pending', [App\Http\Controllers\OccupancyController::class, 'agentPendingIndex']);
    });

    // =============== OWNER SPECIFIC ROUTES ===============
    Route::prefix('owner')->group(function () {
        // Demandes d'occupation en attente de validation propriétaire
        Route::get('/occupancy-requests/pending', [App\Http\Controllers\OccupancyController::class, 'ownerPendingIndex']);
        Route::post('/occupancy-requests/{id}/approve', [App\Http\Controllers\OccupancyController::class, 'ownerApprove']);
        Route::post('/occupancy-requests/{id}/reject', [App\Http\Controllers\OccupancyController::class, 'ownerReject']);
    });

    // =============== CONTRACTS ===============
    Route::prefix('contracts')->group(function () {
        Route::get('/', [App\Http\Controllers\OccupancyController::class, 'contracts']);
        Route::get('/{id}', [App\Http\Controllers\OccupancyController::class, 'contractShow']);
        Route::post('/{id}/sign', [App\Http\Controllers\OccupancyController::class, 'signContract']);
        Route::get('/{id}/download', [App\Http\Controllers\OccupancyController::class, 'downloadContract']);
    });
});

/*
 * Notes d'implémentation:
 * - Utilise contrôleur complet (App\Http\Controllers\...)
 * - Compatible Laravel 11+ 
 * - Auth JWT existant (AuthController.php)
 * - Pour Sanctum: changer 'auth:api' -> 'auth:sanctum', update AuthController
 * - Middleware 'admin' custom requis (Kernel.php)
 * - Test: php artisan route:list --path=api
 * - Flutter: Base URL + /api/ (standard Laravel)
 */

