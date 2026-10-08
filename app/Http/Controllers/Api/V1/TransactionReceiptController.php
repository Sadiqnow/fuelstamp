<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionReceiptResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionReceiptController extends Controller
{
    public function show(
        Transaction $transaction
    ): JsonResponse {
        $user = request()->user();

        if (
            $transaction->tenant_id !==
            $user->tenant_id
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'TRANSACTION_ACCESS_DENIED',

                    'message' =>
                        'You do not have access to this transaction.',

                    'details' => [],
                ],
            ], 403);
        }

        if (
            $transaction->status !==
            'COMPLETED'
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'TRANSACTION_NOT_RECEIPTABLE',

                    'message' =>
                        'Only completed transactions can generate receipts.',

                    'details' => [
                        'transaction_status' =>
                            $transaction->status,
                    ],
                ],
            ], 409);
        }

        $transaction->load([
            'station',
            'shift',
            'attendant',
            'nozzle',
            'fuelProduct',
            'payment',
        ]);

        return response()->json([
            'data' =>
                new TransactionReceiptResource(
                    $transaction
                ),
        ]);
    }
}