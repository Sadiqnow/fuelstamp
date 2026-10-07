<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStationStaffRequest;
use App\Http\Resources\StationStaffResource;
use App\Models\Role;
use App\Models\Station;
use App\Models\StationStaff;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class StationStaffController extends Controller
{
    public function index(
        Station $station
    ): AnonymousResourceCollection {
        $this->ensureStationScope($station);

        $staff = StationStaff::query()
            ->where('station_id', $station->id)
            ->with('user')
            ->orderBy('staff_type')
            ->get();

        return StationStaffResource::collection(
            $staff
        );
    }

    public function store(
        AssignStationStaffRequest $request,
        Station $station
    ): JsonResponse {
        $this->ensureStationScope($station);

        $targetUser = User::findOrFail(
            $request->user_id
        );

        /*
         * Platform admins may manage tenants,
         * but station staff themselves must belong
         * to the station tenant.
         */
        abort_if(
            $targetUser->tenant_id !== $station->tenant_id,
            422,
            'The selected user does not belong to this station tenant.'
        );

        $role = Role::where(
            'code',
            $request->staff_type
        )->firstOrFail();

        $hasRole = $targetUser
            ->roleAssignments()
            ->whereNull('effective_to')
            ->where('role_id', $role->id)
            ->exists();

        abort_unless(
            $hasRole,
            422,
            'The selected user does not have the required role.'
        );

        $assignment = DB::transaction(
            function () use (
                $request,
                $station,
                $targetUser,
                $role
            ) {
                $staff = StationStaff::withTrashed()
                    ->where(
                        'station_id',
                        $station->id
                    )
                    ->where(
                        'user_id',
                        $targetUser->id
                    )
                    ->where(
                        'staff_type',
                        $request->staff_type
                    )
                    ->first();

                if ($staff) {
                    $staff->restore();

                    $staff->update([
                        'status' => 'ACTIVE',
                    ]);
                } else {
                    $staff = StationStaff::create([
                        'tenant_id' =>
                            $station->tenant_id,

                        'station_id' =>
                            $station->id,

                        'user_id' =>
                            $targetUser->id,

                        'staff_type' =>
                            $request->staff_type,

                        'status' =>
                            'ACTIVE',
                    ]);
                }

                /*
                 * Also scope the user's role assignment
                 * to this station.
                 */
                UserRole::query()
                    ->where('user_id', $targetUser->id)
                    ->where('role_id', $role->id)
                    ->whereNull('effective_to')
                    ->update([
                        'station_id' => $station->id,
                    ]);

                return $staff;
            }
        );

        $assignment->load('user');

        return response()->json([
            'data' =>
                new StationStaffResource($assignment),
        ], 201);
    }

    public function deactivate(
        StationStaff $stationStaff
    ): JsonResponse {
        $this->ensureStaffScope($stationStaff);

        $stationStaff->update([
            'status' => 'INACTIVE',
        ]);

        return response()->json([
            'data' => [
                'message' =>
                    'Station staff assignment deactivated.',
            ],
        ]);
    }

    private function ensureStationScope(
        Station $station
    ): void {
        $user = request()->user();

        if ($user->hasRole('PLATFORM_ADMIN')) {
            return;
        }

        abort_if(
            $station->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this station.'
        );
    }

    private function ensureStaffScope(
        StationStaff $stationStaff
    ): void {
        $user = request()->user();

        if ($user->hasRole('PLATFORM_ADMIN')) {
            return;
        }

        abort_if(
            $stationStaff->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this staff assignment.'
        );
    }
}
