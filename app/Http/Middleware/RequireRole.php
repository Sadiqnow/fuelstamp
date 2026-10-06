<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication is required.',
                    'details' => [],
                ],
            ], 401);
        }

        $allowed = collect($roles)->contains(
            fn ($role) => $user->hasRole($role)
        );

        if (! $allowed) {
            return response()->json([
                'error' => [
                    'code' => 'ROLE_FORBIDDEN',
                    'message' => 'You do not have permission to perform this action.',
                    'details' => [
                        'required_roles' => $roles,
                    ],
                ],
            ], 403);
        }

        return $next($request);
    }
}