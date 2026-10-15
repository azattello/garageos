<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        if ($user?->isPlatformAdmin()) {
            return $next($request);
        }

        abort_unless($user?->organization_id, 403);
        $subscription = $user->organization?->subscription;
        if ($subscription?->hasFeature($feature)) {
            return $next($request);
        }

        return to_route('subscription')->with('feature_unavailable', $feature);
    }
}
