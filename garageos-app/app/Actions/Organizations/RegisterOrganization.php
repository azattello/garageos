<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AppointmentStatus;
use App\Models\ServiceCategory;
use App\Support\Activity;

class RegisterOrganization
{
    /**
     * @param array{name: string, business_name: string, phone: ?string, email: string, password: string} $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $organization = Organization::create([
                'name' => $data['business_name'],
                'slug' => $this->uniqueSlug($data['business_name']),
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
            ]);

            $owner = $organization->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role' => 'owner',
                'status' => 'active',
            ]);

            $organization->subscription()->create([
                'plan' => 'start',
                'status' => 'trialing',
                'max_users' => 3,
                'trial_ends_at' => now()->addDays(14),
                'starts_at' => now(),
            ]);

            foreach (['ТО', 'Диагностика', 'Двигатель', 'Ходовая', 'Тормоза', 'Электрика', 'Детейлинг', 'Другое'] as $index => $name) {
                $organization->serviceCategories()->create(['name' => $name, 'sort_order' => $index + 1, 'active' => true]);
            }

            foreach ([['pending', 'Ожидает'], ['confirmed', 'Подтверждена'], ['arrived', 'Клиент приехал'], ['completed', 'Завершена'], ['cancelled', 'Отменена']] as $index => [$slug, $name]) {
                $organization->appointmentStatuses()->create(['slug' => $slug, 'name' => $name, 'sort_order' => $index + 1, 'is_terminal' => in_array($slug, ['completed', 'cancelled'], true)]);
            }

            foreach (['Аренда', 'Зарплата', 'Запчасти', 'Расходники', 'Реклама', 'Коммунальные услуги', 'Оборудование', 'Транспорт', 'Другое'] as $index => $name) {
                $organization->expenseCategories()->create(['name' => $name, 'sort_order' => $index + 1, 'active' => true]);
            }

            Activity::record('organization.registered', $organization, $owner);

            return $owner;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'garage';
        $slug = $base;
        $suffix = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}