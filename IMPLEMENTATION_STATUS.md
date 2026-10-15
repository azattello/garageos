# GarageOS — журнал реализации

Последняя полная сверка: 07.10.2026

## Дополнение (08.10.2026) — публичный Landing

- Гостевой `/` показывает маркетинговую страницу GarageOS; авторизованные пользователи продолжают попадать в существующий dashboard через route name `dashboard` и middleware `trial`.
- Hero содержит live iframe read-only `/demo`; добавлены подтверждённые продуктовые возможности, onboarding flow, FAQ и два тарифа из `PlanCatalog`.
- Добавлены responsive/mobile navigation, SEO metadata, canonical URL, reduced-motion support и лендинг-стили, изолированные от SaaS UI.
- Inertia page resolver использует dynamic imports: entry JS production build уменьшен с 513 KB до 8 KB, рабочие страницы собираются отдельно.
- Проверено: landing/auth/billing/trial — 20 тестов, 182 assertions; production build успешен; Playwright подтвердил demo frame, mobile menu и отсутствие переполнения H1/page на ширинах 320–1440 px.

## Актуальный срез (07.10.2026)

Этот срез сверён с Laravel routes/controllers/actions, React pages, migrations и feature tests. Исторические записи ниже описывают состояние на дату выполнения и не являются актуальным backlog.

### Реализовано и подтверждено

- Multi-tenant workspace, регистрация/trial, роли, приглашения сотрудников с принятием/повторной отправкой/отзывом, tenant-scoped CRM, архивирование автомобилей и услуг.
- Записи с day/week views, 30-минутной сеткой начала, автоподстановкой длительности услуги, debounce availability check с inline conflict feedback, keyboard стрелками и touch-swipe переносом; backend остаётся authoritative.
- Заказы, системные переходы статусов, расчёт итогов, платежи/отмена, явное подтверждение owner/manager при закрытии с ненулевым балансом, складской резерв/ledger/low-stock.
- Приватная выдача фотографий, client-visible флаг, public order link с revoke/rotate, read-only клиентский портал.
- Расходы и tenant-scoped отчётность Business, подписки/feature gates, базовое управление организациями в SaaS Admin, read-only Demo Mode.
- Owner-only edit/activate/deactivate сотрудников с audit; отключение запрещено при активных заказах/будущих назначениях, неактивный сотрудник исключён из новых/изменённых записей.
- Полная paginated client timeline по заказам, appointments, позициям работ/запчастей, платежам, фото и status changes; клиентские агрегаты считаются по полной истории.
- Фото: private upload, edit category/caption/visibility, soft-delete archive с audit, keyboard-accessible lightbox и защищённые streams.
- Analytics сравнивает поступления, расходы и операционный итог с предыдущим периодом; daily inflow/outflow chart и tenant-scoped aggregate CSV без PII.
- Графики Dashboard, Analytics и Finance используют официальные Bklit AreaChart/BarChart из shadcn registry; добавлен workspace skill `.agents/skills/bklit-ui` для всех будущих chart-задач.
- Поиск, карточки клиента/автомобиля, Activity Log и уведомления; все 34 миграции локальной SQLite отмечены `Ran`.

### Частично реализовано

- Календарь пока не учитывает график работы/перерывы и не показывает вертикальную сетку занятости по времени; правила business hours требуют согласования.
- При деактивации сотрудника активные назначения не переназначаются автоматически: owner должен переназначить заказ/запись до отключения.
- Analytics CSV содержит дневные агрегаты, но не является транзакционной выгрузкой для бухгалтерского учёта; accounting basis остаётся продуктовым решением.
- SaaS Admin управляет организациями/trial и заявками на тариф, но не предоставляет полный user management, MRR/churn и выгрузки.
- Acceptance workflow покрыт feature tests, но единый браузерный E2E-сценарий целиком и mobile browser smoke-test не подтверждены.

### Не сделано / release gates

- Реальный migration и smoke-test на MySQL 8/MariaDB, staging deploy и backup/restore rehearsal.
- Публичный маркетинговый сайт, филиалы/несколько складов, granular permissions, онлайн-запись и платёжный gateway/recurring billing.
- Email/SMS/WhatsApp автоматизации, полноценное редактирование/удаление строк заказа и расширение клиентского кабинета операционными действиями.
- Production secrets, домен/HTTPS, SMTP, cron, постоянный queue worker, storage permissions и мониторинг задаются на целевом сервере.

### Проверки 07.10.2026

- `php artisan test`: 114 passed, 1220 assertions.
- `npx tsc -p tsconfig.json --noEmit`: проходит после согласования типа клиента со свойством `phone`.
- `npm run build`: production build проходит.
- `composer validate --no-check-publish`: успешно; `composer audit`: advisory не найдены.
- `php artisan migrate`: применены две additive migrations; все 34 migrations имеют статус `Ran` на локальной SQLite.
- MySQL gate не пройден: `127.0.0.1:3306` недоступен, Docker daemon не запущен. Это не эквивалентно миграции на MySQL/MariaDB.
- Проценты «готовности» исключены: у проекта нет определённого знаменателя/acceptance-модели для такой оценки.

## Исторические записи реализации

Записи ниже сохранены как хронология. Их тестовые числа, backlog и формулировки «текущее состояние» относятся к дате той записи и заменены актуальным срезом выше.

## Актуальное состояние на 02.10.2026 (архивный снимок)

- CRM UX: верхние CTA клиентов/услуг подключены; профиль имеет logout menu; организация не выглядит переключаемой без multi-tenant UI.
- Добавлены отдельный `/cars` index с tenant-scoped поиском и pagination, поиск клиентов по контактам/автомобилям и рабочие ссылки global search на карточки.
- Действия из карточки клиента передают выбранного клиента в создание автомобиля/заявки; мастер заявки поддерживает локальный поиск клиента и переход к созданию недостающих данных.
- Заказ: UI отмены успешного платежа с подтверждением и обязательное owner/manager подтверждение `allow_unpaid` для закрытия с долгом.
- Kanban: системные статусы, backend-authoritative allowed transitions, touch/keyboard selector, inline server errors и переходы в карточки заказов.
- Услуги: edit/archive/restore, category/description fields, Activity Log before/after и tenant checks.
- Автомобили: отдельный tenant-scoped index; edit технических полей; обратимое archive/restore с аудитом; запрет архива при активном заказе/заявке; archived cars исключены из новых appointment/order/portal/search выборок, история сохраняется.
- Из карточки авто быстрый переход в создание заказа предвыбирает tenant-owned client и active car.
- Activity Log: owner/manager filters by action/text, actor, entity and date; pagination; sanitized before/after; mechanic does not receive activity data.
- Проверки после текущих изменений: `php artisan test` — 104 passed / 1045 assertions; TypeScript и Vite production build проходят.
- Finance/Inventory: старые runtime ошибки в Laravel log совпадают с отсутствовавшими тогда SQLite migrations. Текущая схема содержит `expenses.deleted_at` и `parts.reserved_stock`; обе страницы проходят Feature tests и подтверждены authenticated browser smoke-check без ошибки.
- Актуальный приоритет разработки — [GarageOS_REFINEMENT_ROADMAP.md](GarageOS_REFINEMENT_ROADMAP.md); продуктовые решения и отложенные функции — [GarageOS_OPEN_DECISIONS.md](GarageOS_OPEN_DECISIONS.md).

## Текущая точка

Проект уже вышел за пределы “каркаса” и находится в рабочем состоянии по основному операционному циклу: регистрация/tenant → клиенты/автомобили → услуги/сотрудники → записи → заказы → payments/photos → public order link → dashboard/notifications/settings. Добавлены SaaS Admin, публичный read-only Demo Mode, tenant-настройки категорий услуг/статусов записей с CRUD, карточки клиента и автомобиля, а также dashboard analytics за 7 дней.

Следующий блок разработки: финальная доводка UX навигации, аудит реального tenant-демо-состояния и подготовка к следующему релизному циклу.

## Что сделано

### Этап 0 — каркас и frontend foundation

- Laravel 12, PHP 8.3+ и Composer в рабочем состоянии.
- React 19 + TypeScript + Inertia.js.
- Tailwind CSS 4.
- shadcn/ui infrastructure.
- Lucide icons.
- Inter font.
- Dark-first GarageOS design system со стилем и токенами для dark mode.
- Русский интерфейс основных application-страниц.
- SQLite используется для локальной разработки; MySQL/MariaDB остаётся production-целями.
- `DESIGN_SYSTEM.md` добавлен как визуальный контракт продукта.

### Этап 1 — identity и tenant foundation

- Регистрация организации.
- Автоматическое создание владельца.
- Trial на 14 дней.
- Подписка Start.
- Tenant-поля пользователя и страховка на organization_id.
- Роли в поле `users.role`.
- Login, logout, remember me.
- Защищённый dashboard.
- Гостевой redirect на именованный `/login`.
- Middleware для `trial`/active subscription guard.
- Точечные auth- и tenant-тесты.

### Этап 2 — клиенты и автомобили

- Таблицы `clients` и `cars`.
- Eloquent-модели `Client` и `Car`.
- Создание клиента/автомобиля.
- Проверка принадлежности клиента и машины текущей организации.
- Soft delete клиентов.
- Список клиентов с пагинацией.
- Подсчёт количества автомобилей клиента.
- История автомобиля по заказам.
- Добавлена tenant-safe карточка клиента `/clients/{client}` с автомобилями, последними заказами, оплатами и долгом.
- Backend рассчитывает сводку клиента из заказов, без формул во frontend.
- Карточка автомобиля `/cars/{car}` расширена техническими данными, активным заказом, финансовой сводкой, историей и фотографиями.
- Добавлена tenant isolation проверка карточки автомобиля.
- Русская CRM-страница.

### Этап 3 — услуги, сотрудники и записи

- Таблицы `services` и `appointments`.
- Создание и список услуг.
- Создание записей.
- Выбор клиента, автомобиля, услуги и мастера.
- Tenant-проверки всех связанных сущностей.
- Проверка пересечения расписания мастера.
- Создание приглашения сотрудника.
- Добавлены tenant-таблицы `service_categories` и `appointment_statuses`.
- Новая организация получает системные категории услуг и статусы записей автоматически.
- Существующие организации получают дефолтные значения через миграции.
- Создание записи связывается со статусом `pending` из базы.
- Добавлена страница `/settings/catalog` для создания и архивирования категорий услуг и статусов записей.
- Добавлена tenant authorization для операций со справочниками.
- Добавлено tenant-safe редактирование записи с повторной проверкой связанных сущностей и конфликта мастера.
- Добавлена отмена записи с сохранением статуса `cancelled` и связью с системным статусом из БД.
- В списке календаря добавлено действие отмены активной записи.
- Добавлена страница редактирования `/appointments/{appointment}/edit` с формой клиента, автомобиля, услуги, мастера, статуса, времени и комментария.
- Из списка календаря добавлен переход «Изменить».
- Недельный режим вынесен в отдельный плотный grid `/appointments?view=week` с колонками дней и карточками записей.
- Карточки weekly calendar ведут на редактирование записи.
- Добавлен drag-and-drop перенос записи между днями.
- Перенос сохраняет время и длительность, а backend повторно проверяет tenant и конфликт мастера.
- Отдельные страницы `/employees`, `/notifications`, `/settings`.
- Русские страницы услуг и записей.

### Этап 4 — заказ-наряд

- Таблицы `order_statuses`, `orders`, `order_items`.
- Модели заказов, статусов и позиций.
- Создание заказа из клиента, автомобиля, записи или напрямую.
- Номер заказа вида `ЗН-00001`.
- Добавление работ из каталога услуг.
- Исторический `name_snapshot`.
- Backend-расчёт `subtotal`, `discount`, `total`, `paid_total`, `balance`.
- Допустимые переходы статусов.
- Модель `OrderStatus` и endpoint смены статуса.
- Русская страница списка заказов.
- Канбан `/orders/kanban`.

### Платежи, фотографии и public order

- Таблица `payments` и модель `Payment`.
- Успешная оплата пересчитывает `paid_total` и `balance`.
- Отмена оплаты меняет статус на `cancelled`, физического удаления нет.
- Эндпоинты регистрации и отмены платежа.
- Таблица `order_photos` и модель `OrderPhoto`.
- Загрузка через Laravel Storage public disk.
- Ограничения: jpg, jpeg, png, webp, до 10 MB.
- Параметр `visible_to_client` для фото.
- Таблица `public_order_tokens` и SHA-256 hash токена.
- Public route `/order/{token}`.
- Public page отдаёт только client-safe данные.
- Внутреннее примечание и скрытые фотографии не попадают в public props.

### Activity Log, notifications и settings

- Добавлены события `OrderCreated` и `OrderStatusChanged`.
- Добавлена таблица `activity_logs` и helper `Activity::record`.
- Создание заказа и смена статуса пишутся в audit log.
- Добавлены модели `Notification` и `UserInvite`.
- Владелец/менеджер может создавать invite сотрудника.
- Добавлены страницы `/notifications` и `/settings`.
- Реализован read/unread workflow, массовое прочтение, badge количества новых уведомлений.
- Организация может менять `name`, `phone`, `email`, `timezone`, `currency`.
- `Activity Log` и уведомления привязаны к organization/user context.

### Поиск, история автомобиля и appointment→order

- Добавлен серверный поиск клиентов, автомобилей и заказов по tenant-организации.
- Добавлена страница `/search`.
- Добавлена связь `appointments.order_id`.
- Добавлен Action создания заказа из записи.
- Клиент и автомобиль переиспользуются без дублирования.
- Статус записи меняется на `arrived` после создания заказа.
- Добавлена страница истории автомобиля.
- История показывает заказы, работы, статусы и суммы.

### Password reset, archive и calendar filtering

- Добавлены страницы forgot password и reset password.
- Подключён Laravel password broker flow.
- Добавлено soft-delete архивирование клиентов через `DELETE /clients/{client}`.
- Добавлен backend filtering записей по `view=day|week` и дате.
- Существующая страница записей использует tenant-scoped данные.

### Dashboard analytics

- Backend отдаёт server-side trend за 7 дней: revenue, orders и average check.
- Dashboard показывает динамику выручки и сводные показатели периода.
- Chart data contract подготовлен для последующей прямой замены fallback panel на Bklit AreaChart/BarChart.

### Проверки и верификация

- `php artisan test` → 35 passed, 218 assertions.
- `npm run build` → production build successful.
- Дополнительный SPA-полир: оставшиеся переходы между разделами переведены на Inertia `Link`, без лишних full-page reloads.
- Реальный tenant `Qyran АвтоСвет` успешно заполнен демо-данными: 8 клиентов, 8 авто, 10 услуг, 12 заказов и 8 записей.
- Проверка конфликтов расписания.
- Проверка tenant-создания клиента.
- Проверка регистрации и trial.
- Проверка backend-расчёта заказа.
- Проверка auth redirect/login/logout.
- Проверка public order visibility.
- Проверка сохранения фото и visibility flag.
- Все текущие миграции применяются на SQLite в локальной среде.
- PHPUnit изолирован от рабочей базы: тесты используют SQLite `:memory:` и больше не очищают `database/database.sqlite`.

### SaaS Admin — этап 7

- Добавлен защищённый `/admin`, доступный только пользователю с ролью `platform_admin` без организации.
- Добавлены метрики организаций, активных trial-подписок и пользователей.
- Добавлен список организаций с подпиской и количеством пользователей.
- Добавлены действия блокировки/разблокировки организации.
- Добавлено продление trial на заданное количество дней.
- Добавлена детальная страница `/admin/organizations/{organization}` с профилем, статусом, подпиской и пользователями.
- Добавлены feature-тесты admin authorization, управления организациями и detail view.

### Demo Mode — этап 7

- Добавлен отдельный идемпотентный `DemoSeeder` с демо-организацией AutoService Demo.
- Демо-данные включают 50 клиентов, 50 автомобилей, 20 услуг, 8 сотрудников и 30 заказов.
- Добавлена публичная read-only страница `/demo` с KPI и последними заказами.
- Demo page отдаёт только безопасную сводку без внутренних комментариев и служебных полей.
- Добавлены feature-тесты публичного доступа и отсутствия мутации базы.
- Локальная подготовка: `php artisan db:seed --class=DemoSeeder`.

## Что ещё не сделано / не закрыто полностью

### Критично для MVP

- Полная SaaS-admin layer: детальные страницы организаций, пользователей, подписок и activity moderation.
- Расширение demo mode до полного набора данных из ТЗ: запчасти, платежи, расходы, фото и activity feed.
- Утверждённый CurrentOrganization context/middleware, который полностью заменяет повторяющиеся tenant-проверки.
- Полный просмотр, удаление и lightbox фотографий.
- Отзыв публичной ссылки и UI-генерация ссылки в заказе.
- Полный keyboard workflow Ctrl+K для глобального поиска.
- Полный audit log UI и более развитый activity feed.
- Полный список платежей и UI-отмена платежа на странице заказа.
- Полный production-ready calendar UX day/week controls и визуальная логика записи.

### Дополнительные MVP-долги

- Password confirmation.
- Rate limiting login/register.
- Полное редактирование и reorder пользовательских service categories и appointment statuses.
- Принятие invite и создание аккаунта сотрудника по токену.
- Полный CRUD/архив услуг, сотрудников, автомобилей и записей.
- Ссылки из списка клиентов на карточки и полноценное редактирование клиентской карточки.
- Улучшение calendar UX: drag/drop по точным временным слотам внутри дня остаётся отдельным этапом.
- Pagination controls и server-side filters в UI.
- Loading/error/permission-denied states на всех страницах.
- Accessibility smoke-check и unsaved changes.
- MariaDB/MySQL integration check.
- Полное подключение Bklit chart registry: npm registry не содержит проверенные `@bklit/ui`/`@bklit/charts`, поэтому сейчас используется Bklit-ready analytics data contract и GarageOS fallback panel.

### Отложено после первого релиза

- Расходы.
- Расширенная аналитика.
- WhatsApp/SMS/email integrations.
- Online booking и online payments.
- Multi-branch.
- API v1.
- AI и автоматизация.

## Что дополнительно сделано после текущей точки

- Реализован полный workflow настроек организации: route `/settings`, controller `SettingsController`, серверное сохранение `name/phone/email/timezone/currency` и UI-форма настроек.
- Добавлен bulk-read workflow уведомлений: `POST /notifications/read-all`, read/unread-state в UI, кнопка “Отметить все прочитанными”.
- Обновлён App layout: раздел “Настройки” ведёт на page настроек, а страницы уведомлений показывают количество новых сообщений и статус прочитанности.
- Добавлены регрессионные тесты `NotificationAndSettingsTest`.

## Продолжение Critical-аудита (01.10.2026)

### Блок 1 — Order / Payment / Debt Integrity

- Готовность проекта после блока: 71%.
- Добавлен `RecalculateOrderFinancials`: subtotal, discount, total, успешные платежи и balance рассчитываются сервером из строк заказа и платежей.
- Регистрация оплаты повторно сверяет баланс под блокировкой заказа; отмена оплаты пересчитывает долг и сохраняет платёж как cancelled.
- Платежи и изменения финансовых итогов записываются в Activity Log с before/after metadata.
- Закрытие заказа при ненулевом балансе, включая отрицательный, запрещено; владелец или менеджер может передать явный `allow_unpaid` override.
- Миграция переводит `orders.balance` в signed bigint, чтобы схема поддерживала overpayment/negative balance.
- Проверки: `PaymentTest`, `OrderTotalsTest`, `OrderDetailAccessTest` — 7 passed, 92 assertions.
- Изменённые файлы: `app/Actions/Orders/RecalculateOrderFinancials.php`, `app/Actions/Orders/AddOrderItem.php`, `app/Actions/Orders/ChangeOrderStatus.php`, `app/Actions/Payments/RegisterPayment.php`, `app/Http/Requests/ChangeOrderStatusRequest.php`, `app/Http/Controllers/OrderController.php`, `database/migrations/2026_10_01_000005_make_order_balance_signed.php`, `tests/Feature/PaymentTest.php`, `tests/Feature/OrderTotalsTest.php`.

### Блок 2 — Inventory / Stock Reservation / Low-stock

- Готовность проекта после блока: 74%.
- Добавлены `parts.reserved_stock`, `order_items.inventory_status` и отдельный `inventory_transactions.reserved_delta`; backfill сохраняет прежние списания.
- При добавлении запчасти в открытый заказ остаток резервируется; в заказе «В работе» позиция списывается сразу.
- Переход в «Готов» списывает резерв, отмена освобождает резерв или возвращает ранее списанную позицию.
- Доступный остаток проверяется под блокировкой, включая ручные складские операции; движение записывается в inventory ledger и Activity Log.
- Low-stock уведомления рассчитываются от доступного остатка и дедуплицируются до пополнения.
- Проверки: `InventoryTest` — 11 passed, 109 assertions; `npm run build` — успешно.
- Изменённые файлы: `database/migrations/2026_10_01_000003_add_inventory_reservations.php`, `app/Models/Part.php`, `app/Models/OrderItem.php`, `app/Models/InventoryTransaction.php`, `app/Actions/Inventory/ManageInventoryStock.php`, `app/Actions/Orders/AddOrderItem.php`, `app/Actions/Orders/ChangeOrderStatus.php`, `app/Http/Controllers/InventoryController.php`, `app/Http/Controllers/OrderController.php`, `resources/js/Pages/Inventory/Index.tsx`, `resources/js/Pages/Orders/Show.tsx`, `tests/Feature/InventoryTest.php`.

### Блок 3 — Client Experience

- Готовность проекта после блока: 78%.
- Добавлен read-only `/portal`: автомобили, заказы/история, статусы, суммы, безопасные позиции и только `visible_to_client` фотографии.
- Добавлены связующая таблица `client_users`, модели связей и `ClientPolicy::viewPortal` / `OrderPolicy::viewPortal`; роль `client` перенаправляется в portal и не может открывать staff workspace.
- Owner может выдать клиенту доступ из карточки клиента; аккаунт получает ссылку для задания пароля через Laravel password reset flow, действие записывается в Activity Log.
- Public Order Page дополнительно ограничена rate limit-ом; сериализует whitelist client-safe props, скрывает внутренние поля/фото и отклоняет отозванные, истёкшие или несогласованные по tenant ссылки.
- Клиентские аккаунты не расходуют места тарифа сотрудников.
- Проверки: `ClientPortalTest`, `PublicOrderTest`, `EmployeeInviteTest` — 8 focused passed; полный suite — 70 passed, 656 assertions; `npx tsc -p tsconfig.json --noEmit` и `npm run build` — успешно.
- Изменённые файлы: `database/migrations/2026_10_01_000004_create_client_users_table.php`, `app/Models/Client.php`, `app/Models/User.php`, `app/Models/Subscription.php`, `app/Policies/ClientPolicy.php`, `app/Policies/OrderPolicy.php`, `app/Actions/Clients/EnableClientPortalAccess.php`, `app/Http/Controllers/ClientController.php`, `app/Http/Controllers/ClientPortalController.php`, `app/Http/Controllers/PublicOrderController.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `app/Http/Middleware/EnsureActiveSubscription.php`, `routes/web.php`, `resources/js/Pages/ClientPortal/Index.tsx`, `resources/js/Pages/Clients/Show.tsx`, `tests/Feature/ClientPortalTest.php`, `tests/Feature/PublicOrderTest.php`, `tests/Feature/EmployeeInviteTest.php`.

### Общая финальная проверка

- Весь test suite: 70 passed, 656 assertions.
- TypeScript: `npx tsc -p tsconfig.json --noEmit` завершился без ошибок.
- Production frontend: `npm run build` завершился успешно.
- Добавлен базовый SaaS Admin: `/admin`, управление статусом организаций и продление trial.
- Добавлены dashboard analytics за 7 дней и feature-проверка chart props.
- Исправлен `phpunit.xml`: feature-тесты используют SQLite `:memory:` вместо рабочей базы.
- Подтверждён рабочий build и полный тестовый прогон после SaaS Admin.

## Текущее состояние по соответствию ТЗ

### Соответствует

- multi-tenant SaaS ядро;
- rel/tenant-aware data model;
- auth + subscription guard;
- CRM + order management;
- payments, photos, activity log, public order flow;
- dashboard KPIs and notifications;
- dark-first UI foundation.

### Частично соответствует

- design system visual language: да, по базовой теме и структуре, но требует дальнейшей проработки по Bklit charts, visual density и polish;
- план в `IMPLEMENTATION_PLAN.md`: архитектура и последовательность верные, но не все пункты и сроки закрыты;
- реализация всех “MVP acceptance test” шагов: core flow закрыт, но не весь операционный сценарий и admin/demo layer ещё не завершены.

### Не закрыто по формальному ТЗ

- Bklit package source/registry требует отдельного подтверждения перед production-интеграцией; fallback analytics panel уже работает.
- Полный календарь и UX day/week controls ещё не завершены.
- Demo/admin production readiness ещё требуют завершающих проверок и runbook.
- Базовый SaaS Admin и read-only Demo Mode реализованы; детальные admin-разделы и production deployment остаются в работе.
- Документация по статусам требует регулярного синхрона с фактическим состоянием.

## Следующий шаг

Следующий блок: завершение calendar UX — day/week переключение, быстрые действия “Сегодня”, сводные карточки, понятная логика записи. После этого — admin detail pages, code splitting и production readiness.

Проверка на текущий момент:
- backend tests: 33 passed, 205 assertions
- frontend build: successful (Vite сообщает только о chunk > 500 kB)
- core GarageOS workflow: implemented
- full design-system parity and admin/demo completeness: in progress

## Production-readiness sequence (02.10.2026)

### Блок 1 — Production Database & Deploy Readiness

- Готовность после подготовительных изменений: 81%.
- PHP runtime requirement выровнен на 8.3+; `.env.example` теперь содержит production MySQL, `APP_DEBUG=false`, SMTP, HTTPS, public storage и secure-cookie placeholders.
- Laravel timezone/locale defaults: `Asia/Almaty` / русский; MySQL и MariaDB session timezone задаётся через `DB_TIMEZONE` (по умолчанию `+05:00`). Новые организации используют `Asia/Almaty` / `KZT`; timezone/currency settings проходят backend validation.
- Default database и database queue fallback больше не выбирают SQLite. Локальная SQLite конфигурация отдельно описана в `RUN.md`.
- Scheduler readiness завершена в блоке 2: task `subscriptions:expire` зарегистрирован каждые 5 минут; trial/paid lock также проверяется синхронно на каждом запросе.
- Проверки при закрытии блока 1: полный suite — 72 passed, 664 assertions; `npx tsc -p tsconfig.json --noEmit`, `npm run build`, `composer validate --no-check-publish` успешны.
- Ограничение проверки: PHP 8.3.33 и `pdo_mysql` доступны, но MySQL на `127.0.0.1:3306` отсутствует, а Docker daemon выключен. Реальный migration smoke-test на MySQL 8/MariaDB перенесён в staging release gate.
- Изменённые файлы: `.env.example`, `composer.json`, `composer.lock`, `config/app.php`, `config/database.php`, `config/queue.php`, `app/Http/Controllers/SettingsController.php`, `resources/js/Pages/Settings/Index.tsx`, `tests/Feature/NotificationAndSettingsTest.php`, `RUN.md`.
- `composer update --lock` обнаружил 2 security advisories в dependency graph; они устранены в блоке Security Hardening обновлением `league/commonmark` до 2.10.3.

### Блок 4 — Security Hardening

- Готовность после блока: 93%.
- Заказные фотографии хранятся приватно; content/MIME validation отвергает поддельные изображения с расширением `.jpg`, ограничивает размер/размеры изображения.
- Добавлены staff/public/client-authorized photo streams; скрытые фото и чужие client/tenant ресурсы не отдаются. Legacy files переносятся отдельной миграцией/командой.
- Публичные order tokens можно вращать и отзывать; прежняя ссылка сразу перестаёт работать, token secrets в Activity Log не записываются.
- Rate limits покрывают login, registration, password reset, invitations, client portal/public order и чувствительные photo/payment writes.
- Policies используются для tenant/role authorization write paths; cross-tenant resource existence скрывается `404` там, где это security contract.
- Critical writes (subscription/admin, catalog, settings, appointments, employee invites, payments, inventory, photos) оставляют audit events без паролей и иных секретов.
- Dependency remediation: `league/commonmark` обновлён с `2.10.1` до `2.10.3`, `symfony/polyfill-php80` — с `v1.37.0` до `v1.43.0`; `composer audit` чистый.
- Проверки: полный suite — 91 passed, 842 assertions; `npx tsc -p tsconfig.json --noEmit`, `npm run build`, `composer validate --no-check-publish` успешны; `composer audit` сообщает об отсутствии advisories.
- Изменённые файлы: `app/Http/Controllers/SettingsCatalogController.php`, `app/Http/Controllers/OrderPhotoController.php`, `app/Http/Controllers/PublicOrderController.php`, `app/Actions/Orders/UploadOrderPhoto.php`, `app/Http/Requests/StoreOrderPhotoRequest.php`, `app/Policies/AppointmentPolicy.php`, `app/Policies/AppointmentStatusPolicy.php`, `app/Policies/CarPolicy.php`, `app/Policies/ClientPolicy.php`, `app/Policies/ExpenseCategoryPolicy.php`, `app/Policies/ExpensePolicy.php`, `app/Policies/OrderPolicy.php`, `app/Policies/PartPolicy.php`, `app/Policies/ServiceCategoryPolicy.php`, `app/Policies/ServicePolicy.php`, `app/Policies/SubscriptionChangeRequestPolicy.php`, `app/Policies/UserInvitePolicy.php`, `database/migrations/2026_10_02_000003_add_storage_disk_to_order_photos.php`, `composer.json`, `composer.lock`, `tests/Feature/OrderPhotoTest.php`, `tests/Feature/PublicOrderLinkSecurityTest.php`, `tests/Feature/AuthenticationTest.php`, `tests/Feature/SettingsCatalogTest.php`.

### Финальные release gates

- Четыре приоритетных блока завершены в коде; unit/feature suite, TypeScript, production build и Composer audit проходят.
- Перед клиентским запуском обязателен staging rollout на фактический MySQL 8 или MariaDB с `migrate --force`, smoke-тестом заказа/оплаты и проверкой private photo storage. На локальной машине SQL service недоступен, Docker daemon выключен.
- Production owner должен задать реальные `APP_KEY`, HTTPS/domain, MySQL credentials, SMTP, backup retention/restore, storage permissions, cron и Supervisor/systemd queue worker согласно `RUN.md`.
- Billing работает с ручной сверкой платежа Platform Admin; эквайринг/recurring billing не подключены без выбранного провайдера.

### Блок 2 — SaaS Billing & Subscription Lifecycle

- Готовность после блока: 85%.
- Добавлен серверный `PlanCatalog`: Start — 39 900 ₸ / 3 сотрудника; Business — 69 900 ₸ / 10 сотрудников; entitlement list и цены отдаются frontend только с backend.
- Feature gates на сервере защищают inventory, expenses, financial reports и advanced analytics; ограничения действуют при прямом URL и POST.
- Trial и paid subscriptions переводятся в `expired` транзакционно с Activity Log; access требует будущий `trial_ends_at` для trial или будущий `ends_at` для active. Пустые даты fail closed.
- Scheduler `subscriptions:expire` выполняется каждые 5 минут; запросная проверка сохраняет немедленный hard lock при просрочке.
- Owner может запросить upgrade/downgrade/renewal; Platform Admin активирует после обязательного payment reference. Downgrade блокируется, если число сотрудников выше лимита; client portal accounts не занимают seat.
- Цена/KZT фиксируется в заявке на момент запроса; payment reference уникален и повторно применить квитанцию к другому аккаунту нельзя.
- Subscription page показывает тарифы, цены, лимит/использование мест, даты, feature gates и заявки. Менеджер видит lock screen read-only; изменить тариф может только owner.
- Оплата тарифа вручную сверяется Platform Admin; автоматический payment gateway, recurring charge и автопродление не подключены, так как провайдер/реквизиты не заданы.
- Проверки: focused billing/guard/access — 11 passed, 92 assertions; полный suite — 80 passed, 722 assertions; `npx tsc -p tsconfig.json --noEmit`, `npm run build`, `composer validate --no-check-publish` успешны; `schedule:list` показывает expiry sweep каждые 5 минут.
- Изменённые файлы: `app/Services/Billing/PlanCatalog.php`, `app/Models/Subscription.php`, `app/Models/SubscriptionChangeRequest.php`, `app/Models/Organization.php`, `app/Actions/Subscriptions/ExpireSubscription.php`, `app/Actions/Subscriptions/RequestPlanChange.php`, `app/Actions/Subscriptions/ReviewPlanChange.php`, `app/Http/Middleware/EnsureActiveSubscription.php`, `app/Http/Middleware/EnsureSubscriptionFeature.php`, `app/Policies/OrganizationPolicy.php`, `app/Http/Controllers/SubscriptionController.php`, `app/Http/Controllers/AdminController.php`, `database/migrations/2026_10_02_000001_create_subscription_change_requests_table.php`, `bootstrap/app.php`, `routes/web.php`, `routes/console.php`, `resources/js/Pages/Subscription/Show.tsx`, `resources/js/Pages/Admin/Index.tsx`, `tests/Feature/BillingLifecycleTest.php`, `tests/Feature/TrialGuardTest.php`, плюс entitlement-aligned fixtures в `AdminTest`, `RoleAccessTest`, `EmployeeInviteTest`, `FinanceTest`, `AnalyticsTest`, `InventoryTest`; `RUN.md` обновлён с scheduler/queue instructions.

### Блок 3 — Finance Module

- Готовность после блока: 90%.
- Expenses поддерживают create/update/soft-delete archive, tenant-scoped category create/rename/archive и аудит сумм/изменений; архивированные расходы исключаются из отчётов без физического удаления.
- Финансовый backend считает успешные поступления, расходы, операционный P&L, средний чек и положительный долг; report aggregation и debtor join ограничены organization_id.
- Добавлены фильтры: сегодня, 7/30/90 дней, месяц, год и custom date range (максимум 366 дней, неверный диапазон возвращает ошибки валидации).
- Долги сгруппированы по клиентам с количеством открытых заказов; архивный клиент помечен, чувствительная/чужая tenant информация не смешивается.
- Проверки: `FinanceTest` — 6 passed, 98 assertions; полный suite — 83 passed, 795 assertions; TypeScript и `npm run build` успешны.
- Изменённые файлы: `database/migrations/2026_10_02_000002_add_soft_deletes_to_expenses.php`, `app/Models/Expense.php`, `app/Http/Requests/UpdateExpenseRequest.php`, `app/Actions/Finance/CreateExpense.php`, `app/Actions/Finance/UpdateExpense.php`, `app/Actions/Finance/ArchiveExpense.php`, `app/Actions/Finance/UpdateExpenseCategory.php`, `app/Http/Controllers/FinanceController.php`, `routes/web.php`, `resources/js/Pages/Finance/Index.tsx`, `tests/Feature/FinanceTest.php`.
