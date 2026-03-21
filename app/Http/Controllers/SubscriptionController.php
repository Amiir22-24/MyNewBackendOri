<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/SubscriptionController --api
     * Get available plans
     */
    public function plans()
    {
        // Hardcoded plans for prototype
        $plans = [
            ['id' => 1, 'name' => 'Basic', 'price' => 5000, 'duration' => 'monthly'],
            ['id' => 2, 'name' => 'Premium', 'price' => 15000, 'duration' => 'monthly'],
            ['id' => 3, 'name' => 'Pro', 'price' => 30000, 'duration' => 'monthly'],
        ];

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * List user subscriptions
     */
    public function index(Request $request)
    {
        $subscriptions = Subscription::where('user_id', $request->user()->id)
            ->with('payments')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Subscribe to plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id', // assume plans table
            'duration' => 'required|in:monthly,yearly',
        ]);

        $subscription = Subscription::create([
            'user_id' => $request->user()->id,
            'plan_id' => $validator->validated()['plan_id'],
            'status' => 'pending_payment',
            'duration' => $validator->validated()['duration'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Abonnement créé. Procédez au paiement.',
            'data' => $subscription,
            'payment_url' => '/api/payments' // redirect
        ], 201);
    }

    // renew, cancel...
}
