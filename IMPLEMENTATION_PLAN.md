# GarageOS — план реализации

## 1. Цель первого релиза

MVP должен позволить новому автосервису самостоятельно пройти основной цикл:

`Регистрация → организация → услуга → сотрудник → клиент → автомобиль → запись → заказ → работа → статус → фото → оплата → выдача → история`.

В первый релиз входят CRM клиентов и автомобилей, календарь и записи, заказ-наряды, Kanban, сотрудники, услуги, фотографии, платежи, задолженность, dashboard, уведомления, базовая аналитика, настройки, подписка, SaaS-admin и read-only demo. В текущей реализации также присутствуют Business-модули склада, расходов и расширенной аналитики; это расширяет первоначально намеченную границу MVP, но не отменяет проверки production compatibility.

Полноценный клиентский кабинет и внешние каналы коммуникации остаются отдельным scope. Термин «первый релиз» ниже описывает целевые acceptance criteria, а не подтверждение production-релиза.

## 2. Техническое решение

### Backend

- PHP 8.3+ и Laravel 12.
- Eloquent, Form Requests, Policies, Events, Notifications, Scheduler и Storage.
- Controllers только принимают запрос и возвращают Inertia response; бизнес-операции находятся в Actions.
- MySQL 8 / MariaDB через стандартный Laravel MySQL driver.

### Frontend

- React + TypeScript через Inertia.js.
- Tailwind CSS + shadcn/ui infrastructure и локальные GarageOS wrappers.
- Все Dashboard/Analytics/Finance charts используют Bklit UI, установленный как shadcn registry source; workspace skill фиксирует install/composition/theming pattern. Не вводить альтернативные chart libraries без отдельного решения.
- Собственные GarageOS wrappers используются для унификации API, accessibility и визуальных токенов.
- Визуальный контракт приложения зафиксирован в `DESIGN_SYSTEM.md`: dark mode по умолчанию, Inter, Lucide, dense data-driven layout и acid-green accent.
- Сервер остаётся Laravel-приложением; Node нужен только для dev/build.

### Границы модулей

```text
app/
├── Actions/
│   ├── Appointments/
│   ├── Clients/
│   ├── Orders/
│   ├── Payments/
│   └── Organizations/
├── Domain/
│   ├── Orders/
│   ├── Tenancy/
│   └── Subscriptions/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Notifications/
├── Policies/
└── Support/

resources/js/
├── Components/
├── Features/
│   ├── Appointments/
│   ├── Clients/
│   ├── Dashboard/
│   └── Orders/
├── Layouts/
├── Pages/
├── Types/
└── Utils/
```

Структура выше — целевая схема, а не точный inventory текущих директорий. Фактические реализации и отсутствующие части фиксируются в [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## 3. Архитектурные правила

1. Каждый tenant-owned запрос начинается с текущей организации из authenticated user/context. `organization_id` из frontend не принимается как источник истины.
2. Для tenant-owned моделей используются scoped queries и Policies. `Model::find($id)` в application-коде запрещён без tenant scope и authorization.
3. Критические сущности используют archive/soft delete. Платежи и финансовые записи не удаляются физически.
4. Все деньги хранятся в integer minor units или integer KZT; форматирование валюты выполняется только на presentation-слое.
5. Итоги заказа, оплаченная сумма и баланс рассчитываются на backend в транзакции. React не дублирует бизнес-формулы.
6. Исторические позиции заказа получают `name_snapshot`, `unit_price`, `unit_cost` и `total` при добавлении.
7. Изменение статуса заказа проходит через `ChangeOrderStatus`, проверяет допустимый переход, пишет Activity Log и dispatches event.
8. Публичная ссылка заказа хранит в базе только hash токена. Публичное представление никогда не отдаёт себестоимость, внутренние комментарии и скрытые фотографии.
9. Входные данные валидируются Form Requests; загрузки проверяются по MIME, размеру, расширению и сохраняются через Laravel Storage.
10. Все списки имеют server-side pagination, фильтры и eager loading; стандартная страница — 25 записей.

## 4. Минимальная схема данных

### Foundation

`organizations`, `organization_settings`, `users`, `user_invites`, `subscriptions`, `plans`.

### CRM

`clients`, `client_users`, `cars`, `service_categories`, `services`, `employees`.

### Operations

`appointments`, `appointment_statuses`, `orders`, `order_statuses`, `order_items`, `order_photos`.

### Finance and platform

`payments`, `notifications`, `activity_logs`, `public_order_tokens`.

### Deferred but reserved

`parts`, `part_categories`, `inventory_transactions`, `expense_categories`, `expenses`.

На всех tenant-owned таблицах обязательны `organization_id`, внешние ключи и составные индексы tenant + lookup field. Номера заказов уникальны в пределах организации: `(organization_id, number)`.

## 5. Порядок разработки

### Этап 0 — окружение и каркас

- Установить PHP 8.3+, Composer, расширения `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `zip`.
- Создать Laravel 12, подключить Inertia React, TypeScript, Tailwind и Bklit UI.
- Настроить `.env.example`, MySQL/MariaDB, Vite, Pint, PHPUnit/Pest и базовый CI.
- Добавить единый layout, design tokens, русскую локаль, timezone `Asia/Almaty` и формат KZT.

### Этап 1 — identity, tenant и SaaS foundation

- Registration/login/logout/password reset/password confirmation/rate limiting.
- В транзакции регистрации создавать Organization, Owner, Plan/Subscription и 14-дневный trial.
- Добавить `CurrentOrganization` middleware/context, role enum, policies и tenant-aware base query pattern.
- Реализовать блокировку организации и ограничение доступа после окончания trial.
- Тесты: регистрация, owner role, tenant isolation, platform admin isolation, trial state.

### Этап 2 — клиенты и автомобили

- CRUD/архив клиентов, CRUD автомобилей, профиль клиента и карточка автомобиля.
- Серверный глобальный поиск по имени, телефону, номеру, VIN, марке, модели и номеру заказа.
- История автомобиля строится из закрытых заказов, а не из дублируемой таблицы.
- Тесты: связи client → cars → orders и запрет доступа к чужому tenant.

### Этап 3 — услуги, сотрудники, записи и календарь

- CRUD услуг и категорий, сотрудники и специализации.
- Appointment actions, статусы, day/week calendar и проверка пересечений мастера.
- Из записи создавать заказ без дублирования клиента и автомобиля.
- Тесты: conflict detection, роли, переход appointment → order.

### Этап 4 — заказ-наряд как центральный vertical slice

- Создание заказа из клиента, автомобиля, записи и напрямую.
- Order items для работ, назначение мастера и исполнителей.
- Backend total calculator: subtotal, discount, total, successful payments, balance.
- Базовые статусы и допустимые переходы в базе; Kanban с backend-перемещением.
- Activity Log и domain events: `OrderCreated`, `OrderStatusChanged`.
- Тесты формул, transition rules, concurrency/transaction и audit.

### Этап 5 — фотографии, оплаты и публичная страница

- Фото заказа/автомобиля, категории, caption, visibility и preview.
- Несколько платежей, методы KZT, `successful/cancelled/pending`, отмена без физического удаления.
- Защищенная public order link с hash token и отзывом токена.
- Client-safe DTO/resource, который исключает внутренние поля на уровне запроса и ресурса.
- Тесты: скрытое фото, баланс после отмены платежа, token expiry/revoke, отсутствие cost/internal comment.

### Этап 6 — dashboard, уведомления и аналитика

- KPI: машины сегодня, активные/готовые/просроченные заказы, выручка, новые клиенты, долг.
- Activity feed, in-app notifications, unread/read state.
- Базовые агрегаты revenue/orders/average check за today/7/30/custom period.
- Skeleton, empty, error, permission denied и success feedback для ключевых экранов.

### Этап 7 — SaaS admin, demo и production readiness

- `/admin`: organizations, users, subscriptions, activity и действия блокировки/продления trial.
- `/demo`: seeded read-only organization и banner DEMO MODE.
- Безопасность загрузок, audit auth events, backup/restore runbook, queue/scheduler, logging.
- Responsive/mobile navigation, keyboard access, reduced motion и accessibility pass.

## 6. Первый acceptance test

```text
Register
→ Create service
→ Create employee
→ Create client
→ Create car
→ Create appointment
→ Create order
→ Add work
→ Assign mechanic
→ Move order to In Progress
→ Upload client-visible photo
→ Register payment
→ Move order to Ready
→ Mark Delivered and Closed
→ Open car history
→ Verify dashboard KPI
```

Этот сценарий должен проходить в feature/E2E тестах и вручную в demo-like локальном окружении.

## 7. Definition of Done для каждого модуля

- migration/model/factory/seed при необходимости;
- Form Request и Policy;
- Action/service с транзакцией для изменения состояния;
- Inertia page и responsive states: normal/loading/empty/error/success/validation/permission denied;
- activity/event для критического действия;
- unit + feature tests для бизнес-правил;
- tenant isolation test;
- отсутствие N+1 и console errors;
- desktop, mobile и keyboard smoke-check.

## 8. Актуальное состояние и следующий план (07.10.2026)

Разделы 1–7 описывают исходную целевую архитектуру и acceptance criteria; они не являются доказательством того, что каждый пункт уже реализован. Проверенный статус на 07.10.2026: Laravel 12/PHP 8.3, React/Inertia, tenant/RBAC, CRM, appointments, orders, payments, private photos, portal/public order, inventory, finance, billing и базовый SaaS Admin реализованы в разной степени; подробная матрица находится в [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

Актуальные оставшиеся задачи:

1. Согласовать client identity при общих phone/email; сейчас duplicate lookup автоматически переиспользует профиль по совпадению любого поля.
2. Перед внешним запуском выполнить MySQL/MariaDB staging migration, critical workflow smoke-test и backup/restore rehearsal.
3. В следующем продуктовом цикле решить business hours календаря, ручное переназначение сотрудника и accounting basis для CSV.
4. Долгосрочно: SaaS Admin user management/MRR, филиалы, granular permissions, онлайн-запись и интеграции.

Проверка 07.10.2026 после реализации backlog-среза: `php artisan test` — 114 passed / 1220 assertions; TypeScript и Vite production build проходят; `composer validate --no-check-publish` успешен; `composer audit` не находит advisories. Все 34 SQLite migrations применены; MySQL gate не подтверждён, потому что сервер недоступен и Docker daemon выключен.

Функциональные инструкции и подтверждённые пользовательские сценарии находятся в [GarageOS_PRODUCT_GUIDE.md](GarageOS_PRODUCT_GUIDE.md). Согласования и долгосрочный backlog — в [GarageOS_OPEN_DECISIONS.md](GarageOS_OPEN_DECISIONS.md); текущие приоритеты — в [GarageOS_REFINEMENT_ROADMAP.md](GarageOS_REFINEMENT_ROADMAP.md). Объективный процент готовности не используется: критерии и вес функций не определены.