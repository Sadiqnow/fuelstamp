<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StationResource;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StationApprovalController extends Controller
{
    public function approve(
        Request $request,
        Station $station
    ): JsonResponse {
        $request->validate([
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        if ($station->status === 'ACTIVE') {
            return response()->json([
                'error' => [
                    'code' => 'STATION_ALREADY_ACTIVE',
                    'message' => 'This station is already active.',
                    'details' => [],
                ],
            ], 409);
        }

        if ($station->status !== 'PENDING') {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_STATION_STATUS',
                    'message' =>
                        'Only a PENDING station can be approved.',
                    'details' => [
                        'current_status' => $station->status,
                    ],
                ],
            ], 409);
        }

        DB::transaction(function () use (
            $request,
            $station
        ) {
            $station->update([
                'status' => 'ACTIVE',

                'approved_by_user_id' =>
                    $request->user()->id,

                'approved_at' =>
                    now(),

                'approval_notes' =>
                    $request->notes,
            ]);
        });

        return response()->json([
            'data' =>
                new StationResource(
                    $station->fresh()
                ),
        ]);
    }
}