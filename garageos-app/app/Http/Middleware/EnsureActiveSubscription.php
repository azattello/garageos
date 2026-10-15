<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->organization_id || $user->isPlatformAdmin()) {
            return $next($request);
        }

        if ($user->role === 'client') {
            abort_unless($request->routeIs('client-portal.*', 'logout'), 403);
            return $next($request);
        }

        $subscription = $user->organization->subscription;
        if ($user->organization->status !== 'active') {
            if ($request->routeIs('subscription')) {
                return $next($request);
            }

            $message = match ($user->organization->status) {
                'blocked' => 'Организация заблокирована администратором платформы.',
                'archived' => 'Организация архивирована администратором платформы.',
                default => 'Доступ организации отключён администратором платформы.',
            };

            return to_route('subscription')->with('organization_blocked', $message);
        }

        if ($subscription) {
            $subscription = app(\App\Actions\Subscriptions\ExpireSubscription::class)->handle($subscription);
        }

        $hasAccess = $subscription && (
            ($subscription->status === 'trialing' && $subscription->trial_ends_at?->isFuture())
            || ($subscription->status === 'active' && $subscription->ends_at?->isFuture())
        );

        if (! $hasAccess && ! $request->routeIs('subscription')) {
            return to_route('subscription')->with('subscription_expired', 'Пробный период или подписка завершены.');
        }

        return $next($request);
    }
}
