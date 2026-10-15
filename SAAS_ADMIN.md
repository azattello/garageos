# GarageOS — Super Admin / SaaS Owner Panel

> **Статус:** Implementation Specification  
> **Назначение:** внутренняя панель владельца SaaS GarageOS  
> **Главное правило:** GarageOS уже является рабочим продуктом. Super Admin должен быть добавлен поверх существующей архитектуры.

---

# 1. CRITICAL RULE

**НЕ ПЕРЕПИСЫВАТЬ EXISTING GarageOS.**

Перед любой модификацией:

1. изучить проект;
2. изучить database schema;
3. изучить models;
4. изучить authentication;
5. изучить organizations;
6. изучить users;
7. изучить roles;
8. изучить plans;
9. изучить subscriptions;
10. изучить payments, если существуют;
11. изучить existing finance;
12. изучить routes;
13. изучить policies/middleware.

После этого составить:

```text
SAAS_ADMIN_AUDIT.md
```

И только после аудита начинать implementation.

---

# 2. EXISTING PRODUCT

GarageOS уже имеет:

- работающую SaaS-систему;
- организации;
- пользователей;
- существующую авторизацию;
- существующие тарифы;
- два существующих тарифных плана;
- работающую subscription logic;
- существующие business modules.

Super Admin должен управлять этой системой.

---

# 3. SUPER ADMIN PURPOSE

Super Admin — это панель владельца GarageOS.

Он не является обычным пользователем организации.

У него есть доступ к:

- организациям;
- пользователям;
- тарифам;
- подпискам;
- платежам;
- SaaS-финансам;
- аналитике;
- support;
- audit logs;
- системным настройкам.

---

# 4. IMPORTANT TERMINOLOGY

Разделять:

### Organization

Автосервис-клиент GarageOS.

### User

Пользователь конкретной организации.

### Subscription

Подписка организации на GarageOS.

### Plan

Тариф GarageOS.

### Payment

Платеж клиента GarageOS.

### SaaS Finance

Финансы самого GarageOS.

Это НЕ финансы конкретного автосервиса.

---

# 5. DO NOT DUPLICATE MODELS

Перед созданием моделей проверить существующие.

Нельзя создавать:

```text
Organization2
User2
Plan2
Subscription2
Client2
Payment2
```

или аналогичные дубли.

Если существующая модель подходит:

**использовать существующую модель.**

Если не хватает полей:

добавить минимально необходимые поля с миграцией.

---

# 6. SUPER ADMIN SECURITY

Super Admin должен быть отделен от tenant-level permissions.

Обычный пользователь:

```text
Organization A
    ↓
Users
```

Super Admin:

```text
GarageOS
    ↓
ALL ORGANIZATIONS
```

Обычный пользователь никогда не должен получить Super Admin доступ через:

- frontend;
- изменение request;
- изменение organization_id;
- изменение role;
- direct URL.

Все проверки должны происходить server-side.

---

# 7. ADMIN ROUTE

Предпочтительно:

```text
/admin
```

Но сначала проверить существующие routes.

Не создавать route conflict.

---

# 8. ADMIN NAVIGATION

Предварительная структура:

```text
Super Admin
│
├── Dashboard
├── Organizations
├── Users
├── Plans
├── Subscriptions
├── Payments
├── Finance
├── Analytics
├── Support
├── Audit Logs
└── Settings
```

Если какой-то module уже существует:

reuse.

---

# 9. ADMIN UI

Super Admin должен иметь отдельный UI.

Он НЕ должен менять UI основной GarageOS application.

Можно использовать:

- Tailwind;
- существующие base components;
- Bklit components, если они подходят;
- таблицы;
- charts;
- filters;
- drawers;
- modals.

Но Super Admin должен ощущаться как:

**professional SaaS operations console.**

---

# 10. ADMIN DESIGN SYSTEM

Создать:

```text
/design/ADMIN_DESIGN.md
```

Определить:

- sidebar;
- topbar;
- tables;
- filters;
- forms;
- cards;
- charts;
- modal;
- drawer;
- status badges;
- pagination;
- empty states;
- loading;
- error states.

Главный приоритет:

**information density + clarity + speed.**

---

# 11. SUPER ADMIN DASHBOARD

Dashboard должен показывать:

### Organizations

- total organizations;
- active;
- trial;
- suspended;
- cancelled.

### Subscriptions

- active subscriptions;
- subscription distribution;
- plan distribution.

### Revenue

- MRR;
- ARR;
- revenue this month;
- revenue this year.

### Expenses

- monthly expenses;
- yearly expenses.

### Net Revenue

```text
Revenue - Expenses
```

### Growth

- new organizations;
- new subscriptions;
- churn;
- growth rate.

---

# 12. DASHBOARD CARDS

Например:

```text
Organizations
128
+12 this month
```

```text
MRR
1 920 000 ₸
+8.4%
```

```text
Revenue
2 430 000 ₸
this month
```

```text
Expenses
740 000 ₸
this month
```

```text
Net Revenue
1 690 000 ₸
this month
```

Но реальные цифры должны вычисляться из данных.

Не hardcode.

---

# 13. ORGANIZATIONS

Page:

```text
/admin/organizations
```

Table:

```text
Organization
Owner
Plan
Status
Users
Clients
Cars
Subscription
Created
Actions
```

Filters:

- search;
- status;
- plan;
- subscription;
- created date.

---

# 14. ORGANIZATION DETAIL

Page:

```text
/admin/organizations/{organization}
```

Показывать:

### General

- organization name;
- owner;
- status;
- created date.

### Subscription

- current plan;
- status;
- start date;
- end date;
- auto-renew;
- payment status.

### Usage

- users;
- clients;
- cars;
- appointments;
- work orders.

### Activity

- recent activity;
- admin actions;
- subscription events.

### Finance

- payments;
- amount paid;
- outstanding amount.

---

# 15. ORGANIZATION ACTIONS

Super Admin может:

```text
Create
Activate
Suspend
Reactivate
Archive
```

Но destructive actions должны иметь:

- confirmation;
- clear warning;
- audit log.

---

# 16. CREATE ORGANIZATION

Super Admin должен иметь возможность вручную создать организацию.

Минимум:

```text
Organization name
Owner
Email / contact
Plan
Subscription period
Status
```

Не создавать отдельную organization logic.

Использовать существующую.

---

# 17. USERS

Page:

```text
/admin/users
```

Показывать:

- name;
- email;
- organization;
- role;
- status;
- last login;
- created date.

Filters:

- organization;
- role;
- status;
- search.

---

# 18. PLANS

Page:

```text
/admin/plans
```

ВАЖНО:

В GarageOS уже существуют **два тарифа**.

Не создавать новые планы.

Сначала найти существующую plan structure.

Admin должен управлять тем, что уже существует.

Возможные действия:

- view;
- edit;
- activate/deactivate;
- change price;
- change features;

но только если текущая архитектура уже позволяет безопасно это делать.

---

# 19. SUBSCRIPTIONS

Page:

```text
/admin/subscriptions
```

Table:

```text
Organization
Plan
Status
Start
End
Amount
Payment
Actions
```

Statuses:

```text
Active
Trial
Expired
Suspended
Cancelled
```

Использовать существующие statuses, если они уже определены.

Не создавать conflicting enum.

---

# 20. MANUAL SUBSCRIPTION MANAGEMENT

На первом этапе платежи могут обрабатываться вручную.

Super Admin должен иметь возможность:

### Activate

Активировать подписку.

### Extend

Продлить.

### Change Plan

Изменить тариф, если поддерживается текущей системой.

### Suspend

Приостановить.

### Reactivate

Возобновить.

### Cancel

Отменить.

Каждое действие:

**audit log.**

---

# 21. SUBSCRIPTION FORM

Например:

```text
Organization
Plan
Start date
End date
Amount
Payment status
Notes
```

После сохранения:

- проверить permissions;
- validate;
- transaction;
- update subscription;
- create audit event.

---

# 22. PAYMENTS

Page:

```text
/admin/payments
```

Показывать:

```text
Date
Organization
Plan
Amount
Method
Status
Invoice
Actions
```

Statuses:

```text
Paid
Pending
Failed
Refunded
```

Использовать существующую payment architecture, если она есть.

---

# 23. MANUAL PAYMENT

На начальном этапе можно добавить:

```text
Add payment
```

Поля:

```text
Organization
Amount
Date
Method
Description
Subscription
```

После добавления:

- payment record;
- subscription update if needed;
- audit log.

---

# 24. SAAS FINANCE

Критически важно:

## SaaS Finance ≠ Organization Finance

Например:

### GarageOS Revenue

```text
Subscriptions
Implementation
Support
Add-ons
Other
```

### GarageOS Expenses

```text
Hosting
Domain
Servers
AI
SMS
Email
Advertising
Software
Contractors
Other
```

---

# 25. FINANCE PAGE

Route:

```text
/admin/finance
```

Показывать:

```text
Revenue
Expenses
Net Revenue
```

Формула:

```text
Net Revenue = Revenue - Expenses
```

---

# 26. REVENUE

Categories:

```text
Subscription
Setup
Support
Add-on
Other
```

Каждая запись:

```text
Amount
Date
Category
Organization
Description
```

---

# 27. EXPENSES

Categories:

```text
Hosting
Domain
Infrastructure
AI
SMS
Email
Marketing
Software
Contractor
Other
```

Каждая запись:

```text
Amount
Date
Category
Description
Vendor
```

---

# 28. FINANCE ANALYTICS

Charts:

### Revenue over time

```text
Day
Week
Month
Year
```

### Expenses over time

### Net Revenue

### Revenue by category

### Expenses by category

### Revenue by plan

---

# 29. SaaS METRICS

Если данных достаточно, добавить:

### MRR

Monthly Recurring Revenue.

### ARR

```text
ARR = MRR × 12
```

### ARPU

```text
ARPU = MRR / Active Paying Organizations
```

### Churn

Отдельно определить метод расчета и документировать его.

### New MRR

MRR from new subscriptions.

### Expansion MRR

Если поддерживается изменение тарифов.

### Contraction MRR

Если поддерживается downgrade.

Не показывать метрику, если для нее нет надежных данных.

---

# 30. ANALYTICS

Page:

```text
/admin/analytics
```

Разделы:

### Growth

- organizations;
- subscriptions;
- users.

### Revenue

- MRR;
- ARR;
- ARPU;
- revenue.

### Churn

- cancelled;
- expired;
- churn rate.

### Plans

- plan distribution;
- plan revenue.

### Usage

- users;
- clients;
- cars;
- appointments;
- work orders.

---

# 31. DATE FILTER

Global analytics filters:

```text
Today
7 days
30 days
90 days
This year
Custom
```

Все charts должны использовать одинаковый date filter.

---

# 32. SUPPORT

Создать основу для:

```text
/admin/support
```

Если полноценный support module еще не нужен:

сделать architecture-ready placeholder.

В будущем:

```text
Ticket
Organization
User
Priority
Status
Messages
Created
Updated
```

Statuses:

```text
Open
In Progress
Waiting
Resolved
Closed
```

Не создавать полноценный сложный ticketing system без необходимости на текущем этапе.

---

# 33. AUDIT LOGS

Page:

```text
/admin/audit-logs
```

Логировать критические действия:

```text
Admin login
Admin logout
Organization created
Organization suspended
Organization reactivated
Subscription activated
Subscription extended
Subscription changed
Subscription cancelled
Payment created
Payment refunded
User role changed
Settings changed
Impersonation
```

---

# 34. AUDIT LOG DATA

Каждая запись:

```text
Actor
Action
Target
Organization
Metadata
IP
User Agent
Created At
```

Не хранить sensitive secrets.

---

# 35. IMPERSONATION

В будущем можно добавить:

```text
Login as organization admin
```

Но:

- только Super Admin;
- обязательный audit log;
- visible impersonation state;
- возможность выйти;
- нельзя скрывать факт impersonation.

Если реализация не нужна сейчас:

подготовить architecture, но не реализовывать без необходимости.

---

# 36. SETTINGS

Page:

```text
/admin/settings
```

Возможные разделы:

### General

GarageOS name/logo/contact.

### System

Maintenance settings.

### Notifications

Email/SMS/system notifications.

### Security

Admin security settings.

### Billing

Payment configuration.

Не показывать secrets в UI.

---

# 37. PAYMENT INTEGRATION

На первом этапе:

**не обязательно подключать автоматический acquiring.**

Manual payments могут быть достаточны.

Если позже понадобится recurring billing:

создать abstraction:

```text
PaymentGatewayInterface
```

чтобы можно было подключить:

- Halyk;
- Kaspi;
- Stripe;
- другой provider.

Не привязывать всю subscription architecture к одному gateway.

---

# 38. WEBHOOKS

При появлении automated payments:

обязательно:

- webhook validation;
- idempotency;
- event logging;
- transaction handling;
- retry handling.

Не считать payment successful только по frontend redirect.

---

# 39. SECURITY

Обязательно:

### Server-side authorization

Frontend permissions недостаточно.

### Organization isolation

Tenant users не должны видеть другие organizations.

### Super Admin isolation

Super Admin routes недоступны обычным пользователям.

### Validation

Все admin mutations валидируются server-side.

### CSRF

Сохранять существующую защиту.

### Audit

Critical mutations логируются.

### Secrets

Никаких:

- API keys;
- payment secrets;
- database credentials;

в frontend.

---

# 40. DATABASE

Перед migration:

проверить existing schema.

Новые таблицы создавать только если сущности реально отсутствуют.

Возможные новые SaaS-level tables:

```text
saas_revenues
saas_expenses
audit_logs
```

Но перед созданием проверить, нет ли уже:

```text
revenues
expenses
audit_logs
```

или эквивалентов.

Не создавать дубли.

---

# 41. DATA MODEL PRINCIPLE

Предпочтительно:

```text
Organization
     │
     └── Subscription
              │
              └── Plan
```

Payment:

```text
Organization
     │
     └── Payments
```

SaaS finance:

```text
GarageOS
 ├── Revenue
 └── Expenses
```

Это отдельный уровень от финансов автосервиса.

---

# 42. TRANSACTIONS

Operations:

- create organization;
- activate subscription;
- change subscription;
- create payment;
- refund;

должны использовать database transactions там, где операция изменяет несколько связанных сущностей.

---

# 43. UI TABLES

Для больших списков использовать:

- pagination;
- server-side filtering;
- search;
- sorting;
- date filters.

Не загружать тысячи организаций/users/payments сразу.

---

# 44. EMPTY STATES

Каждая страница должна иметь нормальный empty state.

Например:

```text
Нет организаций

Когда первая организация будет создана,
она появится здесь.
```

---

# 45. LOADING STATES

Использовать:

- skeleton;
- loading indicators;
- disabled actions;
- optimistic UI только там, где безопасно.

---

# 46. ERROR STATES

Ошибки должны быть понятными.

Не:

```text
500
```

а:

```text
Не удалось активировать подписку.

Попробуйте еще раз.
```

При этом реальные technical details должны оставаться в logs.

---

# 47. ADMIN DASHBOARD VISUALIZATION

Использовать существующий Chart.js, если он уже используется проектом.

Не подключать новую chart library без необходимости.

---

# 48. IMPLEMENTATION PHASES

## Phase 0 — Audit

Никаких изменений.

Исследовать:

```text
routes
models
migrations
auth
roles
organizations
plans
subscriptions
payments
finance
analytics
```

Создать:

```text
SAAS_ADMIN_AUDIT.md
```

---

## Phase 1 — Authorization

Реализовать Super Admin access.

Проверить:

- middleware;
- policies;
- server-side authorization.

---

## Phase 2 — Admin Shell

Создать:

```text
/admin
```

и:

- sidebar;
- header;
- breadcrumbs;
- dashboard shell;
- responsive layout.

---

## Phase 3 — Organizations

Реализовать:

- list;
- search;
- filters;
- detail;
- create;
- status management.

---

## Phase 4 — Subscriptions

Реализовать:

- list;
- detail;
- activate;
- extend;
- suspend;
- reactivate;
- cancel.

---

## Phase 5 — Payments

Реализовать:

- payment list;
- payment detail;
- manual payment;
- payment status.

---

## Phase 6 — Finance

Реализовать:

- revenue;
- expenses;
- net revenue;
- categories;
- charts.

---

## Phase 7 — Analytics

Реализовать:

- MRR;
- ARR;
- ARPU;
- churn;
- growth;
- plan distribution.

Только метрики, которые можно корректно вычислить.

---

## Phase 8 — Audit Logs

Реализовать:

- audit events;
- filtering;
- detail.

---

## Phase 9 — Support Foundation

Добавить foundation для support.

---

## Phase 10 — QA

Проверить:

- authorization;
- tenant isolation;
- admin isolation;
- subscriptions;
- payments;
- finance;
- analytics;
- audit logs;
- responsive.

---

# 49. TESTING

Обязательно проверить сценарии:

### Normal User

Не может открыть:

```text
/admin
```

### Organization Admin

Не может увидеть:

```text
Organization B
```

### Super Admin

Может:

```text
Organization A
Organization B
Organization C
```

### Subscription

```text
Activate
→ Active
```

```text
Extend
→ end_date changes
```

```text
Suspend
→ suspended
```

```text
Reactivate
→ active
```

---

# 50. FINANCIAL TESTS

Проверить:

```text
Revenue = 1 000 000
Expenses = 300 000

Net Revenue = 700 000
```

MRR:

```text
3 organizations × 15 000
+
2 organizations × 30 000

MRR = 105 000
```

Но реальные тарифы брать из существующей системы.

---

# 51. DO NOT BREAK EXISTING APP

После каждой крупной фазы проверить:

- login;
- organization switching;
- existing dashboard;
- clients;
- cars;
- appointments;
- work orders;
- analytics;
- subscriptions;
- existing tariff behavior.

---

# 52. NO UNNECESSARY REFACTOR

Запрещено:

> «Пока я здесь, перепишу архитектуру».

Не делать:

- mass refactoring;
- renaming existing models;
- moving hundreds of files;
- replacing libraries;
- rewriting authentication;
- rewriting subscription system.

Если рефакторинг действительно необходим:

1. объяснить почему;
2. определить scope;
3. сделать минимальное изменение;
4. проверить regression.

---

# 53. FINAL ACCEPTANCE CRITERIA

Super Admin считается готовым, если:

- существует отдельный защищенный `/admin`;
- обычный пользователь не может попасть в Super Admin;
- организациями можно управлять;
- существующие два тарифа отображаются корректно;
- subscriptions можно управлять;
- payments можно видеть/добавлять согласно текущей architecture;
- SaaS Revenue отделен от Organization Finance;
- Expenses работают;
- Net Revenue рассчитывается;
- MRR/ARR/ARPU считаются корректно там, где есть данные;
- analytics работают;
- audit logs работают;
- critical actions логируются;
- tenant isolation сохранен;
- existing GarageOS продолжает работать;
- existing tariff logic не сломана;
- migrations проходят;
- build проходит;
- tests проходят, если они существуют.

---

# FINAL PRINCIPLE

> **Super Admin — это операционный центр GarageOS, а не новая версия GarageOS.**

Он должен дать владельцу SaaS возможность:

```text
SEE
→ Understand the business

MANAGE
→ Organizations & subscriptions

TRACK
→ Payments & revenue

CONTROL
→ Access & system state

ANALYZE
→ SaaS growth

AUDIT
→ Critical actions
```

При этом существующая рабочая система GarageOS остается нетронутой настолько, насколько это возможно.