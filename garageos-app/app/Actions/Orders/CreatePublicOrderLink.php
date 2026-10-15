<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\PublicOrderToken;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePublicOrderLink
{
    public function handle(User $user, Order $order): string
    {
        abort_unless($user->organization_id === $order->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);
        $token = Str::random(64);
        DB::transaction(function () use ($user, $order, $token): void {
            $lockedOrder = Order::where('organization_id', $user->organization_id)->lockForUpdate()->findOrFail($order->id);
            $revoked = PublicOrderToken::where('organization_id', $user->organization_id)
                ->where('order_id', $lockedOrder->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
            $publicToken = PublicOrderToken::create([
                'organization_id' => $user->organization_id,
                'order_id' => $lockedOrder->id,
                'token_hash' => hash('sha256', $token),
            ]);
            Activity::record('order.public_link_created', $publicToken, $user, ['order_id' => $lockedOrder->id, 'previous_links_revoked' => $revoked]);
        });

        return $token;
    }
}