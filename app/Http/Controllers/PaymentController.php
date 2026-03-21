<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/PaymentController --api
     * Create payment (subscription, etc.)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:subscription,commission_withdrawal,mobile_money',
            'payment_method' => 'required|string',
            'reference' => 'string', // stripe/moov reference
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'amount' => $validator->validated()['amount'],
            'type' => $validator->validated()['type'],
            // ...
        ]);

        // Update subscription if applicable
        if ($subscriptionId = $validator->validated()['subscription_id']) {
            Subscription::find($subscriptionId)->update(['status' => 'paid']);
        }

        // Notify admin
        Notification::create([
            'user_id' => 1, // admin
            'type' => 'new_payment',
            'data' => ['payment_id' => $payment->id],
        ]);

        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Paiement enregistré'
        ], 201);
    }

    /**
     * List user payments
     */
    public function index(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->with('subscription')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Verify payment (callback)
     */
    public function verify(Request $request)
    {
        // Logic for moov/stripe verification
        return response()->json(['success' => true, 'status' => 'verified']);
    }

    // other methods...
}
