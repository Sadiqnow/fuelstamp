<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionReceiptController extends Controller
{
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load([
            'attendant',
            'nozzle',
            'fuelProduct',
            'manualDetail',
            'payment',
        ]);

        return response()->json([
            'data' => new TransactionResource($transaction),
        ]);
    }
}
