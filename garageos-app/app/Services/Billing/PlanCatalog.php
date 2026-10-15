<?php

namespace App\Services\Billing;

class PlanCatalog
{
    public function all(): array
    {
        $start = [
            'clients', 'cars', 'appointments', 'orders', 'kanban', 'services', 'employees',
            'vehicle_history', 'photos', 'payments', 'debt_control', 'basic_analytics',
            'notifications', 'public_order',
        ];

        return [
            'start' => [
                'key' => 'start',
                'name' => 'Start',
                'price' => 39900,
                'currency' => 'KZT',
                'max_users' => 3,
                'features' => $start,
            ],
            'business' => [
                'key' => 'business',
                'name' => 'Business',
                'price' => 69900,
                'currency' => 'KZT',
                'max_users' => 10,
                'features' => [...$start, 'inventory', 'expenses', 'advanced_analytics', 'financial_reports', 'employee_workload'],
            ],
        ];
    }

    public function get(string $plan): ?array
    {
        return $this->all()[$plan] ?? null;
    }

    public function includes(string $plan, string $feature): bool
    {
        return in_array($feature, $this->get($plan)['features'] ?? [], true);
    }
}
