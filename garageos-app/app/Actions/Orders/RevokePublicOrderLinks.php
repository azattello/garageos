<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\PublicOrderToken;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;

class RevokePublicOrderLinks
{
    public function handle(User $user, Order $order): int
    {
        abort_unless($user->organization_id === $order->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);

        return DB::transaction(function () use ($user, $order): int {
            $lockedOrder = Order::where('organization_id', $user->organization_id)->lockForUpdate()->findOrFail($order->id);
            $tokens = PublicOrderToken::where('organization_id', $user->organization_id)
                ->where('order_id', $lockedOrder->id)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get();
            if ($tokens->isEmpty()) {
                return 0;
            }

            PublicOrderToken::whereIn('id', $tokens->modelKeys())->update(['revoked_at' => now()]);
            Activity::record('order.public_links_revoked', $lockedOrder, $user, ['revoked_count' => $tokens->count()]);

            return $tokens->count();
        });
    }
}
