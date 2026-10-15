<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_payment_is_recorded_once_and_refund_is_removed_from_revenue(): void
    {
        $admin = User::factory()->create(['organization_id' => null, 'role' => 'platform_admin']);
        $organization = Organization::create(['name' => 'Paid Garage', 'slug' => 'paid-garage']);
        $subscription = Subscription::create(['organization_id' => $organization->id, 'plan' => 'start', 'status' => 'active', 'max_users' => 3]);

        $this->actingAs($admin)->post('/admin/payments', [
            'organization_id' => $organization->id,
            'subscription_id' => $subscription->id,
            'amount' => 10000,
            'method' => 'kaspi',
            'status' => 'paid',
            'reference' => 'SAAS-PAYMENT-001',
        ])->assertRedirect();

        $payment = SaasPayment::where('reference', 'SAAS-PAYMENT-001')->firstOrFail();
        $this->assertDatabaseHas('saas_revenues', ['payment_id' => $payment->id, 'amount' => 10000]);

        $this->actingAs($admin)->post('/admin/payments', [
            'organization_id' => $organization->id,
            'amount' => 20000,
            'method' => 'bank_transfer',
            'status' => 'pending',
            'description' => 'Ожидаемый перевод',
        ])->assertRedirect();
        $pending = SaasPayment::where('status', 'pending')->firstOrFail();
        $this->assertDatabaseMissing('saas_revenues', ['payment_id' => $pending->id]);

        $this->actingAs($admin)->post("/admin/payments/{$pending->id}/status", ['status' => 'paid'])->assertRedirect();
        $this->assertDatabaseHas('saas_revenues', ['payment_id' => $pending->id, 'amount' => 20000]);
        $this->assertDatabaseCount('saas_revenues', 2);

        $this->actingAs($admin)->post("/admin/payments/{$payment->id}/status", ['status' => 'failed'])->assertSessionHasErrors('status');
        $this->assertSame('paid', $payment->fresh()->status);
        $this->actingAs($admin)->post("/admin/payments/{$payment->id}/status", ['status' => 'refunded'])->assertRedirect();
        $this->actingAs($admin)->get('/admin/finance?period=year')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('metrics.revenue', 20000));
        $this->assertDatabaseHas('activity_logs', ['action' => 'saas.payment.refunded', 'user_id' => $admin->id]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_tenant_user_cannot_add_saas_payment(): void
    {
        $organization = Organization::create(['name' => 'Tenant Garage', 'slug' => 'tenant-payment']);
        $owner = User::factory()->create(['organization_id' => $organization->id, 'role' => 'owner']);

        $this->actingAs($owner)->post('/admin/payments', [])->assertForbidden();
    }
}