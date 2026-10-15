# GarageOS Landing Audit

**Дата:** 2026-10-08  
**Статус:** Phase 0 завершён до реализации.  
**Область:** Laravel/Inertia public routes, auth, реальный продуктовый UI, demo data и `PlanCatalog`.

## Краткий вывод

Сайт GarageOS работает в том же Laravel + Inertia/React приложении. Сейчас root `/` зарегистрирован внутри `auth` + `trial` и рендерит операционный `Dashboard`; публичные login/register pages и отдельный read-only `/demo` существуют. Маршрутов landing/features/pricing/faq/contact/terms/privacy пока нет.

Ключевое ограничение routing: root name `dashboard` используется после сохранения настроек организации. При добавлении публичного home нужно сохранить named route и поведение для аутентифицированного владельца/менеджера/platform admin/mechanic. Middleware `trial` пропускает гостя без organization, но продолжает закрывать истёкшие и заблокированные tenant accounts.

## Изучено

### Routes и auth

- `routes/web.php`: `/login`, `/register`, `/demo` и public order links — guest/public; `/` и вся рабочая CRM находятся в `auth` + `trial`; Super Admin изолирован отдельным `platform-admin` middleware.
- `AuthenticatedSessionController`: login делает redirect intended на `/`; platform admin на root будет перенаправлен в `/admin` контроллером dashboard. Новый public root controller должен делегировать вошедшего пользователя текущему Dashboard flow.
- `SettingsController` возвращает на named route `dashboard`; имя root route должно сохраниться.
- `HandleInertiaRequests` публикует auth user/organization; публичный экран не должен подделывать auth props или tenant session.

### Реальные экраны/компоненты

- `Dashboard.tsx`: существующая рабочая сводка с реальными метриками организации, schedule, onboarding, быстрыми действиями и Bklit AreaChart.
- `AppLayout.tsx`: настоящий responsive sidebar и навигация (Сегодня, Заявки, Заказы, Клиенты, Автомобили, Услуги, Склад, Сотрудники, Финансы, Аналитика, Настройки).
- `Analytics/Index.tsx`: настоящий экран аналитики на Bklit BarChart, с tenant-scoped revenue, expenses, orders, services и mechanic workload.
- `Clients/Index.tsx`, `Cars/Index.tsx`, `Appointments/Index.tsx`, `Orders/List.tsx` и `Orders/Show.tsx` — реальные рабочие поверхности с Inertia Forms, tenant data и разрешёнными бизнес-операциями; напрямую подключать их как публичные страницы нельзя.
- `Demo/Index.tsx` — отдельная read-only демонстрация фактических клиентов/заказов организации.

### Demo data

- `DemoController` выбирает только organization `auto-service-demo`, делает read-only aggregate/query и не мутирует DB.
- `DemoSeeder` создаёт фиктивную организацию и fictional clients/cars/services/orders, но `DatabaseSeeder` его не запускает автоматически.
- `DemoTest::test_demo_requires_seed_data_instead_of_mutating_database` ожидает 404 при отсутствии demo seed; public landing не должен синхронно запускать seeder или менять бизнес-таблицы.
- Поэтому hero preview должен иметь безопасный fictional fallback и оставаться read-only, если demo organization не подготовлена.

### Plans/features

`PlanCatalog` — единственный источник истины. Start — 39 900 ₸/мес., максимум 3 сотрудника; Business — 69 900 ₸/мес., максимум 10 сотрудников. Features подключаются через server-side gates. Landing получает планы из этого каталога, не дублирует цены/features в нескольких местах и не называет несуществующие возможности.

### Frontend/assets

- Стек: Laravel 12, React 19, TypeScript, Inertia, Tailwind 4, Vite; `app.tsx` автоматически резолвит `resources/js/Pages/**/*.tsx`.
- В проекте установлены Motion и локальный font package Geist; для charts используется Bklit/Visx; Chart.js отсутствует. Новую chart library или framework добавлять не нужно.
- Действующая product design language: near-black GarageOS surfaces, lime accent, muted neutral text, blue/success/warning/error statuses, Inter, radius до 10 px; глобальный app UI остается нетронутым.
- Найден `public/logo.jpg`; иных product screenshots/photos в public assets нет. Live read-only browser preview продукта предпочтительнее искусственного скриншота.

## Реализационная гипотеза и проверка

**Гипотеза:** один публичный `GET /` с существующим именем `dashboard`, `trial` middleware и controller branch `guest → Landing`, `authenticated → текущий DashboardController` добавит landing, сохранив route name, auth redirect и tenant billing guards.  
**Дешёвая проверка:** route feature tests для guest (`Landing/Home`), owner (текущий `Dashboard`) и недействующей subscription (redirect `/subscription`), плюс существующий settings redirect test.

## Scope landing

- `/`: product-first hero, browser-style live Demo/Showcase, actual feature summary, how-it-works, current pricing, FAQ preview и CTA.
- `/features`, `/pricing`, `/faq`, `/contact`, `/terms`, `/privacy`: public Inertia pages на общем PublicLayout.
- `/demo` остается отдельной read-only страницей; demo fallback не создаёт организации, users, заказы или другие DB records.
- `/login` и `/register` сохраняют существующие routes/forms; CTA ведут в существующий onboarding.
- `LANDING_AUDIT.md` и `/design/LANDING_DESIGN.md` создаются до runtime-кода.