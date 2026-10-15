<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AutoSvetDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'qyran-avtosvet')->firstOrFail();

        Subscription::updateOrCreate(
            ['organization_id' => $organization->id],
            [
                'plan' => 'business',
                'status' => 'active',
                'max_users' => 15,
                'starts_at' => now()->subDays(35),
                'ends_at' => now()->addDays(330),
            ]
        );

        $mechanics = collect([
            'Азамат Нурланов',
            'Данияр Сеитов',
            'Марат Омаров',
            'Илья Волков',
            'Руслан Ким',
            'Ерлан Ахметов',
            'Сергей Ли',
            'Тимур Беков',
        ])->map(function (string $name, int $index) use ($organization) {
            $email = 'demo-mechanic-' . ($index + 1) . '@GarageOS.local';

            return User::updateOrCreate(
                ['organization_id' => $organization->id, 'email' => $email],
                [
                    'name' => $name,
                    'role' => 'mechanic',
                    'status' => 'active',
                    'password' => Hash::make('password123'),
                ]
            );
        });

        $services = collect([
            ['name' => 'Замена масла', 'price' => 18000, 'duration_minutes' => 60],
            ['name' => 'Диагностика двигателя', 'price' => 25000, 'duration_minutes' => 90],
            ['name' => 'Тормозная система', 'price' => 42000, 'duration_minutes' => 120],
            ['name' => 'Ремонт ходовой', 'price' => 65000, 'duration_minutes' => 180],
            ['name' => 'Компьютерная диагностика', 'price' => 15000, 'duration_minutes' => 45],
            ['name' => 'Шиномонтаж', 'price' => 22000, 'duration_minutes' => 60],
            ['name' => 'Кондиционер', 'price' => 30000, 'duration_minutes' => 90],
            ['name' => 'Полировка кузова', 'price' => 85000, 'duration_minutes' => 240],
            ['name' => 'Детейлинг салона', 'price' => 55000, 'duration_minutes' => 180],
            ['name' => 'Замена аккумулятора', 'price' => 12000, 'duration_minutes' => 30],
        ])->map(function (array $service) use ($organization) {
            return Service::updateOrCreate(
                ['organization_id' => $organization->id, 'name' => $service['name']],
                [
                    'description' => 'Демо-услуга для автосервиса',
                    'price' => $service['price'],
                    'duration_minutes' => $service['duration_minutes'],
                    'active' => true,
                ]
            );
        });

        $orderStatuses = collect([
            ['slug' => 'new', 'name' => 'Новый', 'sort_order' => 1],
            ['slug' => 'diagnostics', 'name' => 'Диагностика', 'sort_order' => 2],
            ['slug' => 'in_progress', 'name' => 'В работе', 'sort_order' => 3],
            ['slug' => 'waiting_parts', 'name' => 'Ожидание запчастей', 'sort_order' => 4],
            ['slug' => 'ready', 'name' => 'Готов', 'sort_order' => 5],
            ['slug' => 'delivered', 'name' => 'Выдан', 'sort_order' => 6],
            ['slug' => 'closed', 'name' => 'Закрыт', 'sort_order' => 7],
        ])->map(function (array $status) use ($organization) {
            return OrderStatus::updateOrCreate(
                ['organization_id' => $organization->id, 'slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'is_terminal' => $status['slug'] === 'closed',
                ]
            );
        });

        $appointmentStatuses = collect([
            ['slug' => 'pending', 'name' => 'Ожидает'],
            ['slug' => 'confirmed', 'name' => 'Подтверждена'],
            ['slug' => 'arrived', 'name' => 'Клиент приехал'],
            ['slug' => 'completed', 'name' => 'Завершена'],
            ['slug' => 'cancelled', 'name' => 'Отменена'],
        ])->map(function (array $status) use ($organization) {
            return AppointmentStatus::updateOrCreate(
                ['organization_id' => $organization->id, 'slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => ['pending' => 1, 'confirmed' => 2, 'arrived' => 3, 'completed' => 4, 'cancelled' => 5][$status['slug']],
                    'is_terminal' => in_array($status['slug'], ['completed', 'cancelled'], true),
                ]
            );
        });

        $clients = collect([
            ['name' => 'Алексей Иванов', 'phone' => '+7 701 111 11 11', 'email' => 'alexey.ivanov@example.com'],
            ['name' => 'Данияр Сериков', 'phone' => '+7 702 222 22 22', 'email' => 'daniyar.serikov@example.com'],
            ['name' => 'Марат Омаров', 'phone' => '+7 703 333 33 33', 'email' => 'marat.omarov@example.com'],
            ['name' => 'Айжан Ким', 'phone' => '+7 704 444 44 44', 'email' => 'aizhan.kim@example.com'],
            ['name' => 'Николай Петров', 'phone' => '+7 705 555 55 55', 'email' => 'nikolay.petrov@example.com'],
            ['name' => 'Ержан Сарсенов', 'phone' => '+7 706 666 66 66', 'email' => 'erzhan.sarsenov@example.com'],
            ['name' => 'Станислав Ли', 'phone' => '+7 707 777 77 77', 'email' => 'stanislav.li@example.com'],
            ['name' => 'Бекзат Иманов', 'phone' => '+7 708 888 88 88', 'email' => 'bekzat.imanov@example.com'],
        ])->map(function (array $clientData) use ($organization) {
            return Client::updateOrCreate(
                ['organization_id' => $organization->id, 'phone' => $clientData['phone']],
                [
                    'name' => $clientData['name'],
                    'email' => $clientData['email'],
                    'status' => 'active',
                    'comment' => 'Демо-клиент автосервиса',
                ]
            );
        });

        $makes = [
            ['Toyota', 'Camry'],
            ['BMW', 'X5'],
            ['Lexus', 'RX 350'],
            ['Mercedes-Benz', 'E-Class'],
            ['Hyundai', 'Tucson'],
            ['Kia', 'Sportage'],
            ['Volkswagen', 'Tiguan'],
            ['Audi', 'Q7'],
        ];

        $cars = $clients->map(function ($client, $index) use ($organization, $makes) {
            $make = $makes[$index % count($makes)];

            return Car::updateOrCreate(
                ['organization_id' => $organization->id, 'plate_number' => 'D' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) . 'KZ'],
                [
                    'client_id' => $client->id,
                    'make' => $make[0],
                    'model' => $make[1],
                    'year' => 2016 + ($index % 8),
                    'vin' => strtoupper(Str::random(17)),
                    'mileage' => 32000 + ($index * 2500),
                    'fuel_type' => $index % 2 === 0 ? 'petrol' : 'diesel',
                    'color' => ['Белый', 'Черный', 'Серый', 'Синий', 'Красный'][$index % 5],
                    'comment' => 'Автомобиль для демо-показов',
                ]
            );
        });

        $owner = User::updateOrCreate(
            ['organization_id' => $organization->id, 'email' => 'owner@qyran-avtosvet.local'],
            [
                'name' => 'Дархан',
                'role' => 'owner',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ]
        );

        for ($i = 1; $i <= 12; $i++) {
            $client = $clients->get(($i - 1) % $clients->count());
            $car = $cars->get(($i - 1) % $cars->count());
            $service = $services->get(($i - 1) % $services->count());
            $status = $orderStatuses->get(($i - 1) % $orderStatuses->count());
            $total = $service->price + (($i % 4) * 7500);
            $paid = $i % 3 === 0 ? (int) ($total / 2) : $total;

            $order = Order::updateOrCreate(
                ['organization_id' => $organization->id, 'number' => 'AUTO-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'client_id' => $client->id,
                    'car_id' => $car->id,
                    'mechanic_id' => $mechanics->get(($i - 1) % $mechanics->count())->id,
                    'status_id' => $status->id,
                    'opened_at' => now()->subDays($i),
                    'planned_completion_at' => now()->addDays(($i % 5) + 1),
                    'subtotal' => $total,
                    'discount' => 0,
                    'total' => $total,
                    'paid_total' => $paid,
                    'balance' => $total - $paid,
                    'client_comment' => 'Демо-заказ для демонстрации системы.',
                    'created_by' => $owner->id,
                ]
            );

            OrderItem::updateOrCreate(
                ['organization_id' => $organization->id, 'order_id' => $order->id, 'service_id' => $service->id],
                [
                    'type' => 'service',
                    'name_snapshot' => $service->name,
                    'quantity' => 1,
                    'unit_price' => $total,
                    'discount' => 0,
                    'total' => $total,
                ]
            );
        }

        $appointmentDates = [
            now()->subDays(1)->setTime(9, 0),
            now()->subDays(1)->setTime(11, 30),
            now()->setTime(10, 0),
            now()->setTime(13, 0),
            now()->addDays(1)->setTime(9, 30),
            now()->addDays(1)->setTime(14, 0),
            now()->addDays(2)->setTime(12, 0),
            now()->addDays(3)->setTime(15, 30),
        ];

        foreach ($appointmentDates as $index => $startAt) {
            $client = $clients->get($index % $clients->count());
            $car = $cars->get($index % $cars->count());
            $service = $services->get($index % $services->count());
            $appointmentStatus = $appointmentStatuses->get($index % $appointmentStatuses->count());

            Appointment::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'client_id' => $client->id,
                    'car_id' => $car->id,
                    'service_id' => $service->id,
                    'start_at' => $startAt,
                ],
                [
                    'mechanic_id' => $mechanics->get($index % $mechanics->count())->id,
                    'status_id' => $appointmentStatus->id,
                    'status' => $appointmentStatus->slug,
                    'end_at' => $startAt->copy()->addMinutes($service->duration_minutes),
                    'comment' => 'Демо-запись для планирования работы',
                    'created_by' => $owner->id,
                ]
            );
        }
    }
}
