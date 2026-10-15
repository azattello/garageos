<?php

namespace Database\Seeders;

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

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Organization::where('slug', 'auto-service-demo')->delete();

        $organization = Organization::create([
            'name' => 'AutoService Demo',
            'slug' => 'auto-service-demo',
            'phone' => '+7 700 000 00 00',
            'email' => 'demo@GarageOS.local',
            'timezone' => 'Asia/Almaty',
            'currency' => 'KZT',
            'status' => 'active',
        ]);

        Subscription::create([
            'organization_id' => $organization->id,
            'plan' => 'business',
            'status' => 'active',
            'max_users' => 10,
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->addDays(335),
        ]);

        $mechanics = collect(['Азамат Нурланов', 'Данияр Сеитов', 'Марат Омаров', 'Илья Волков', 'Руслан Ким', 'Ерлан Ахметов', 'Сергей Ли', 'Тимур Беков'])
            ->map(fn (string $name, int $index) => User::create([
                'organization_id' => $organization->id,
                'name' => $name,
                'email' => 'demo-mechanic-'.($index + 1).'@GarageOS.local',
                'role' => 'mechanic',
                'status' => 'active',
                'password' => Hash::make(Str::random(32)),
            ]));

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
            ['name' => 'Развал-схождение', 'price' => 28000, 'duration_minutes' => 60],
            ['name' => 'ТО 60 000 км', 'price' => 120000, 'duration_minutes' => 240],
            ['name' => 'Ремонт электрики', 'price' => 45000, 'duration_minutes' => 120],
            ['name' => 'Замена свечей', 'price' => 16000, 'duration_minutes' => 45],
            ['name' => 'Замена колодок', 'price' => 32000, 'duration_minutes' => 90],
            ['name' => 'Ремонт кондиционера', 'price' => 70000, 'duration_minutes' => 180],
            ['name' => 'Керамическое покрытие', 'price' => 180000, 'duration_minutes' => 480],
            ['name' => 'Диагностика подвески', 'price' => 12000, 'duration_minutes' => 45],
            ['name' => 'Замена ремня ГРМ', 'price' => 95000, 'duration_minutes' => 300],
            ['name' => 'Мойка двигателя', 'price' => 18000, 'duration_minutes' => 60],
        ])->map(fn (array $service) => Service::create([...$service, 'organization_id' => $organization->id, 'active' => true]));

        $statuses = collect([
            ['slug' => 'new', 'name' => 'Новый', 'sort_order' => 1],
            ['slug' => 'diagnostics', 'name' => 'Диагностика', 'sort_order' => 2],
            ['slug' => 'in_progress', 'name' => 'В работе', 'sort_order' => 3],
            ['slug' => 'waiting_parts', 'name' => 'Ожидание запчастей', 'sort_order' => 4],
            ['slug' => 'ready', 'name' => 'Готов', 'sort_order' => 5],
            ['slug' => 'delivered', 'name' => 'Выдан', 'sort_order' => 6],
            ['slug' => 'closed', 'name' => 'Закрыт', 'sort_order' => 7],
        ])->map(fn (array $status) => OrderStatus::create([...$status, 'organization_id' => $organization->id, 'is_terminal' => in_array($status['slug'], ['closed'], true)]));

        $makes = [['Toyota', 'Camry'], ['BMW', 'X5'], ['Lexus', 'RX 350'], ['Mercedes-Benz', 'E-Class'], ['Hyundai', 'Tucson'], ['Kia', 'Sportage'], ['Volkswagen', 'Tiguan'], ['Audi', 'Q7']];
        $clients = collect();

        for ($index = 1; $index <= 50; $index++) {
            $client = Client::create([
                'organization_id' => $organization->id,
                'name' => ['Алексей', 'Данияр', 'Марат', 'Айжан', 'Николай'][$index % 5].' '.['Иванов', 'Сериков', 'Омаров', 'Ким', 'Петров'][$index % 5],
                'phone' => '+7 7'.str_pad((string) (100000000 + $index), 8, '0', STR_PAD_LEFT),
                'email' => 'demo-client-'.$index.'@GarageOS.local',
                'status' => 'active',
            ]);
            $make = $makes[$index % count($makes)];
            Car::create([
                'organization_id' => $organization->id,
                'client_id' => $client->id,
                'make' => $make[0],
                'model' => $make[1],
                'year' => 2017 + ($index % 8),
                'plate_number' => 'D'.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'KZ',
                'vin' => strtoupper(Str::random(17)),
                'mileage' => 30000 + ($index * 1800),
                'fuel_type' => $index % 3 === 0 ? 'diesel' : 'petrol',
            ]);
            $clients->push($client);
        }

        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Demo Administrator',
            'email' => 'demo-owner@GarageOS.local',
            'role' => 'owner',
            'status' => 'active',
            'password' => Hash::make(Str::random(32)),
        ]);

        for ($index = 1; $index <= 30; $index++) {
            $client = $clients->get(($index - 1) % $clients->count());
            $car = $client->cars()->first();
            $service = $services->get(($index - 1) % $services->count());
            $status = $statuses->get(($index - 1) % 6);
            $total = $service->price + (($index % 4) * 7500);
            $paid = $index % 5 === 0 ? (int) ($total / 2) : $total;
            $order = Order::create([
                'organization_id' => $organization->id,
                'number' => 'DEMO-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'car_id' => $car->id,
                'mechanic_id' => $mechanics->get(($index - 1) % $mechanics->count())->id,
                'status_id' => $status->id,
                'opened_at' => now()->subDays($index),
                'planned_completion_at' => now()->addDays($index % 5),
                'subtotal' => $total,
                'discount' => 0,
                'total' => $total,
                'paid_total' => $paid,
                'balance' => $total - $paid,
                'client_comment' => 'Демо-заказ для просмотра возможностей GarageOS.',
                'created_by' => $owner->id,
            ]);
            OrderItem::create([
                'organization_id' => $organization->id,
                'order_id' => $order->id,
                'type' => 'service',
                'service_id' => $service->id,
                'name_snapshot' => $service->name,
                'quantity' => 1,
                'unit_price' => $total,
                'discount' => 0,
                'total' => $total,
            ]);
        }
    }
}
