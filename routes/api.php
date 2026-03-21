<?php

/*
|--------------------------------------------------------------------------
| API Routes - ORIZON Real Estate Management System
|--------------------------------------------------------------------------|
| Complete RESTful API pour application Flutter immobilière
| Auth: JWT (Tymon\JWTAuth) - compatible Sanctum si migration souhaitée
| Middlewares: auth:api (JWT), admin (custom role check)
| Groupes: Public (auth/register/login) + Protected (properties, admin, chat, etc.)
|--------------------------------------------------------------------------|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * PUBLIC ROUTES - Auth sans token
 */
Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/auth/register', [App\Http\Controllers\AuthController::class, 'register']);

/*
 * PROTECTED ROUTES - Toutes les API nécessitent auth:api (JWT)
 */
Route::middleware('auth:sanctum')->group(function () {

    // =============== USER & AUTH ===============
    Route::prefix('auth')->group(function () {
        Route::get('/me', [App\Http\Controllers\AuthController::class, 'me']);
        Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);
        Route::post('/refresh', [App\Http\Controllers\AuthController::class, 'refreshToken']);
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
        Route::post('/owners/{id}/validate', [App\Http\Controllers\AdminController::class, 'validateOwner']);
        Route::post('/owners/{id}/reject', [App\Http\Controllers\AdminController::class, 'rejectOwner']);

        // Agent management
        Route::get('/agents', [App\Http\Controllers\AdminController::class, 'getAgents']);
        Route::post('/agents/{id}/validate', [App\Http\Controllers\AdminController::class, 'validateAgent']);
        Route::post('/agents/{id}/reject', [App\Http\Controllers\AdminController::class, 'rejectAgent']);
        
        // Properties Admin
        Route::get('/properties', [App\Http\Controllers\AdminController::class, 'getAllProperties']);
        Route::get('/properties/{id}', [App\Http\Controllers\AdminController::class, 'getPropertyDetail']);
        Route::post('/properties/{id}/reject', [App\Http\Controllers\AdminController::class, 'rejectProperty']);
        Route::get('/properties/new', [App\Http\Controllers\AdminController::class, 'getNewProperties']);
        Route::get('/properties/rejected', [App\Http\Controllers\AdminController::class, 'getRejectedProperties']);
        Route::get('/properties/notifications', [App\Http\Controllers\AdminController::class, 'getPropertyNotifications']);
        
        // Withdrawals & Activities
        Route::get('/pending-validations', [App\Http\Controllers\AdminController::class, 'getPendingValidations']);
        Route::get('/withdrawals', [App\Http\Controllers\AdminController::class, 'getWithdrawals']);
        Route::post('/withdrawals/{id}/approve', [App\Http\Controllers\AdminController::class, 'approveWithdrawal']);
        Route::post('/withdrawals/{id}/reject', [App\Http\Controllers\AdminController::class, 'rejectWithdrawal']);
        Route::get('/dashboard/stats', [App\Http\Controllers\AdminController::class, 'dashboard']);
    });

    // =============== CHAT & CONVERSATIONS ===============
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [App\Http\Controllers\ChatController::class, 'index']);
        Route::post('/conversations', [App\Http\Controllers\ChatController::class, 'store']);
        Route::get('/conversations/{id}', [App\Http\Controllers\ChatController::class, 'show']);
        Route::get('/conversations/{id}/messages', [App\Http\Controllers\ChatController::class, 'messages']);
        Route::post('/conversations/{id}/messages', [App\Http\Controllers\ChatController::class, 'sendMessage']);
    });

    // =============== NOTIFICATIONS ===============
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('/unread', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [App\Http\Controllers\NotificationController::class, 'markAllRead']);
    });

    // =============== OCCUPANCY REQUESTS ===============
    Route::prefix('occupancy')->group(function () {
        Route::get('/', [App\Http\Controllers\OccupancyController::class, 'index']);
        Route::post('/requests', [App\Http\Controllers\OccupancyController::class, 'storeRequest']);
        Route::post('/requests/{id}/approve', [App\Http\Controllers\OccupancyController::class, 'approveRequest']);
        Route::post('/requests/{id}/reject', [App\Http\Controllers\OccupancyController::class, 'rejectRequest']);
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
    });

    // =============== CONTRACTS ===============
    Route::prefix('contracts')->group(function () {
        Route::get('/', [App\Http\Controllers\OccupancyController::class, 'contracts']); // Reuse or dedicated
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

