<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $receipts = Receipt::query()
            ->where('user_id', $request->user()->id)
            ->with('transaction')
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $receipts,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $receipt = Receipt::with('transaction')->findOrFail($id);

        if ((int) $receipt->user_id !== (int) $request->user()->id && ! $request->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }
}
