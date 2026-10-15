<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\PublicOrderToken;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_photo_is_stored_with_client_visibility_flag(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);

        $response = $this->actingAs($owner)->post("/orders/{$order->id}/photos", ['photo' => UploadedFile::fake()->image('damage.jpg'), 'category' => 'damage', 'visible_to_client' => '1']);

        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('order_photos', ['order_id' => $order->id, 'category' => 'damage', 'visible_to_client' => true]);
        $photo = $order->photos()->first();
        $this->assertSame('local', $photo->storage_disk);
        Storage::disk('local')->assertExists($photo->path);
        Storage::disk('public')->assertMissing($photo->path);

        $this->actingAs($owner)->get("/orders/{$order->id}/photos/{$photo->id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_photo_upload_rejects_non_image_content_with_image_extension(): void
    {
        Storage::fake('local');
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-a']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);

        $this->actingAs($owner)
            ->from(route('orders.show', $order))
            ->post("/orders/{$order->id}/photos", [
                'photo' => UploadedFile::fake()->createWithContent('payload.jpg', '<script>alert(1)</script>'),
                'category' => 'damage',
            ])
            ->assertSessionHasErrors('photo');

        $this->actingAs($owner)
            ->from(route('orders.show', $order))
            ->post("/orders/{$order->id}/photos", [
                'photo' => UploadedFile::fake()->image('too-large.jpg')->size(10241),
                'category' => 'damage',
            ])
            ->assertSessionHasErrors('photo');

        $this->assertSame(0, $order->photos()->count());
    }

    public function test_owner_can_edit_and_archive_photo_without_exposing_archived_stream(): void
    {
        Storage::fake('local');
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-photo-management']);
        Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14), 'max_users' => 3]);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);
        $photo = $order->photos()->create(['organization_id' => $organization->id, 'path' => 'organizations/1/orders/1/photo.jpg', 'storage_disk' => 'local', 'category' => 'before', 'visible_to_client' => false]);
        Storage::disk('local')->put($photo->path, UploadedFile::fake()->image('photo.jpg')->getContent());

        $this->actingAs($owner)
            ->put("/orders/{$order->id}/photos/{$photo->id}", ['category' => 'after', 'caption' => 'После ремонта', 'visible_to_client' => true])
            ->assertRedirect();
        $this->assertDatabaseHas('order_photos', ['id' => $photo->id, 'category' => 'after', 'caption' => 'После ремонта', 'visible_to_client' => true]);

        $this->delete("/orders/{$order->id}/photos/{$photo->id}")->assertRedirect();
        $this->assertSoftDeleted('order_photos', ['id' => $photo->id]);
        $this->get("/orders/{$order->id}/photos/{$photo->id}")->assertNotFound();
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_public_photo_stream_requires_valid_token_and_client_visibility(): void
    {
        Storage::fake('local');
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-public-photo']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);
        $token = Str::random(64);
        PublicOrderToken::create(['organization_id' => $organization->id, 'order_id' => $order->id, 'token_hash' => hash('sha256', $token)]);
        $visible = $order->photos()->create(['organization_id' => $organization->id, 'path' => 'visible/photo.jpg', 'storage_disk' => 'local', 'category' => 'before', 'visible_to_client' => true]);
        $hidden = $order->photos()->create(['organization_id' => $organization->id, 'path' => 'private/photo.jpg', 'storage_disk' => 'local', 'category' => 'damage', 'visible_to_client' => false]);
        Storage::disk('local')->put($visible->path, UploadedFile::fake()->image('visible.jpg')->getContent());
        Storage::disk('local')->put($hidden->path, UploadedFile::fake()->image('hidden.jpg')->getContent());

        $this->get(route('orders.public.photos.show', ['token' => $token, 'photo' => $visible->id]))->assertOk();
        $this->get(route('orders.public.photos.show', ['token' => $token, 'photo' => $hidden->id]))->assertNotFound();
        $this->get(route('orders.public.photos.show', ['token' => Str::random(64), 'photo' => $visible->id]))->assertNotFound();
    }

    public function test_client_photo_stream_requires_ownership_and_visibility(): void
    {
        Storage::fake('local');
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-portal-photo']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);
        $photo = $order->photos()->create(['organization_id' => $organization->id, 'path' => 'portal/photo.jpg', 'storage_disk' => 'local', 'category' => 'after', 'visible_to_client' => true]);
        Storage::disk('local')->put($photo->path, UploadedFile::fake()->image('portal.jpg')->getContent());
        $portalUser = User::factory()->create(['organization_id' => $organization->id, 'role' => 'client']);
        $client->portalUsers()->attach($portalUser->id, ['organization_id' => $organization->id]);
        $unrelatedClient = Client::create(['organization_id' => $organization->id, 'name' => 'Другой клиент', 'phone' => '+77000000000']);
        $unrelatedUser = User::factory()->create(['organization_id' => $organization->id, 'role' => 'client']);
        $unrelatedClient->portalUsers()->attach($unrelatedUser->id, ['organization_id' => $organization->id]);

        $this->actingAs($portalUser)->get(route('client-portal.photos.show', $photo))->assertOk();
        $this->actingAs($unrelatedUser)->get(route('client-portal.photos.show', $photo))->assertNotFound();
    }

    public function test_legacy_public_order_photos_can_be_migrated_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $organization = Organization::create(['name' => 'Garage A', 'slug' => 'garage-legacy-photo']);
        $client = Client::create(['organization_id' => $organization->id, 'name' => 'Иван Петров', 'phone' => '+77001234567']);
        $car = Car::create(['organization_id' => $organization->id, 'client_id' => $client->id, 'make' => 'Toyota', 'model' => 'Camry']);
        $status = OrderStatus::create(['organization_id' => $organization->id, 'name' => 'Новый', 'slug' => 'new']);
        $order = Order::create(['organization_id' => $organization->id, 'number' => 'ЗН-00001', 'client_id' => $client->id, 'car_id' => $car->id, 'status_id' => $status->id, 'opened_at' => now()]);
        $photo = $order->photos()->create(['organization_id' => $organization->id, 'path' => 'legacy/photo.jpg', 'storage_disk' => 'public', 'category' => 'before', 'visible_to_client' => true]);
        Storage::disk('public')->put($photo->path, UploadedFile::fake()->image('legacy.jpg')->getContent());

        $this->artisan('photos:secure-migrate')->expectsOutput('Moved 1 photos to private storage.')->assertSuccessful();

        $this->assertSame('local', $photo->refresh()->storage_disk);
        Storage::disk('local')->assertExists($photo->path);
        Storage::disk('public')->assertMissing($photo->path);
    }
}
