<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftAuditPackageResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,

            'reconciliation_report_id' =>
                $this->reconciliation_report_id,

            'package_hash' =>
                $this->package_hash,

            'sealed_by_user_id' =>
                $this->sealed_by_user_id,

            'sealed_at' =>
                $this
                    ->sealed_at
                    ?->toISOString(),
        ];
    }
}