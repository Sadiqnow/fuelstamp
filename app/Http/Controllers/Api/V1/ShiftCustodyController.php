<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftNozzleAssignmentResource;
use App\Models\ShiftAssignment;
use App\Models\ShiftNozzleAssignment;
use Illuminate\Http\JsonResponse;

class ShiftCustodyController extends Controller
{
    public function accept(
        ShiftNozzleAssignment $assignment
    ): JsonResponse {
        $user = request()->user();

        abort_if(
            $assignment->attendant_user_id !== $user->id,
            403,
            'This nozzle assignment does not belong to you.'
        );

        abort_if(
            ! in_array(
                $assignment->shift->status,
                ['DRAFT', 'OPEN'],
                true
            ),
            409,
            'Custody cannot be accepted at the current shift status.'
        );

        if ($assignment->custody_status === 'ACCEPTED') {
            $assignment->load([
                'nozzle',
                'attendant',
            ]);

            return response()->json([
                'data' =>
                    new ShiftNozzleAssignmentResource(
                        $assignment
                    ),
            ]);
        }

        $assignment->update([
            'custody_status' => 'ACCEPTED',
            'accepted_at' => now(),
        ]);

        /*
         * Also mark the attendant's shift assignment
         * as accepted.
         */
        ShiftAssignment::query()
            ->where(
                'shift_id',
                $assignment->shift_id
            )
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'assignment_type',
                'ATTENDANT'
            )
            ->update([
                'status' => 'ACCEPTED',
                'accepted_at' => now(),
            ]);

        $assignment->load([
            'nozzle',
            'attendant',
        ]);

        return response()->json([
            'data' =>
                new ShiftNozzleAssignmentResource(
                    $assignment
                ),
        ]);
    }
}