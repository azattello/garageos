# GarageOS: план доработок и открытые решения

**Срез:** 07.10.2026  
**Состояние:** все пять задач предыдущего ближайшего среза реализованы; остаются продуктовые решения и production database gate.  
**Проверка:** 114 backend-тестов / 1220 assertions; TypeScript и production build проходят; `composer validate` успешен; `composer audit` чистый. Все 34 локальные SQLite migrations применены. MySQL/MariaDB не проверены: сервер недоступен, Docker daemon не запущен.

Этот план продолжает [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) и дополняет [GarageOS_PRODUCT_GUIDE.md](GarageOS_PRODUCT_GUIDE.md). Задачи отсортированы по влиянию на ежедневную работу сервиса и выпуск клиентам. Необсуждённые архитектурные решения помечены **[ОБСУДИТЬ]** и не должны самовольно превращаться в бизнес-правила. Долгосрочные функции и вопросы для согласования собраны также в [GarageOS_OPEN_DECISIONS.md](GarageOS_OPEN_DECISIONS.md).

## Статус цикла

Метки: **Готово** — подтверждено кодом и/или тестами; **Частично** — основной сценарий есть, перечисленные ограничения остаются; **Не начато** — реализации пока нет. Отсутствие feature test или staging проверки не трактуется как подтверждение.

- **Готово:** car/client search, карточки и tenant checks; архивирование услуг/автомобилей; appointment CRUD/cancel и appointment→order; order/payment/debt invariants; inventory reservations/ledger; Finance expenses/reports; billing gates; public/client photo authorization; notifications/activity filters.
- **Готово:** приглашения сотрудников с принятием, повторной отправкой и отзывом; tenant-scoped базовый SaaS Admin и read-only demo.
- **Готово в этом срезе:** 30-минутный стартовый интервал и service-duration end time; inline appointment availability/conflict; keyboard/touch move; employee profile/status management с safety checks; client event timeline; photo edit/archive/lightbox; expense comparisons/chart/aggregate CSV.
- **Остаётся частичным:** график работы/вертикальные time slots; автоматическое переназначение сотрудника не производится; analytics CSV не заменяет бухгалтерскую транзакционную выгрузку; полный SaaS Admin analytics/user management.
- **Release gate:** локальные migrations и SQLite tests подтверждены, но production MySQL/MariaDB compatibility и restore rehearsal не подтверждены.

## P0.0 — Восстановить Finance и Inventory — Готово

- Выполнено: старые ошибки Inventory от 18:02 и 19:11–19:13 (`no such table: parts`, `no such column: reserved_stock`) и Finance от 19:14–19:15 (`no such column: expenses.deleted_at`) соответствовали неполной SQLite schema до применения migrations.
- Текущая база `database/database.sqlite` содержит `parts.reserved_stock` и `expenses.deleted_at`; migrations отмечены Ran; Finance/Inventory Feature-тесты проходят (17 тестов).
- Выполнен authenticated browser smoke-check `/finance` и `/inventory`: обе страницы вернули рабочий экран без ошибки; Finance показывает KPI/периоды/должников, Inventory — KPI, поиск, empty state и форму.
- Если ошибка повторится, сверить новый stack trace и database path процесса; не применять destructive migrations без подтверждения причины.

## P0 — Стабильный ежедневный поток

### P0.1. Исправить активные UI-кнопки — Готово

- Выполнено: `Clients/Index.tsx` и `Services/Index.tsx` — верхние действия прокручивают к уже существующим формам.
- Выполнено: `Orders/Index.tsx` — верхняя кнопка «Новый заказ-наряд» ведёт к форме создания.
- Выполнено: `AppLayout.tsx` — организация теперь честная non-interactive метка; меню профиля показывает роль/email и выполняет logout.
- **Проверено в этом аудите:** TypeScript и production build проходят. Отдельный browser/mobile smoke test кликов в текущем срезе не запускался; это остаётся acceptance check.

### P0.2. Список автомобилей и навигация — Готово

- Выполнено: отдельный tenant-scoped `/cars` index с поиском по марке/модели/госномеру/VIN/клиенту, pagination и ссылкой в историю; sidebar ведёт на `/cars`.
- **Проверено тестами:** поиск, карточка и tenant isolation. Открытие в авторизованном браузере зафиксировано ранее, но в аудите 07.10 не повторялось.

### P0.3. Дедупликация клиента и создание автомобиля — Частично

- **Готово:** server-side duplicate lookup по телефону/email в текущей организации; найденный клиент возвращается выбранным; быстрые действия ведут в создание авто/заявки/заказа; backend назначает tenant.
- **Остаётся:** сейчас совпадение по любому из полей автоматически переиспользует клиента. Явное подтверждение выбора и политика shared family phone не согласованы; проверить email/phone cross-tenant leakage отдельным тестом.
- **[ОБСУДИТЬ]** Правило дублей: phone only, email only, или совпадение любого поля? Что делать при shared family phone?

### P0.4. Поток заявки и календарь — Базовый UX готов

- **Готово:** 4-step wizard, выбор tenant-owned client/car, quick-create, service/mechanic/date fields, day/week views, «Сегодня», серверная проверка пересечения и перенос записи между днями с сохранением времени/длительности.
- **Готово:** начало записи с 30-минутным шагом, конец заполняется из длительности услуги; debounced availability endpoint и inline result; keyboard-стрелки и горизонтальный touch-swipe перемещают запись на соседний день.
- **Остаётся согласовать:** business hours, перерывы, внерабочие записи и вертикальная time grid; MySQL timezone/DST поведение требует staging проверки.
- **[ОБСУДИТЬ]** Длительности по умолчанию для услуг, часы работы сервиса и поведение вне рабочих часов.

### P0.5. Единый быстрый поток «клиент → автомобиль → заявка» — Готово, кроме политики дублей

- **Готово:** quick-create client/car из appointment/order workflow, с возвратом в контекст и предварительным выбором сущностей; связи проверяются на backend.
- **Остаётся согласовать:** автоматический reuse при shared phone/email против явного подтверждения найденного клиента (см. [GarageOS_OPEN_DECISIONS.md](GarageOS_OPEN_DECISIONS.md)).

## P1 — Удобство операционной работы

### P1.1. Заказ, статусы и расчёты — Основной сценарий готов

- Выполнено: платежи показывают статус и имеют отдельную кнопку отмены с подтверждением; закрытие с долгом требует owner/manager `allow_unpaid` checkbox и подтверждение.
- Карточка заказа показывает следующие workflow actions; закрытие с ненулевым балансом требует явный `allow_unpaid` owner/manager и подтверждение.
- Выполнено: Kanban получает допустимые следующие этапы из backend Action, содержит все системные состояния и даёт select для touch/keyboard; номер заказа открывает карточку.
- Выполнено: невалидный drag не отправляется; backend transition errors отображаются у карточки, повторное действие блокируется до завершения запроса.
- Добавить edit/archive строки заказа только после определения правил складских возвратов и финансового перерасчёта.
- **Не подтверждено отдельным тестом:** конкурентные запросы в реальном MySQL и rollback складских движений при редактировании/удалении позиции; edit/archive строк заказа пока отсутствует.
- **[ОБСУДИТЬ]** Кто может вручную закрывать долг/переплату, требуется ли reason обязательным, нужна ли двухсторонняя отмена.

### P1.2. Услуги, автомобили и управление аккаунтами сотрудников — Готово

- Выполнено: услуги можно редактировать, архивировать и восстанавливать; изменения аудируются, архивная услуга исключается из нового каталога записей.
- Выполнено: технические поля автомобиля редактируются; archive/restore обратимы и аудируются; архив блокируется при активном заказе/заявке; архивные машины исключены из новых записей/заказов/портала/поиска, история сохраняется. Фото автомобиля пока отображаются существующим способом без полноценного lightbox/edit workflow.
- **Готово:** team view/invites; owner-only name/phone/specialization edit, activate/deactivate, audit events и tenant/role tests.
- Деактивация блокируется, если у сотрудника есть активный заказ либо будущая незавершённая запись. Автоматического переназначения нет; owner должен выполнить его вручную.
- **[ОБСУДИТЬ]** Может ли manager архивировать услугу/сотрудника или это owner-only?

### P1.3. Поиск, Activity Log и полная история — Готово

- Выполнено: server-side поиск клиентов по имени/телефону/email и госномеру/VIN связанных машин; строки списка кликабельны, pagination сохраняет query.
- Выполнено: global search ведёт из каждого результата в карточку клиента, автомобиля или заказа.
- **Готово:** автомобильная история включает связанные заказы/работы/платежи/фото; клиентская карточка показывает tenant-scoped paginated timeline заказов, appointments, работ/запчастей, платежей, фото и status changes; сводка агрегирует все заказы.
- Выполнено: Activity Log фильтруется по тексту/action, actor, entity и датам; есть pagination и безопасный before/after viewer; доступ ограничен owner/manager.
- Проверки: поиск не возвращает архивных/чужих объектов, журнал не показывает secrets/payment credentials.

### P1.4. Фото — Готово

- **Готово:** загрузка с category и `visible_to_client`, content validation, private storage/authorized streams, public/client ownership routes.
- **Готово:** lightbox с Escape/стрелками/кнопками, caption/category/visibility edit, soft-delete archive с audit и подтверждением; private file сохраняется, archived photo streams возвращают 404.

### P1.5. Finance и выбранный analytics slice — Готово

- **Готово:** server-side finance aggregation для поступлений/расходов/операционного итога/долга и analytics page с tenant-scoped периодными показателями.
- **Готово:** сравнение поступлений/расходов/операционного итога с предыдущим периодом, Bklit daily inflow/outflow chart и tenant-scoped CSV без PII; Bklit BarCharts показывают услуги/статусы/workload.
- **Не входит в этот slice:** бухгалтерский транзакционный export; CSV содержит дневные агрегаты и требует отдельного решения по accounting basis.
- Явно обозначить метод расчёта и валюту, не смешивать стоимость созданных заказов с полученными платежами; все суммы и tenant-scoped выборки вычислять на backend.
- **Готово:** aggregate CSV экспорт с tenant/RBAC тестом; транзакционная бухгалтерская выгрузка остаётся вне scope до согласования accounting basis.
- Улучшить finance empty/error/loading states и фильтры; текущие суммы/P&L/долги уже считаются backend.
- **[ОБСУДИТЬ]** Средний чек считать по выручке платежей или по стоимости созданных заказов? Текущая логика задокументирована в finance tests.

## P2 — Product expansion

- Public landing page с тарифами, FAQ, demo CTA и регистрацией.
- Client portal расширить записями, рекомендациями, собственной историей платежей и сервисными напоминаниями; сейчас это read-only машины/заказы/статусы/доступные фото/суммы.
- Настраиваемые статусы заказа и переходы через organization-level каталог без возможности обойти финансовые/складские invariants.
- Granular permissions на пользователя сверх фиксированных ролей.
- Несколько филиалов/складов и branch-aware inventory.
- Email/SMS/WhatsApp notifications, online booking и payment gateway/recurring billing после выбора провайдеров.
- SaaS Admin: MRR, subscription history, payment reconciliation export, user management и audit explorer.
- Salary calculations, accounting integrations, public API, AI automation.
- CI/CD с MySQL 8 и MariaDB migration matrix, staging smoke tests, backup/restore drill, observability/error alerting.

## Product decisions: не реализовывать без согласования

1. Дубли клиентов: поле/правило идентификации; семейные номера; merge vs block.
2. Рабочий календарь: часы работы, выходные, timezone per branch, длительности, посты/подъёмники.
3. Закрытие заказа с долгом/переплатой: роли, подтверждение, reason и аудит.
4. Статусы: системные обязательные состояния, разрешённые переходы, отмена после выдачи, custom status ownership.
5. Финансы: cash basis vs accrual basis, возвраты/частичный refund, считать ли скидки/запчасти в average check.
6. Тарифы: налог/НДС, платёжные реквизиты/провайдер, период подписки, grace period, автоматическое продление/возврат.
7. Клиентский кабинет: email/phone identity, один клиент в нескольких организациях, доступ нескольких family users.
8. Branch/warehouse model: отдельные tenant organizations или подразделения одной организации.
9. **Решено:** все chart-визуализации строить официальными Bklit UI registry-компонентами и следовать `.agents/skills/bklit-ui`.
10. Retention: срок хранения фото/activity/payment audit и процесс удаления по запросу.

## Следующие шаги

1. **Продуктовое решение:** правила совпадения клиента по phone/email, включая shared family phone.
2. **Release gate:** MySQL 8/MariaDB staging migration + critical workflow smoke-test + backup/restore rehearsal.
3. **Продуктовое решение:** business hours, перерывы, внерабочие записи и вертикальная календарная сетка.
4. **Продуктовое решение:** accounting basis и требования к полноценной финансовой выгрузке.
5. **P2:** SaaS Admin user management/MRR, branches/permissions, online booking и внешние integrations.

## Verification gates

- После каждого P0/P1 среза: focused feature tests + tenant/role test; обновлять этот roadmap только после сверки результата.
- Перед merge/release: `php artisan test`, `npx tsc -p tsconfig.json --noEmit`, `npm run build`, `composer validate --no-check-publish`, `composer audit`.
- Перед клиентской установкой: backup/restore rehearsal и полный migration/smoke run на целевой MySQL 8 либо MariaDB; SQLite suite этот gate не заменяет.
