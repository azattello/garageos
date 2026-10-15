<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AdminSupportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Support/Index', [
            'ticketFields' => [
                ['key' => 'organization', 'label' => 'Организация'],
                ['key' => 'user', 'label' => 'Пользователь'],
                ['key' => 'priority', 'label' => 'Приоритет'],
                ['key' => 'status', 'label' => 'Статус'],
                ['key' => 'messages', 'label' => 'Сообщения'],
                ['key' => 'created_at', 'label' => 'Создано'],
                ['key' => 'updated_at', 'label' => 'Обновлено'],
            ],
            'statuses' => ['Открыт', 'В работе', 'Ожидает ответа', 'Решён', 'Закрыт'],
            'ticketingEnabled' => false,
        ]);
    }
}