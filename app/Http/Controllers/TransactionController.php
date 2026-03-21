<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Receipt;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Artisan: php artisan make:controller Api/TransactionController --api
     * List user transactions
     */
    public function index(Request $request)
    {
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->with(['property', 'receipts', 'commissions.agent'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Show transaction details
     */
    public function show(Request $request, $id)
    {
        $transaction = Transaction::with(['property', 'user', 'receipts'])
            ->findOrFail($id);

        if ($transaction->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    // other CRUD for admin
}
