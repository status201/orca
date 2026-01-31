<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMultiple
{
    /**
     * Handle an incoming request by trying multiple authentication guards.
     *
     * Attempts Sanctum authentication first, then falls back to JWT if enabled.
     * This allows existing Sanctum token integrations to continue working while
     * adding support for JWT authentication for frontend RTE integrations.
     *
     * @param  string  ...$guards  Optional list of guards to try (defaults to sanctum, jwt)
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // Default guards to try in order
        $guardsToTry = ! empty($guards) ? $guards : ['sanctum', 'jwt'];

        foreach ($guardsToTry as $guard) {
            // Skip JWT guard if JWT authentication is disabled (either via env or setting)
            if ($guard === 'jwt') {
                $envEnabled = config('jwt.enabled', false);
                $settingEnabled = Setting::get('jwt_enabled_override', true);
                if (! $envEnabled || ! $settingEnabled) {
                    continue;
                }
            }

            if (Auth::guard($guard)->check()) {
                // Set this guard as the default for the request
                Auth::shouldUse($guard);

                return $next($request);
            }
        }

        // No authentication method succeeded
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
