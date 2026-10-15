<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderPhoto;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Throwable;

class UploadOrderPhoto
{
    public function handle(User $user, Order $order, UploadedFile $photo, array $data): OrderPhoto
    {
        abort_unless($user->organization_id === $order->organization_id, 403);
        $path = $photo->store('organizations/'.$user->organization_id.'/orders/'.$order->id, 'local');

        try {
            return DB::transaction(function () use ($user, $order, $path, $data): OrderPhoto {
                $record = $order->photos()->create([
                    'organization_id' => $user->organization_id,
                    'car_id' => $order->car_id,
                    'path' => $path,
                    'storage_disk' => 'local',
                    'category' => $data['category'],
                    'caption' => $data['caption'] ?? null,
                    'visible_to_client' => (bool) ($data['visible_to_client'] ?? false),
                    'uploaded_by' => $user->id,
                ]);
                Activity::record('order.photo_uploaded', $record, $user, [
                    'order_id' => $order->id,
                    'category' => $record->category,
                    'visible_to_client' => $record->visible_to_client,
                ]);

                return $record;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}