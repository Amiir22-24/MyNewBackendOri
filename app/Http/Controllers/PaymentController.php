<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|string|max:64',
            'payment_method' => 'required|string|max:64',
            'stripe_charge_id' => 'nullable|string|max:255',
            'external_reference' => 'nullable|string|max:255',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $stripeId = $data['stripe_charge_id'] ?? null;

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'subscription_id' => $data['subscription_id'] ?? null,
            'stripe_charge_id' => $stripeId,
            'amount' => $data['amount'],
            'status' => $stripeId ? 'succeeded' : 'pending',
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'external_reference' => $data['external_reference'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $admin = \App\Models\User::where('user_type', 'admin')->orderBy('id')->first();
        if ($admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_payment',
                'title' => 'Nouveau paiement',
                'message' => 'Un paiement a été enregistré.',
                'data' => ['payment_id' => $payment->id],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Paiement enregistré',
        ], 201);
    }

    public function index(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->with('subscription')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $payment = Payment::findOrFail($id);
        if ((int) $payment->user_id !== (int) $request->user()->id && ! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment->load('subscription'),
        ]);
    }

    /**
     * Callback / vérif paiement (Stripe, etc.) — à brancher sur la logique réelle.
     */
    public function verify(Request $request)
    {
        return response()->json([
            'success' => true,
            'status' => 'verified',
            'message' => 'Brancher ici la vérification serveur (Stripe webhook / intent).',
        ]);
    }

    /**
     * Initier un paiement mobile money — retourne références pour l’app Flutter.
     */
    public function initiateMobileMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'provider' => 'required|string|in:orange,mtn,moov,wave',
            'phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $ref = 'MM-'.Str::upper(Str::random(12));

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $ref,
                'amount' => $validator->validated()['amount'],
                'provider' => $validator->validated()['provider'],
                'instructions' => 'Finaliser le paiement via l’API du fournisseur (USSD / deep link).',
            ],
        ]);
    }

    public function verifyPayment(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'À implémenter : appeler l’API opérateur avec la référence et mettre à jour payments.',
        ]);
    }
}
