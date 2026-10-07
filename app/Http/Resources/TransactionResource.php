<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'transaction_code' =>
                $this->transaction_code,

            'lane_type' =>
                $this->lane_type,

            'status' =>
                $this->status,

            'shift_id' =>
                $this->shift_id,

            'station_id' =>
                $this->station_id,

            'attendant' => $this->whenLoaded(
                'attendant',
                fn () => [
                    'id' => $this->attendant->id,
                    'user_code' =>
                        $this->attendant->user_code,
                    'name' =>
                        $this->attendant->name,
                ]
            ),

            'nozzle' => $this->whenLoaded(
                'nozzle',
                fn () => [
                    'id' => $this->nozzle->id,
                    'nozzle_code' =>
                        $this->nozzle->nozzle_code,
                ]
            ),

            'fuel_product' =>
                $this->whenLoaded(
                    'fuelProduct',
                    fn () => [
                        'id' =>
                            $this->fuelProduct->id,

                        'code' =>
                            $this->fuelProduct->code,

                        'name' =>
                            $this->fuelProduct->name,
                    ]
                ),

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

            'manual_detail' =>
                $this->whenLoaded(
                    'manualDetail'
                ),

            'payment' =>
                $this->whenLoaded(
                    'payment'
                ),

            'completed_at' =>
                $this->completed_at?->toISOString(),
        ];
    }
}