<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $receiptPayload = [
            'transaction_id' =>
                $this->id,

            'transaction_code' =>
                $this->transaction_code,

            'lane_type' =>
                $this->lane_type,

            'status' =>
                $this->status,

            'station' => [
                'id' =>
                    $this->station?->id,

                'station_code' =>
                    $this->station?->station_code,

                'name' =>
                    $this->station?->name,
            ],

            'shift' => [
                'id' =>
                    $this->shift?->id,

                'shift_code' =>
                    $this->shift?->shift_code,
            ],

            'attendant' => [
                'id' =>
                    $this->attendant?->id,

                'user_code' =>
                    $this->attendant?->user_code,

                'name' =>
                    $this->attendant?->name,
            ],

            'nozzle' => [
                'id' =>
                    $this->nozzle?->id,

                'nozzle_code' =>
                    $this->nozzle?->nozzle_code,
            ],

            'fuel_product' => [
                'id' =>
                    $this->fuelProduct?->id,

                'code' =>
                    $this->fuelProduct?->code,

                'name' =>
                    $this->fuelProduct?->name,
            ],

            'volume_litres' =>
                $this->volume_litres,

            'unit_price_minor' =>
                $this->unit_price_minor,

            'unit_price_naira' =>
                number_format(
                    $this->unit_price_minor / 100,
                    2,
                    '.',
                    ''
                ),

            'amount_minor' =>
                $this->amount_minor,

            'amount_naira' =>
                number_format(
                    $this->amount_minor / 100,
                    2,
                    '.',
                    ''
                ),

            'payment' => [
                'method' =>
                    $this->payment?->payment_method,

                'amount_minor' =>
                    $this->payment?->amount_minor,

                'amount_naira' =>
                    $this->payment
                        ? number_format(
                            $this->payment->amount_minor / 100,
                            2,
                            '.',
                            ''
                        )
                        : null,

                'reference' =>
                    $this->payment?->payment_reference,

                'status' =>
                    $this->payment?->status,
            ],

            'completed_at' =>
                $this->completed_at?->toISOString(),
        ];

        ksort($receiptPayload);

        $canonicalJson = json_encode(
            $receiptPayload,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        return [
            'receipt' =>
                $receiptPayload,

            'verification' => [
                'algorithm' =>
                    'SHA-256',

                'hash' =>
                    hash(
                        'sha256',
                        $canonicalJson
                    ),
            ],
        ];
    }
}