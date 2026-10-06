<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $login = trim($validated['login']);

        $user = User::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->orWhere('user_code', $login)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => [
                    'The supplied credentials are incorrect.',
                ],
            ]);
        }

        if (strtoupper(trim((string) $user->status)) !== 'ACTIVE') {
            return response()->json([
                'error' => [
                    'code' => 'USER_NOT_ACTIVE',
                    'message' => 'This account is not active.',
                    'details' => [
                        'status' => $user->status,
                    ],
                ],
            ], 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user->createToken(
            $validated['device_name'] ?? 'FuelStamp Client'
        )->plainTextToken;

        $roles = $user->roleAssignments()
            ->whereNull('effective_to')
            ->with('role')
            ->get()
            ->pluck('role.code')
            ->values();

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',

                'user' => [
                    'id' => $user->id,
                    'user_code' => $user->user_code,
                    'tenant_id' => $user->tenant_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ],

                'roles' => $roles,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $roles = $user->roleAssignments()
            ->whereNull('effective_to')
            ->with('role')
            ->get()
            ->map(function ($assignment) {
                return [
                    'code' => $assignment->role->code,
                    'name' => $assignment->role->name,
                    'station_id' => $assignment->station_id,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'user_code' => $user->user_code,
                    'tenant_id' => $user->tenant_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ],

                'roles' => $roles,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'data' => [
                'message' => 'Logged out successfully.',
            ],
        ]);
    }
}