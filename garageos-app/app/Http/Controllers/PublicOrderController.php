<?php

namespace App\Http\Controllers;

use App\Models\PublicOrderToken;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class PublicOrderController extends Controller
{
    public function show(string $token): Response
    {
        $record = PublicOrderToken::where('token_hash', hash('sha256', $token))->whereNull('revoked_at')->firstOrFail();
        abort_if($record->expires_at?->isPast(), 404);
        $order = $record->order()->with(['client', 'car', 'status', 'items', 'payments', 'photos'])->firstOrFail();
        abort_unless($record->organization_id === $order->organization_id
            && $order->client?->organization_id === $order->organization_id
            && $order->car?->organization_id === $order->organization_id, 404);
        $statusUpdatedAt = ActivityLog::where('organization_id', $order->organization_id)
            ->where('subject_type', $order->getMorphClass())
            ->where('subject_id', $order->id)
            ->where('action', 'order.status_changed')
            ->latest('created_at')
            ->value('created_at');

        return Inertia::render('PublicOrder/Show', [
            'order' => [
                'number' => $order->number,
                'organization' => $order->organization?->name,
                'car' => trim($order->car->make.' '.$order->car->model),
                'plate' => $order->car->plate_number,
                'status' => [
                    'slug' => $order->status?->slug,
                    'name' => $order->status?->name,
                ],
                'status_updated_at' => $statusUpdatedAt,
                'items' => $order->items->map(fn ($item) => [
                    'type' => $item->type,
                    'name' => $item->name_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price' => (int) $item->unit_price,
                    'total' => (int) $item->total,
                ])->values(),
                'photos' => $order->photos->where('visible_to_client', true)->map(fn ($photo) => [
                    'url' => route('orders.public.photos.show', ['token' => $token, 'photo' => $photo->id]),
                    'caption' => $photo->caption,
                    'category' => $photo->category,
                ])->values(),
                'total' => (int) $order->total,
                'paid' => (int) $order->paid_total,
                'balance' => (int) $order->balance,
            ],
        ]);
    }
}