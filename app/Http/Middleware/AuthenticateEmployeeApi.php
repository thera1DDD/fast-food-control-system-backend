<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateEmployeeApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return response()->json([
                'message' => 'Employee token is required.',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($plainTextToken);

        if (! $accessToken || ! $accessToken->tokenable instanceof Employee) {
            return response()->json([
                'message' => 'Employee token is invalid.',
            ], 401);
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'message' => 'Employee token has expired.',
            ], 401);
        }

        $employee = $accessToken->tokenable;

        $accessToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        Auth::setUser($employee);
        $request->setUserResolver(fn () => $employee);
        $request->attributes->set('employee_access_token', $accessToken);

        return $next($request);
    }
}
