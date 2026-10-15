# GarageOS Admin Design System

## Purpose

Внутренняя operational console для владельца SaaS. Интерфейс ориентирован на ежедневное управление организациями, подписками, оплатами и системными событиями, а не на tenant operations.

## Shell

- Отдельный `AdminLayout`; не использовать sidebar tenant workspace для Super Admin screens.
- Desktop: фиксированный sidebar шириной 256 px, прокручиваемая навигация по группам «Платформа», «Биллинг и метрики», «Система»; активный маршрут подсвечен акцентной полосой.
- Mobile: sidebar открывается поверх затемнённого фона, закрывается кнопкой и после выбора раздела.
- Верхняя панель фиксирует название текущего раздела, статус платформы и текущего администратора; выход доступен через POST `/logout`.
- Основная область ограничена 1440 px, контент адаптирует таблицы и формы к узким экранам.

## Visual tokens

- Использовать существующие `--garage-*` tokens из `resources/css/app.css`.
- Акцент GarageOS lime предназначен для текущего состояния и primary action; синий — информационные значения; жёлтый — ожидание/предупреждение; красный — блокировка, refund и destructive actions.
- Повторяющиеся карточки — плоские, радиус не больше 8 px; таблицы и секции не вкладывать в декоративные cards.
- У сумм всегда указывать валюту; тарифная цена и лимиты должны поступать из `PlanCatalog`, не из UI-констант.

## Navigation

- Платформа: Обзор, Организации, Пользователи.
- Биллинг и метрики: Тарифы, Подписки, Платежи, Финансы SaaS, Аналитика.
- Система: Поддержка, Журнал аудита, Настройки.
- Платформенная навигация отображается только пользователю, прошедшему серверную проверку `isPlatformAdmin()`; клиентское скрытие не является authorization.

## Tables and filters

- Таблица: компактный заголовок, выравнивание чисел вправо, status badge, строковые действия в последней колонке.
- Списки используют серверную pagination (25 rows по умолчанию), поиск и только относящиеся к ресурсу status/plan/organization/date filters.
- Query filters сохраняются при переходе по страницам; empty state объясняет отсутствие результатов и не имитирует ошибку.
- На mobile строки переходят в плотные stacked rows; скрывать не ключевую вторичную метаинформацию, но не primary status/amount/action.

## Forms and actions

- Основные action формы короткие и label-based; ошибки Laravel показываются у поля и summary-level flash.
- Status/role/plan mutations отправляются только POST/PUT/PATCH с CSRF; сервер повторно валидирует target и permissions.
- Block, archive, cancel и refund требуют отдельного подтверждения с названием организации/объекта и необратимым эффектом.
- Не показывать и не возвращать payment credentials, API keys, database credentials или секреты конфигурации.

## Metrics and charts

- KPI: значение, точное определение периода и источник; нулевые/неполные исторические данные не маскировать догадками.
- Временные ряды: Bklit `AreaChart`, горизонтальная сетка, `XAxis`, tooltip; категории и тарифы: Bklit `BarChart` с `BarXAxis` или `BarYAxis`.
- Графики используют `--chart-*` variables и `chartCssVars` при наличии, не задают одноразовые цвета внутри компонентов.
- Единственный выбранный date filter передаётся каждому графику страницы.
- Revenue, expenses и net revenue берутся только из SaaS ledger; MRR считается отдельно из оплачиваемых active subscriptions и каталога тарифов.
- Empty chart state показывает понятное отсутствие данных вместо фиктивных точек.

## Statuses

- Organization: Active, Suspended/Blocked, Archived.
- Subscription: Trial, Active, Expired, Suspended, Cancelled.
- Payment: Paid, Pending, Failed, Refunded.
- Status badge сочетает короткую подпись и semantic color; цвет не единственный носитель статуса.

## States

- Loading: существующая серверная загрузка Inertia; при локальном async action — блокировка повторной отправки и явный progress state.
- Empty: zero-state в области таблицы/графика с одной ясной подсказкой.
- Error: человеческое сообщение, сохранение фильтров/введённых значений; технические детали только в server logs.
- Success: краткое flash-сообщение после redirect; не заменять им audit trail.

## Dialogs and drawers

- Использовать dialog только для confirm/destructive action или короткой формы, не для больших create/edit workflows.
- Для organization details использовать отдельную страницу; drawer допустим для небольшого контекста без собственного сложного маршрута.
- Dialog содержит заголовок, краткое последствие, cancel и явную primary/destructive кнопку.

## Pagination and accessibility

- Сохранять query filters при pagination; показывать текущую страницу и общий диапазон результатов.
- Все действия работают с клавиатуры и имеют видимый focus; иконки без текста снабжаются доступным названием.
- Навигация mobile имеет aria-label; интерактивные элементы не вкладываются друг в друга.