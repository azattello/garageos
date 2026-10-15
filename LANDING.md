# GarageOS — Landing / Marketing Website

> **Статус:** Implementation Specification  
> **Проект:** GarageOS  
> **Назначение:** публичный маркетинговый сайт SaaS-продукта  
> **Главное правило:** существующий GarageOS уже работает. Landing должен быть добавлен вокруг существующего продукта, а не вместо него.

---

# 1. CRITICAL RULES

Перед началом разработки ОБЯЗАТЕЛЬНО изучить существующий проект.

## Нельзя

- переписывать GarageOS;
- менять существующую архитектуру;
- менять существующую БД без необходимости;
- менять существующие модели;
- создавать дубликаты существующих моделей;
- менять существующую бизнес-логику;
- менять существующую систему авторизации;
- менять существующую систему организаций;
- менять существующие тарифы;
- менять существующие subscription-механизмы;
- менять существующий UI самого приложения;
- заменять Bklit UI;
- создавать второй параллельный дизайн-системный слой для SaaS application;
- удалять существующие страницы;
- менять существующие routes без необходимости.

## Главное правило

**Existing GarageOS = source of truth.**

Если существующий код уже решает задачу — использовать его.

Если неясно, как что-то устроено — сначала исследовать код.

Не предполагать.

---

# 2. CURRENT PRODUCT

GarageOS уже является рабочей SaaS-системой для автосервисов.

В системе уже существуют:

- авторизация;
- организации;
- пользователи;
- роли;
- клиенты;
- автомобили;
- записи;
- рабочие процессы автосервиса;
- аналитика;
- существующий UI;
- существующие тарифы;
- существующая subscription logic.

Особенно важно:

> В GarageOS уже есть **два рабочих тарифных плана**.

Landing должен использовать именно существующие тарифы.

Нельзя придумывать новые тарифы только потому, что их нет в ТЗ.

---

# 3. STACK

Сохранять существующий stack:

- Laravel
- React
- TypeScript
- Inertia.js
- Tailwind CSS
- Bklit UI
- Chart.js
- Node.js только для development/build

Не добавлять новый framework без необходимости.

---

# 4. LANDING ARCHITECTURE

Landing является публичной частью GarageOS.

Концептуально:

```text
GarageOS
│
├── Public Landing
│   ├── /
│   ├── /features
│   ├── /pricing
│   ├── /demo
│   ├── /faq
│   ├── /contact
│   ├── /login
│   ├── /register
│   ├── /terms
│   └── /privacy
│
├── Existing SaaS Application
│
└── Super Admin
```

Не создавать отдельное приложение, если существующая архитектура позволяет аккуратно добавить public routes/pages.

---

# 5. DESIGN SYSTEM

Перед написанием большого количества frontend-кода создать:

```text
/design/LANDING_DESIGN.md
```

Использовать следующие skills/resources:

### Primary

`web-design`

GitHub:

https://github.com/xiaopu-ai/web-design

Назначение:

- visual direction;
- design system;
- typography;
- colors;
- spacing;
- layout;
- components;
- responsive rules;
- animation rules;
- accessibility;
- anti-generic-SaaS rules.

### Secondary

`frontend-design`

GitHub:

https://github.com/wenkang-deepblue/frontend-design

Использовать для:

- production-quality frontend;
- visual hierarchy;
- composition;
- responsive behavior;
- UI polish.

### Optional

`21st-dev/skill`

GitHub:

https://github.com/21st-dev/skill

Использовать только если действительно нужны отдельные качественные React/shadcn components.

Не превращать 21st.dev в обязательную dependency всей системы.

---

# 6. DESIGN DIRECTION

Landing должен ощущаться как:

- premium B2B SaaS;
- automotive technology;
- professional;
- fast;
- reliable;
- modern;
- clean;
- practical.

Не должен ощущаться как:

- generic AI startup;
- crypto website;
- gaming website;
- Web3 landing;
- template marketplace;
- Dribbble concept;
- чрезмерный glassmorphism.

---

# 7. PRODUCT-FIRST DESIGN

Главный принцип:

> **REAL PRODUCT > DECORATION**

Landing должен продавать настоящий GarageOS.

Посетитель должен увидеть реальные части системы.

Не использовать fake dashboard screenshots, если соответствующий экран уже существует в GarageOS.

---

# 8. REAL GarageOS UI

Перед разработкой найти в существующем коде:

- Sidebar;
- Header;
- Dashboard;
- Analytics;
- Charts;
- Statistics cards;
- Clients;
- Cars;
- Appointments;
- Work Orders;
- Services;
- Employees;
- Settings;
- Tables;
- Forms;
- Dialogs;
- Notifications;
- Bklit components.

Если компонент уже существует:

**использовать его.**

Не создавать визуальную копию.

---

# 9. BROWSER PRODUCT SHOWCASE

Главный visual element Landing:

## Real GarageOS Browser Window

GarageOS должен демонстрироваться внутри реалистичного browser/desktop window.

Концепция:

```text
┌─────────────────────────────────────────────────────────┐
│ ● ● ●      GarageOS — Автосервис                ─ □ × │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  [REAL GarageOS SIDEBAR]       [REAL DASHBOARD]         │
│                                                         │
│  Главная                        Выручка                 │
│  Клиенты                        Заказы                  │
│  Автомобили                     Клиенты                 │
│  Записи                                                 │
│  Заказы                         [REAL CHART]             │
│  Аналитика                                              │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

Это не должна быть просто PNG-картинка.

По возможности использовать реальные React-компоненты GarageOS.

---

# 10. UBUNTU-STYLE WINDOW

Browser showcase может визуально использовать Ubuntu/Linux-style desktop window:

- rounded window;
- top chrome;
- window controls;
- address bar;
- subtle shadow;
- realistic proportions.

Но:

**не копировать Ubuntu буквально.**

Это должен быть GarageOS product showcase.

---

# 11. HERO

Hero:

### Headline

> Операционная система для современного автосервиса

### Subheadline

> Клиенты, автомобили, записи, заказ-наряды и аналитика — в одной системе.

Текст должен быть изменен, если существующие реальные функции GarageOS требуют другой формулировки.

Не рекламировать функцию, которой нет.

CTA:

```text
Получить демо
```

Secondary:

```text
Посмотреть систему
```

Справа/ниже:

**Real GarageOS Browser Preview**

---

# 12. HERO ANIMATION

Использовать:

- Motion / Framer Motion / Motion for React;
- CSS animations только для простых эффектов.

Hero animation:

```text
page load
    ↓
browser window fade
    ↓
translateY
    ↓
dashboard appears
    ↓
sidebar appears
    ↓
cards stagger
    ↓
chart animation
```

Использовать:

- opacity;
- transform;
- scale;
- stagger;
- spring.

Не использовать тяжелые постоянные animations.

---

# 13. PRODUCT SHOWCASE

Создать блок:

## GarageOS в работе

Tabs:

```text
Dashboard
Клиенты
Автомобили
Записи
Заказ-наряды
Аналитика
```

При переключении:

- меняется реальный product preview;
- используется Motion transition;
- интерфейс максимально основан на реальных GarageOS components.

---

# 14. DASHBOARD SHOWCASE

Показать:

- real statistics cards;
- real charts;
- real layout;
- real visual language.

Не делать второй dashboard только для landing.

Если существующий dashboard можно безопасно использовать:

использовать его.

Если нельзя напрямую использовать страницу:

создать presentation component на базе существующих компонентов.

---

# 15. ANALYTICS SHOWCASE

Особенно важно показать аналитику.

Использовать существующие:

- Chart.js;
- analytics components;
- statistics cards.

Не создавать отдельную chart library.

Пример marketing message:

> Видите состояние бизнеса, а не просто список заказов.

Текст адаптировать под реальные возможности системы.

---

# 16. CLIENTS / CARS SHOWCASE

Показать:

```text
Client
   ↓
Car
   ↓
Appointments
   ↓
Work Orders
   ↓
Service history
```

Использовать реальные компоненты.

Demo data должна быть искусственной.

---

# 17. CALENDAR SHOWCASE

Если календарь уже существует:

использовать существующий.

Показать:

- записи;
- даты;
- автомобили;
- клиентов;
- статусы.

Animation:

```text
calendar
   ↓
appointment appears
   ↓
appointment opens
```

---

# 18. WORK ORDER SHOWCASE

Показать реальный workflow:

```text
Client
   ↓
Vehicle
   ↓
Work Order
   ↓
Services
   ↓
Status
```

Использовать существующий GarageOS UI.

---

# 19. SIDEBAR SHOWCASE

Использовать настоящий GarageOS Sidebar.

Не рисовать fake Sidebar.

Можно добавить interaction:

```text
Dashboard
Clients
Cars
Appointments
Work Orders
Analytics
Settings
```

Hover → active state → соответствующий product preview.

---

# 20. HOW IT WORKS

Три шага:

```text
01
Создайте организацию

02
Добавьте клиентов и автомобили

03
Управляйте сервисом в GarageOS
```

Тексты проверить относительно реального onboarding flow.

---

# 21. FEATURES

Features должны соответствовать реальному продукту.

Пример:

### Клиенты

Вся клиентская база в одном месте.

### Автомобили

История автомобилей и обслуживания.

### Записи

Управление загрузкой автосервиса.

### Заказ-наряды

Контроль работ и статусов.

### Аналитика

Контроль ключевых показателей.

Не добавлять features, которых нет.

---

# 22. PRICING

Использовать существующие два тарифа.

Источник:

**existing database/configuration.**

Не хардкодить цены в нескольких местах.

Landing должен получать:

- plan name;
- description;
- price;
- billing period;
- features;
- status;

из существующей системы, если архитектура это позволяет.

Не изменять существующую tariff logic.

---

# 23. CTA

Основные CTA:

```text
Получить демо
```

```text
Попробовать GarageOS
```

```text
Начать работу
```

Конкретный CTA выбрать исходя из существующего onboarding.

---

# 24. FAQ

Минимальные вопросы:

- Что такое GarageOS?
- Для какого автосервиса подходит?
- Какие функции входят в систему?
- Как подключиться?
- Какие тарифы доступны?
- Можно ли получить демо?
- Где хранятся данные?
- Есть ли поддержка?

Ответы должны соответствовать реальному продукту.

---

# 25. FOOTER

Footer:

- GarageOS logo;
- navigation;
- features;
- pricing;
- FAQ;
- contact;
- login;
- terms;
- privacy;
- copyright.

---

# 26. SEO

Добавить:

- title;
- meta description;
- Open Graph;
- canonical;
- favicon;
- semantic HTML;
- proper headings;
- structured metadata, если уместно.

Основные keywords:

- CRM для автосервиса;
- программа для автосервиса;
- учет автосервиса;
- управление автосервисом;
- GarageOS;
- программа СТО;
- CRM СТО.

Не делать keyword stuffing.

---

# 27. RESPONSIVE

Обязательные breakpoints:

- desktop;
- laptop;
- tablet;
- mobile.

На mobile browser showcase должен адаптироваться.

Не просто уменьшать desktop UI до 320px.

Можно:

- horizontal scroll внутри controlled showcase;
- crop;
- mobile-specific composition;
- simplified preview.

---

# 28. ACCESSIBILITY

Поддержать:

- keyboard navigation;
- focus states;
- semantic HTML;
- contrast;
- aria labels;
- reduced motion.

Обязательно:

```css
@media (prefers-reduced-motion: reduce)
```

---

# 29. PERFORMANCE

Landing должен быть быстрым.

Использовать:

- lazy loading;
- dynamic imports;
- optimized images;
- WebP/AVIF;
- code splitting;
- transform/opacity animations.

Не загружать весь GarageOS application bundle только ради showcase.

---

# 30. DEMO DATA

Использовать fictional data.

Пример:

```text
Toyota Camry
KZ 123 ABC

Замена масла
Диагностика
Ходовая часть
```

Не использовать production data.

---

# 31. ROUTING

Предпочтительная структура:

```text
/
/features
/pricing
/demo
/faq
/contact
/login
/register
/terms
/privacy
```

Но сначала проверить существующие routes.

Не конфликтовать с существующим SaaS routing.

---

# 32. IMPLEMENTATION PHASES

## Phase 0 — Audit

Никаких изменений.

Проверить:

- Laravel structure;
- React structure;
- Inertia pages;
- routes;
- models;
- existing UI;
- Bklit;
- dashboard;
- analytics;
- plans;
- subscriptions;
- auth.

Результат:

```text
LANDING_AUDIT.md
```

---

## Phase 1 — Design

Создать:

```text
/design/LANDING_DESIGN.md
```

Определить:

- colors;
- typography;
- spacing;
- layout;
- components;
- browser showcase;
- animations;
- responsive;
- accessibility.

---

## Phase 2 — Landing Foundation

Создать:

- layout;
- navbar;
- footer;
- typography;
- responsive system;
- CTA system.

---

## Phase 3 — Hero

Создать:

- hero;
- browser window;
- real GarageOS UI;
- animations.

---

## Phase 4 — Product Showcase

Создать:

- dashboard;
- clients;
- cars;
- calendar;
- work orders;
- analytics.

---

## Phase 5 — Marketing Sections

Добавить:

- features;
- how it works;
- pricing;
- FAQ;
- CTA.

---

## Phase 6 — SEO / Performance

Добавить:

- SEO;
- OpenGraph;
- performance optimization;
- accessibility.

---

## Phase 7 — QA

Проверить:

- desktop;
- mobile;
- tablet;
- navigation;
- animations;
- pricing;
- CTA;
- login/register;
- existing SaaS.

---

# 33. FINAL ACCEPTANCE CRITERIA

Landing готов только если:

- GarageOS понятно за первые 5 секунд;
- Hero показывает реальный GarageOS;
- product preview находится внутри browser window;
- используются реальные UI components;
- используется реальный Sidebar;
- используется реальная Analytics;
- используются реальные dashboard components;
- два существующих тарифа отображаются корректно;
- нет fake features;
- Motion используется качественно;
- responsive работает;
- mobile работает;
- accessibility работает;
- existing SaaS не сломан;
- existing auth не сломан;
- existing subscriptions не сломаны;
- build проходит;
- tests проходят, если они существуют.

---

# FINAL PRINCIPLE

> **Не создавай красивый сайт, который рассказывает о GarageOS.**
>
> **Создай красивый сайт, который показывает GarageOS.**