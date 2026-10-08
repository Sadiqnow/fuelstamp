<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftAuditPackageDetailResource;
use App\Models\Shift;
use App\Models\ShiftAuditPackage;
use Illuminate\Http\JsonResponse;

class ShiftAuditPackageController extends Controller
{
    public function show(
        Shift $shift
    ): JsonResponse {
        $this->authorizeShiftAccess($shift);

        if ($shift->status !== 'SEALED') {
            return response()->json([
                'error' => [
                    'code' =>
                        'SHIFT_NOT_SEALED',

                    'message' =>
                        'An audit package is only available for a sealed shift.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $package =
            ShiftAuditPackage::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->first();

        if (! $package) {
            return response()->json([
                'error' => [
                    'code' =>
                        'AUDIT_PACKAGE_NOT_FOUND',

                    'message' =>
                        'The sealed shift does not have an audit package.',

                    'details' => [],
                ],
            ], 404);
        }

        return response()->json([
            'data' =>
                new ShiftAuditPackageDetailResource(
                    $package
                ),
        ]);
    }

    public function verify(
        Shift $shift
    ): JsonResponse {
        $this->authorizeShiftAccess($shift);

        if ($shift->status !== 'SEALED') {
            return response()->json([
                'error' => [
                    'code' =>
                        'SHIFT_NOT_SEALED',

                    'message' =>
                        'Integrity verification is only available for sealed shifts.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $package =
            ShiftAuditPackage::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->first();

        if (! $package) {
            return response()->json([
                'error' => [
                    'code' =>
                        'AUDIT_PACKAGE_NOT_FOUND',

                    'message' =>
                        'The sealed shift does not have an audit package.',

                    'details' => [],
                ],
            ], 404);
        }

        /*
         * Recreate the same canonical JSON
         * used when the package was sealed.
         */
        $payload =
            $package->package_payload;

        if (! is_array($payload)) {
            return response()->json([
                'error' => [
                    'code' =>
                        'INVALID_AUDIT_PAYLOAD',

                    'message' =>
                        'The stored audit package payload is invalid.',

                    'details' => [],
                ],
            ], 500);
        }

        ksort($payload);

        $canonicalJson = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        $calculatedHash =
            hash(
                'sha256',
                $canonicalJson
            );

        $isValid =
            hash_equals(
                $package->package_hash,
                $calculatedHash
            );

        return response()->json([
            'data' => [
                'shift_id' =>
                    $shift->id,

                'audit_package_id' =>
                    $package->id,

                'algorithm' =>
                    'SHA-256',

                'stored_hash' =>
                    $package->package_hash,

                'calculated_hash' =>
                    $calculatedHash,

                'integrity_status' =>
                    $isValid
                        ? 'VALID'
                        : 'TAMPERED',

                'is_valid' =>
                    $isValid,

                'sealed_at' =>
                    $package
                        ->sealed_at
                        ?->toISOString(),
            ],
        ]);
    }

    private function authorizeShiftAccess(
        Shift $shift
    ): void {
        $user = request()->user();

        abort_if(
            $shift->tenant_id !==
                $user->tenant_id,
            403,
            'You do not have access to this shift.'
        );

        /*
         * Manager may access own shift.
         * Platform Admin may access tenant shift.
         */
        $isManager =
            $user->hasRole(
                'STATION_MANAGER'
            );

        $isAdmin =
            $user->hasRole(
                'PLATFORM_ADMIN'
            );

        if (
            $isManager &&
            $shift->manager_user_id ===
                $user->id
        ) {
            return;
        }

        if ($isAdmin) {
            return;
        }

        abort(
            403,
            'You are not authorized to access this audit package.'
        );
    }
}