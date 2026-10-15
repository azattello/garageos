<?php

namespace App\Http\Controllers;

use App\Models\OrderPhoto;
use App\Models\Order;
use App\Models\PublicOrderToken;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderPhotoController extends Controller
{
    public function update(Request $request, Order $order, OrderPhoto $photo)
    {
        $this->authorizePhotoManagement($request, $order, $photo);
        $data = $request->validate([
            'category' => ['required', 'in:before,damage,process,after,other'],
            'caption' => ['nullable', 'string', 'max:160'],
            'visible_to_client' => ['sometimes', 'boolean'],
        ]);
        $before = $photo->only(['category', 'caption', 'visible_to_client']);
        $photo->update([
            ...$data,
            'visible_to_client' => (bool) ($data['visible_to_client'] ?? false),
        ]);
        Activity::record('order.photo_updated', $photo, $request->user(), [
            'order_id' => $order->id,
            'before' => $before,
            'after' => $photo->only(['category', 'caption', 'visible_to_client']),
        ]);

        return back()->with('success', 'Данные фотографии обновлены.');
    }

    public function archive(Request $request, Order $order, OrderPhoto $photo)
    {
        $this->authorizePhotoManagement($request, $order, $photo);
        Activity::record('order.photo_archived', $photo, $request->user(), [
            'order_id' => $order->id,
            'category' => $photo->category,
            'visible_to_client' => $photo->visible_to_client,
        ]);
        $photo->delete();

        return back()->with('success', 'Фотография архивирована.');
    }

    public function public(string $token, OrderPhoto $photo): StreamedResponse
    {
        $tokenRecord = PublicOrderToken::where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->firstOrFail();
        abort_if($tokenRecord->expires_at?->isPast(), 404);
        $order = $tokenRecord->order()->firstOrFail();
        abort_unless($tokenRecord->organization_id === $order->organization_id
            && $photo->organization_id === $order->organization_id
            && $photo->order_id === $order->id
            && $photo->visible_to_client, 404);

        return $this->stream($photo);
    }

    public function portal(Request $request, OrderPhoto $photo): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'client' && $user->organization_id === $photo->organization_id && $photo->visible_to_client, 404);
        abort_unless(Gate::forUser($user)->allows('viewPortal', $photo->order), 404);

        return $this->stream($photo);
    }

    public function staff(Request $request, Order $order, OrderPhoto $photo): StreamedResponse
    {
        abort_unless($photo->order_id === $order->id && $photo->organization_id === $order->organization_id, 404);
        Gate::forUser($request->user())->authorize('view', $order);

        return $this->stream($photo);
    }

    private function stream(OrderPhoto $photo): StreamedResponse
    {
        $disk = in_array($photo->storage_disk, ['local', 'public'], true) ? $photo->storage_disk : 'local';
        abort_unless(Storage::disk($disk)->exists($photo->path), 404);

        return Storage::disk($disk)->response($photo->path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ], 'inline');
    }

    private function authorizePhotoManagement(Request $request, Order $order, OrderPhoto $photo): void
    {
        abort_unless($photo->order_id === $order->id && $photo->organization_id === $order->organization_id, 404);
        Gate::forUser($request->user())->authorize('manageFinances', $order);
    }
}
