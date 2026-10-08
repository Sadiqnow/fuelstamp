<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletLedgerEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'direction' =>
                $this->direction,

            'asset_type' =>
                $this->asset_type,

            'fuel_product' =>
                $this->fuelProduct
                    ? [
                        'id' =>
                            $this->fuelProduct->id,

                        'code' =>
                            $this->fuelProduct->code,

                        'name' =>
                            $this->fuelProduct->name,
                    ]
                    : null,

            'amount_minor' =>
                $this->amount_minor,

            'amount_naira' =>
                $this->asset_type === 'NGN'
                && $this->amount_minor !== null
                    ? number_format(
                        $this->amount_minor / 100,
                        2,
                        '.',
                        ''
                    )
                    : null,

            'volume_litres' =>
                $this->volume_litres,

            'balance_after_minor' =>
                $this->balance_after_minor,

            'balance_after_litres' =>
                $this->balance_after_litres,

            'entry_type' =>
                $this->entry_type,

            'reference_type' =>
                $this->reference_type,

            'reference_id' =>
                $this->reference_id,

            'reference_code' =>
                $this->reference_code,

            'notes' =>
                $this->notes,

            'posted_at' =>
                $this->posted_at?->toISOString(),
        ];
    }
}