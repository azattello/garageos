<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_page_is_public_and_exposes_read_only_summary(): void
    {
        $this->seed(DemoSeeder::class);

        $response = $this->get('/demo');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Demo/Index')
            ->where('organization.name', 'AutoService Demo')
            ->where('metrics.clients', 50)
            ->where('metrics.orders', 30)
            ->has('orders', 8)
            ->missing('orders.0.internal_comment')
            ->missing('orders.0.created_by'));
    }

    public function test_demo_requires_seed_data_instead_of_mutating_database(): void
    {
        $this->get('/demo')->assertNotFound();
    }
}
