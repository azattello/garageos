# SAAS Admin Architecture Audit

**Дата:** 2026-10-08  
**Область:** `GarageOS-app` текущая архитектура перед расширением `/admin`  
**Статус:** Phase 0 завершён; аудит не меняет runtime-код.

## Краткий вывод

GarageOS уже содержит tenant organizations, users/roles, подписки на два тарифных плана, feature gating, заявки на смену тарифа и activity log. Super Admin `/admin` уже существует, но пока покрывает обзор организаций, просмотр одной организации, блокировку, продление trial и ручное рассмотрение заявок на тариф.

Модели `Payment` и `Expense` принадлежат финансам автосервиса. Их нельзя использовать как SaaS-выручку или расходы GarageOS. Отдельных SaaS-платежей, SaaS-доходов/расходов и support tickets сейчас нет. Планов как записей БД нет: `PlanCatalog` задаёт ровно `start` и `business` в коде.

## Изученные подсистемы

### Database и модели

- `organizations`: имя, уникальный slug, контакты, timezone, валюта и статус. Связи с users, subscription, clients, orders и расходами организации.
- `users`: nullable `organization_id`, `role`, `status`, `last_login_at`, email/phone. `User::isPlatformAdmin()` возвращает true только при `role=platform_admin` и `organization_id=null`.
- `subscriptions`: organization, строковый plan/status, max_users, даты trial/start/end. Отдельной таблицы plans нет.
- `subscription_change_requests`: организация, текущий/запрошенный plan, quote, статус, requester/reviewer, уникальный payment reference, review note и даты.
- `payments`: обязательные organization и order, сумма/метод/status/comment/received_by/paid_at. Это платежи клиентов СТО по заказ-нарядам, не платежи клиентов GarageOS.
- `expenses` и `expense_categories`: обязательный organization scope; затраты автосервиса. Не являются затратами GarageOS.
- `activity_logs`: nullable organization/user, action, morph subject, JSON metadata, IP, user agent, timestamp. Подходит для расширения system/admin audit trail; отдельной таблицы audit logs нет.
- В просмотренных миграциях и моделях не найдены SaaS revenue/expense/payment, support ticket, impersonation session или системная таблица настроек.

### Plans и subscription lifecycle

`App\Services\Billing\PlanCatalog` содержит два плана:

- Start: 39 900 KZT/месяц, максимум 3 сотрудника, core GarageOS features.
- Business: 69 900 KZT/месяц, максимум 10 сотрудников, core features плюс inventory, expenses, advanced analytics, financial reports и employee workload.

Feature access проверяется серверным middleware `feature` и `Subscription::hasFeature()`. Trial/paid доступ проверяется middleware `trial`; истечение обрабатывает `ExpireSubscription`. Владелец организации создаёт одну pending-заявку на смену тарифа. Super Admin после ручной сверки reference подтверждает или отклоняет её. Подтверждение меняет тариф/срок подписки, проверяет лимит сотрудников, работает в DB transaction и пишет событие в `activity_logs`.

Текущий код не поддерживает самостоятельное автопродление, acquiring, отдельную сущность SaaS-платежа, произвольные тарифы или редактирование plan из интерфейса. Плановый экран Super Admin должен показывать реальные два тарифа как read-only, пока каталог остаётся кодовой конфигурацией; редактирование через временную запись в другой модели создаст рассинхронизацию с feature middleware.

### Authentication, authorization и tenant isolation

- Приложение использует Laravel auth/session и CSRF web middleware.
- Admin routes находятся в `auth` группе. `AdminController` дополнительно проверяет `isPlatformAdmin()` для index/detail; мутации используют Gate `managePlatform` либо policy `review`.
- `OrganizationPolicy::managePlatform()` и `SubscriptionChangeRequestPolicy::review()` требуют платформенную роль.
- `EnsureActiveAccount` отключает неактивного пользователя. `EnsureActiveSubscription` пропускает платформенного администратора из tenant billing checks.
- Tenant finance, analytics и бизнес-запросы фильтруются по `organization_id`; нельзя использовать их глобальные таблицы в SaaS-аналитике.
- Login сохраняет `last_login_at`; logout/login пока не записываются как audit events.

### Routes и текущие admin screens

Текущие платформенные маршруты: `/admin`, `/admin/organizations/{organization}`, смена organization status, продление trial и review subscription request. Отдельных страниц/маршрутов organizations list, users, plans, subscriptions, SaaS payments, SaaS finance, SaaS analytics, support, audit logs и system settings пока нет.

`Admin/Index` показывает агрегаты organizations/active/trialing/users, ожидающие plan requests и пагинированный список организаций без поисковых фильтров. `Admin/Show` показывает профиль, users и агрегаты clients/orders/order revenue. Общий `AppLayout` уже распознаёт отдельную роль платформенного администратора, но Super Admin navigation пока состоит из ссылки на dashboard.

### Existing business finance и visualization

`FinanceController` считает доход организации из успешных `payments`, расходы из tenant `expenses` и отдаёт trend/category series. Это операционная аналитика автосервиса. В frontend доступны Visx-модули; отдельный chart dependency добавлять не требуется. Подходящие SaaS charts должны получать данные из SaaS-level моделей.

## Найденные пробелы и решения реализации

1. **Organizations:** добавить server-side поиск/фильтры/пагинацию и отдельный список; расширить organization detail owner/usage/subscription/activity; создание через существующую organization registration/setup логику, не дублируя model.
2. **Users:** отдельный глобальный список с organization/role/status/search фильтрами и пагинацией; изменение роли только через серверную allowlist и audit event.
3. **Plans:** показывать PlanCatalog и доступность/лимиты read-only. Изменение цен/features не реализовывать через неподходящее хранилище; для динамических plans потребуется отдельное согласованное изменение архитектуры billing.
4. **Subscriptions:** отдельный list и server-side actions для поддерживаемых состояний. Все связанные изменения делать транзакционно, повторно проверять target/seat limit и писать activity events. Не заявлять recurring billing.
5. **SaaS payments/revenue/expenses:** добавить изолированные SaaS-level записи и категории, не привязывая их к `Order`, `Payment` или tenant `Expense`. При ручном SaaS payment связывать запись с organization/subscription; сделать ввод/список и audit.
6. **Metrics:** считать из SaaS subscriptions/revenue/expenses и PlanCatalog. Не выводить churn/new/expansion/contraction MRR без надёжной исторической базы. Документировать вычислимые определения.
7. **Audit logs:** переиспользовать `activity_logs`; добавить системные события, фильтрацию/пагинацию и IP/actor/target/organization rendering, не записывать пароли или секреты.
8. **Support:** добавить архитектурно готовую заглушку без тикетной БД/сложного workflow на этом этапе.
9. **Settings:** не отображать секреты. Любые сохраняемые runtime settings должны иметь явную модель/валидацию, audit и понятные эффекты; не подменять это произвольной записью в `.env`.
10. **Security/QA:** вся защита на backend; проверки normal user/tenant/platform role, tenant isolation, duplicate payment reference, transactions, financial arithmetic, pagination/filters, frontend build и миграции.

## Проверяемые исходные ориентиры

- Routes: `GarageOS-app/routes/web.php`
- Admin controllers: `GarageOS-app/app/Http/Controllers/AdminController.php`
- Roles/tenant relations: `GarageOS-app/app/Models/User.php`, `Organization.php`
- Subscription: `GarageOS-app/app/Models/Subscription.php`, `SubscriptionChangeRequest.php`
- Plan definitions: `GarageOS-app/app/Services/Billing/PlanCatalog.php`
- Authorization: `GarageOS-app/app/Policies/OrganizationPolicy.php`, `SubscriptionChangeRequestPolicy.php`, `GarageOS-app/app/Providers/AppServiceProvider.php`
- SaaS-vs-tenant finance boundary: `GarageOS-app/app/Models/Payment.php`, `Expense.php`; `GarageOS-app/app/Http/Controllers/FinanceController.php`
- Audit storage: `GarageOS-app/app/Models/ActivityLog.php` and migration `2026_09_19_000013_create_activity_logs_table.php`
- Existing admin UI: `GarageOS-app/resources/js/Pages/Admin/Index.tsx`, `Show.tsx`
- Existing billing tests: `GarageOS-app/tests/Feature/AdminTest.php`, `AdminDetailTest.php`, `BillingLifecycleTest.php`

## Ограничение окружения

Каталог workspace и `GarageOS-app` не распознаются как Git-репозиторий в текущем окружении; `git status` недоступен. Перед изменениями состояние файлов сверялось через чтение текущего workspace. Миграции являются источником схемы; проверку runtime DB следует выполнять на настроенном приложении при QA, не меняя существующую пользовательскую базу.

## Реализация после Phase 0

### Выполнено

- Добавлен общий `platform-admin` middleware для всех admin routes. Доступ требует platform role и отсутствия `organization_id`; tenant tests проверяют также попытку изменить собственную роль на `platform_admin`.
- Для БД без Super Admin добавлена CLI provisioning команда `php artisan platform-admin:create`: имя/email валидируются, пароль (12+ символов) вводится скрыто/подтверждается и не выводится в web или logs. Web self-registration для platform role отсутствует; default admin credentials не создаются.
- Добавлена отдельная responsive `AdminLayout` и навигация: Overview, Organizations, Users, Plans, Subscriptions, Payments, SaaS Finance, Analytics, Support, Audit Logs, Settings. Текущие admin pages больше не используют tenant workspace shell.
- Добавлен organizations directory с search/status/plan/subscription/date filters и pagination; показаны владелец, subscription, usage и даты. Organization detail расширен owner, usage, текущими и ожидающими SaaS-платежами и activity.
- Onboarding Super Admin переиспользует существующий `RegisterOrganization`, выбирает Start/Business и trial period, создаёт стандартные tenant catalog records, генерирует случайный пароль и отправляет владельцу стандартную ссылку установки пароля. Архивирование меняет status, не удаляя данные; archived tenant теряет доступ.
- Добавлен глобальный users directory с фильтрами и pagination. Role/status mutations ограничены текущими tenant roles, проверяют seat limit, запрещают выдавать platform role и логируются.
- Добавлена read-only страница двух текущих планов из `PlanCatalog`, с included/excluded feature list и лимитами. Безопасное редактирование price/features пока не реализуется, так как catalog статичен и напрямую управляет feature gates.
- Добавлена subscriptions directory, пагинация plan-change requests и ручные activation/extend/suspend/reactivate/cancel/change-plan actions. Status mutations транзакционны и audit-логируются; активация/перевыпуск после истечения требуют совпадающий paid SaaS payment reference.
- Добавлены изолированные `saas_payments`, `saas_revenues`, `saas_expenses`, `saas_settings`; tenant `payments`/`expenses` не используются и не изменялись для SaaS отчетности.
- Платёжный workflow поддерживает pending/paid/failed/refunded. Paid payment создаёт revenue в той же транзакции, refunded исключается из realized revenue, duplicate references защищены; paid нельзя поменять на failed.
- SaaS Finance поддерживает ручные revenue/expense entries, categories, date range, пагинацию ledger, revenue/expense/net totals и Bklit charts для trends/categories/plans.
- Dashboard показывает MRR/ARR/ARPU, revenue/expenses/net month/year, organization/subscription counts, new organizations/subscriptions, plan distribution, recent tenants и activity. MRR/ARPU и платящее plan distribution учитывают только действующие подписки с подтверждённым SaaS payment. Churn rate не показывается.
- Current paid subscriptions агрегируются SQL-группировкой по plan, а не загружаются целиком в память; набор плана ограничен существующим `PlanCatalog`.
- Analytics имеет единый Today/7d/30d/90d/year/custom date filter, growth series, finance trends, plan/revenue-by-plan distribution и usage aggregates.
- Activity log переиспользуется для audit UI и платформенных login/logout events; доступны actor/action/target/organization/metadata/IP/User-Agent и filters/pagination. Support реализован как foundation placeholder без фиктивных ticket records.
- Settings позволяют менять только несекретные support contacts; обновления валидируются и логируются. Экран показывает read-only system/billing summary, не отображает payment/mail/config secrets.
- Добавлен `design/ADMIN_DESIGN.md` с навигацией, таблицами, состояниями, фильтрами, charts, dialogs и accessibility conventions.

### Сознательно отложено

- Редактирование тарифов требует миграции `PlanCatalog` в управляемое хранилище с versioning/совместимостью feature gates; сейчас остаются ровно два текущих тарифа.
- Исторические MRR/new/expansion/contraction/churn rate не вычисляются: subscription хранит текущую запись без смен статусов/тарифов во времени и без исторических billing periods. Показываются только данные с надёжным источником.
- Отдельные invoice documents/numbering не реализованы: архитектура пока хранит payment reference, но не инвойсы.
- Автоматический acquiring, payment gateway abstraction и webhooks не включены; billing остаётся ручным и не притворяется recurring billing.
- Support ticket workflow и storage оставлены выключенными; экран описывает будущие поля/statuses.
- Impersonation не реализована; она не нужна для нынешней эксплуатации и потребует отдельной безопасной сессии/явного audit flow.
- Прочие runtime settings (maintenance mode, notification providers, security secrets) не редактируются из UI; доступно только безопасное управление контактами поддержки.

### Проверки реализации

- Полный `php artisan test`: 141 tests passed, 1 690 assertions passed.
- Две новые SaaS migrations применены к локальной development DB; итоговый `php artisan migrate:status` показывает их как `Ran`, остальные старые миграции также `Ran`.
- SaaS Dashboard и Analytics имеют отдельные feature tests с конкретной арифметикой, date filters, paid MRR gate и tenant isolation.
- Все 11 admin Inertia routes проходят render smoke test на чистой test database.
- Финальный `npm run build` завершился успешно.
- `get_errors` для затронутых admin PHP/TSX файлов на момент проверки не сообщил ошибок.
- В браузере tenant-сессия получила `403 Forbidden` при прямом GET `/admin`.