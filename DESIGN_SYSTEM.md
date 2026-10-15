ТЗ — GarageOS DESIGN SYSTEM
01. Общая концепция

GarageOS — современный B2B SaaS для управления автосервисами.

Визуальное направление:

OpenPanel × Linear × modern automotive software

Интерфейс должен ощущаться как:

профессиональный;
дорогой;
технологичный;
быстрый;
плотный;
data-driven;
минималистичный;
рассчитанный на ежедневную работу.

Интерфейс не должен выглядеть как сайт автосервиса.

Запрещено визуально превращать продукт в:

автомобильный лендинг;
racing UI;
tuning UI;
dashboard автомобиля;
неоновый cyberpunk;
типичный CRM-шаблон.

GarageOS — это software product, а не сайт СТО.

02. Основная тема
DARK MODE — DEFAULT

Тёмная тема является основной и первой темой продукта.

При первом входе пользователь видит именно Dark Mode.

Светлая тема может существовать в будущем, но не является приоритетом MVP.

Основной background
--background: #090A0C;
Основная поверхность
--surface: #101216;
Вторичная поверхность
--surface-secondary: #15181D;
Elevated surface
--surface-elevated: #191C22;
Border
--border: #242830;
Border subtle
--border-subtle: #1B1E24;
03. Цветовая система

Основные цвета должны быть нейтральными.

Background

#090A0C

Text Primary

#F4F5F7

Text Secondary

#A1A6B0

Text Muted

#6F7580

Border

#242830

Accent
Acid Green

#B8FF3D

Используется ограниченно:

primary CTA;
active navigation;
positive metrics;
selected states;
important highlights;
progress;
некоторые графики.

Не заливать весь интерфейс зелёным.

Акцент должен занимать примерно 5–10% визуального пространства.

04. Semantic colors
Success
#72E06A
Warning
#F5C451
Error
#F06464
Info
#62A8FF

Но semantic colors также использовать умеренно.

05. Главный принцип интерфейса
Dark background ≠ чёрные карточки повсюду.

Не делать:

BLACK
BLACK
BLACK
BLACK

Вместо этого использовать небольшую разницу между слоями:

Background
    ↓
Surface
    ↓
Elevated surface
    ↓
Hover
    ↓
Active

Это создаёт глубину без огромных теней.

06. Sidebar

Sidebar является одним из главных элементов GarageOS.

Ориентироваться на плотность и структуру OpenPanel.

5
Desktop width
240px

В collapsed состоянии:

64px

Sidebar фиксированный.

position: fixed
height: 100vh
07. Sidebar structure

Верх:

[G] GarageOS

или логотип GarageOS.

Ниже:

WORKSPACE
⌂  Dashboard
▣  Appointments
◉  Work Orders
♙  Customers
🚗 Vehicles
◫  Services
▤  Inventory
MANAGEMENT
◉  Employees
▥  Reports
◌  Analytics
SYSTEM
⚙  Settings

Но не использовать emoji.

Каждый пункт должен иметь Lucide icon.

08. Иконки

Это обязательное правило.

Каждый navigation item должен иметь собственную иконку.

Использовать:

Lucide Icons

Иконки должны быть:

outline;
16–18px;
одинаковой визуальной толщины;
одинакового размера;
аккуратно выровнены.

Пример:

Dashboard        LayoutDashboard
Appointments     CalendarDays
Work Orders      ClipboardList
Customers        Users
Vehicles         CarFront
Services         Wrench
Inventory        Package
Employees        UsersRound
Analytics        ChartNoAxesCombined
Reports          FileChartColumn
Settings         Settings

Не смешивать разные icon libraries.

09. Sidebar active state

Active navigation item:

background: rgba(...)
color: primary

Можно использовать лёгкий зелёный tint.

Например:

background: rgba(184, 255, 61, 0.08);
color: #B8FF3D;

Но без яркой зелёной плашки.

Можно добавить небольшой indicator:

│ Dashboard

или аккуратную левую/внутреннюю линию.

10. Sidebar hover

Hover должен быть очень мягким:

background: #15181D;

Без:

bounce;
scale;
glow;
огромных анимаций.

GarageOS — рабочий инструмент.

11. Sidebar organization switcher

В верхней части:

┌──────────────────────┐
│ G  Garage Almaty   ⌄ │
└──────────────────────┘

Нажатие:

Garage Almaty
Garage Astana
+ Add organization

Это важно архитектурно, потому что GarageOS — SaaS с несколькими организациями.

12. User profile

Внизу sidebar:

┌──────────────────────┐
│ AZ  Azat             │
│     Administrator  ⋯ │
└──────────────────────┘

При клике:

Profile
Account
Notifications
Theme
Logout
13. Topbar

Основной контент не должен иметь огромный header.

Topbar компактный.

Пример:

Dashboard

Today
Sep 19, 2026

                         Search   🔔   AZ

Высота:

56–64px
14. Search

Global search:

⌕ Search...

Shortcut:

⌘ K

или Windows:

Ctrl K

Поиск должен потенциально искать:

клиентов;
автомобили;
VIN;
госномер;
заказ-наряды;
записи.
15. Buttons

Кнопки должны быть компактными.

Не использовать огромные rounded buttons.

Primary
+ New work order

Acid Green.

Secondary
Export

Dark surface + border.

Ghost
More

Прозрачная.

16. Button radius

Основной radius:

8px

Можно:

6px
8px
10px

Но не:

24px
9999px

за исключением badge/pill.

GarageOS должен быть sharp / professional, а не bubbly.

17. Cards

Карточки:

background: #101216;
border: 1px solid #242830;
border-radius: 10px;

Без огромных теней.

KPI card
Revenue

₸487,000

+18.4%

vs previous period

Название маленькое.

Число большое.

Изменение компактное.

18. KPI

Dashboard начинается с KPI.

Например:

Revenue
₸487,000
+18.4%

Orders
18
+12.5%

Average check
₸27,055
+4.2%

Occupancy
82%
+8.1%

Числа должны быть главным визуальным элементом.

19. Typography

Основной шрифт:

Inter

Fallback:

system-ui

Для маркетингового сайта можно рассмотреть Manrope, но внутри приложения основной Inter.

20. Typography scale
Page title
24–28px
font-weight: 600
Section title
16–18px
font-weight: 600
Body
14px
Small
12px
KPI
28–32px
font-weight: 600

Не использовать огромные 64–80px заголовки внутри dashboard.

21. Таблицы

GarageOS будет сильно зависеть от таблиц.

Таблица:

компактная;
много информации;
хорошие hover states;
sticky header при необходимости.

Например:

CUSTOMER       VEHICLE       SERVICE       STATUS       TOTAL
──────────────────────────────────────────────────────────────
Alex           BMW 530i      Oil change    Completed    ₸42,000
Daniyar        Camry         Suspension    In progress  ₸86,500
Murat          Lexus RX      Diagnostics   Waiting      ₸25,000

Row height:

48–56px
22. Status badges

Использовать маленькие pills.

● In progress
● Completed
● Waiting
● Cancelled

Пример:

border-radius: 999px;

Но только для status/tag.

23. Формы

Input:

background: #101216
border: #242830
height: 40px
radius: 8px

Focus:

border: accent

Но без огромного glow.

Placeholder:

#6F7580
24. Dropdowns

Dropdown должен визуально выглядеть как часть той же системы:

background #15181D
border #242830
radius 8px

Shadow:

очень мягкая.

25. Modal

Modal:

background: #101216
border: 1px solid #242830
border-radius: 12px;

Overlay:

rgba(0,0,0,0.65)

Не использовать белые модалки в dark mode.

26. Charts — Bklit UI

Все основные аналитические графики реализовывать через Bklit UI.

Bklit устанавливается как shadcn registry и добавляется через @bklit/....

Использовать:

Revenue

AreaChart

Orders

BarChart

Trends

LineChart

Occupancy

Gauge

Service distribution

RingChart

Workshop load

HeatmapChart

Customer conversion

FunnelChart

Workflow

SankeyChart

Bklit предоставляет эти типы компонентов официально.

27. Charts style

Графики должны быть частью UI, а не отдельными разноцветными картинками.

Не делать:

red
blue
yellow
purple
green
orange

как стандартный Excel.

Основная палитра:

Accent
Muted
Neutral
Semantic

Например:

Revenue     Acid Green
Previous    Gray
Expenses    Red
Target      Muted Gray
28. Grid

Основной desktop layout:

Sidebar 240px
+
Content

Content:

max-width: 1600px

Padding:

24px

На больших мониторах:

32px
29. Border philosophy

GarageOS должен использовать borders вместо shadows.

Это важная часть визуального характера.

То есть:

❌ огромные тени

❌ glassmorphism

❌ blur everywhere

❌ neon glow

Вместо этого:

✓ тонкие borders

✓ разные dark surfaces

✓ spacing

✓ typography

✓ hierarchy

30. Border radius philosophy

Основной:

8px

Cards:

10px

Modal:

12px

Buttons:

8px

Inputs:

8px

Badges:

999px
31. Animations

Минимальные.

Duration:

150–200ms

Использовать:

opacity;
background;
border;
transform небольшого масштаба.

Не использовать:

spring на каждом элементе;
bounce;
flashy transitions;
page-wide animations.

GarageOS должен ощущаться быстрым.

32. Loading

Skeleton должен соответствовать Dark Mode.

Например:

████████
██████████████
██████

с subtle shimmer.

Для chart loading можно использовать возможности Bklit, где loading labels/shimmering text предусмотрены компонентами.

33. Empty states

Не:

Ничего нет :(

А:

No vehicles yet

Add your first vehicle to start
tracking service history.

[ + Add vehicle ]

С маленькой Lucide icon.

34. Error states
Something went wrong

We couldn't load the work orders.

[ Try again ]

Минимально.

35. Icons everywhere

Это отдельное обязательное правило.

Sidebar

✓ icon

Buttons

✓ icon where meaningful

Tables

✓ status icons where useful

Empty states

✓ icon

Settings

✓ section icon

Dashboard

✓ contextual icons

Но не надо ставить иконку ради иконки на каждую кнопку.

Например:

[ + Add vehicle ]

хорошо.

[ 🔧 Add vehicle ]

не обязательно.

36. Automotive visual language

Автомобильная тема должна появляться через контент, а не через дизайн интерфейса.

Используем:

CarFront icon;
номера;
VIN;
пробег;
фотографии;
service history;
vehicle cards;
технические данные.

Не использовать:

карбоновые текстуры;
racing stripes;
speedometer UI;
огромные изображения автомобилей;
металлические градиенты.
37. Responsive

Desktop first.

Основной пользователь GarageOS — сотрудник СТО за компьютером.

Но:

Tablet

обязательно.

Mobile

обязательно для:

механика;
владельца;
администратора.

На mobile sidebar превращается в:

bottom navigation

или drawer.

38. Mobile navigation

Например:

Home
Orders
Calendar
Clients
More

У каждого:

icon + label

39. Общий visual hierarchy

Каждый экран должен иметь:

PAGE TITLE
↓
CONTEXT / FILTERS
↓
PRIMARY ACTION
↓
KPI / CONTENT
↓
TABLE / CHART

Не делать интерфейс визуально плоским.

40. Что категорически запрещено

AI-разработчик НЕ должен:

❌ использовать Bootstrap

❌ использовать Material UI

❌ использовать разношерстные icon libraries

❌ использовать emoji вместо иконок

❌ делать light theme основной

❌ использовать градиенты без причины

❌ делать glassmorphism

❌ делать giant rounded cards

❌ использовать огромные тени

❌ использовать случайные цвета

❌ создавать custom charts вместо Bklit

❌ менять стиль между страницами

❌ делать каждый экран отдельным design system

❌ использовать стандартные browser inputs/selects без стилизации

41. Главная формула GarageOS
DARK
+
DENSE
+
CLEAN
+
DATA
+
AUTOMOTIVE
+
SHADCN
+
BKLIT
+
LUCIDE

И самое важное:

GarageOS должен выглядеть как software для управления серьёзным бизнесом, а не как CRM-шаблон для СТО.

42. Финальный prompt для AI-разработчика

Вот это я бы отдельным файлом DESIGN_SYSTEM.md положил в корень проекта:

# GarageOS — Design System

## Product

GarageOS is a modern B2B SaaS operating system for automotive service businesses.

The UI must feel like a premium, modern software product.

Visual references:
- OpenPanel
- Linear
- modern shadcn/ui products
- modern automotive technology

Do NOT copy any product literally.
Use them only as visual references.

---

## Theme

Dark mode is the default and primary theme.

Primary background:
#090A0C

Surface:
#101216

Secondary surface:
#15181D

Elevated surface:
#191C22

Border:
#242830

Subtle border:
#1B1E24

Primary text:
#F4F5F7

Secondary text:
#A1A6B0

Muted text:
#6F7580

Primary accent:
#B8FF3D

Success:
#72E06A

Warning:
#F5C451

Error:
#F06464

Info:
#62A8FF

Use accent colors sparingly.

---

## UI Foundation

Use:

- React
- Tailwind CSS
- shadcn/ui
- Lucide icons
- Bklit UI for charts and data visualization

Bklit is installed through the shadcn registry using the @bklit namespace.

Never replace Bklit charts with custom SVG charts unless explicitly required.

---

## Typography

Primary font:
Inter

Fallback:
system-ui

Page title:
24–28px / 600

Section title:
16–18px / 600

Body:
14px

Small:
12px

KPI:
28–32px / 600

Avoid oversized typography inside the application.

---

## Icons

Use Lucide icons consistently.

Do not use:
- emoji
- Font Awesome
- random icon libraries
- custom unrelated SVG icons

All sidebar navigation items must have icons.

Use 16–18px icon size for navigation and standard controls.

Suggested icons:

Dashboard:
LayoutDashboard

Appointments:
CalendarDays

Work Orders:
ClipboardList

Customers:
Users

Vehicles:
CarFront

Services:
Wrench

Inventory:
Package

Employees:
UsersRound

Analytics:
ChartNoAxesCombined

Reports:
FileChartColumn

Settings:
Settings

Search:
Search

Notifications:
Bell

---

## Sidebar

Desktop width:
240px

Collapsed width:
64px

Fixed full-height sidebar.

Use compact navigation inspired by modern analytics SaaS products.

Sidebar sections:

WORKSPACE
- Dashboard
- Appointments
- Work Orders
- Customers
- Vehicles
- Services
- Inventory

MANAGEMENT
- Employees
- Reports
- Analytics

SYSTEM
- Settings

Every item must have a Lucide icon.

Active state:
- subtle accent background
- accent text
- no bright neon blocks

Hover:
- subtle surface change

---

## Organization Switcher

Top of sidebar must contain organization switcher.

Example:

Garage Almaty
Administrator

Support multiple organizations in the UI architecture.

---

## User Profile

Bottom of sidebar:

Avatar
Name
Role

Click opens:
- Profile
- Account
- Notifications
- Theme
- Logout

---

## Topbar

Compact.

Height:
56–64px

Include:
- page context
- search
- notifications
- user menu

Global search shortcut:
Cmd/Ctrl + K

---

## Buttons

Default radius:
8px

Primary:
- accent background
- dark text

Secondary:
- dark surface
- border

Ghost:
- transparent

Buttons must remain compact.

Do not use giant pill buttons.

---

## Cards

Default:

background:
#101216

border:
1px solid #242830

radius:
10px

Avoid heavy shadows.

Use borders and surface hierarchy instead of excessive shadows.

---

## Inputs

Height:
40px

Background:
#101216

Border:
#242830

Radius:
8px

Focus:
accent border

Placeholder:
#6F7580

---

## Tables

Tables should be dense and professional.

Row height:
48–56px

Use subtle borders.

Use hover states.

Use badges for statuses.

---

## Status Badges

Use pills only for statuses/tags.

Examples:

Completed
In progress
Waiting
Cancelled

Do not use pill styling for every UI element.

---

## Charts

All analytics charts must use Bklit UI.

Preferred components:

Revenue:
Area Chart

Orders:
Bar Chart

Trends:
Line Chart

Occupancy:
Gauge

Service distribution:
Ring Chart

Workshop load:
Heatmap

Customer conversion:
Funnel

Workflow:
Sankey

Charts must use the GarageOS color system.

Avoid rainbow charts.

---

## Layout

Desktop:

Sidebar:
240px

Main content:
remaining width

Page padding:
24px

Large screens:
32px

Maximum content width:
1600px

---

## Border Radius

Buttons:
8px

Inputs:
8px

Cards:
10px

Modals:
12px

Badges:
999px

Avoid excessive rounded UI.

---

## Shadows

Use shadows minimally.

Prefer:
- borders
- surface hierarchy
- spacing

Avoid:
- huge shadows
- glow
- neon effects
- glassmorphism

---

## Animation

Animations should be subtle.

Duration:
150–200ms

Use:
- opacity
- background
- border
- small transforms

Avoid:
- bounce
- excessive spring animations
- large page transitions

---

## Empty States

Use:

Icon
Title
Short description
Primary action

Example:

No vehicles yet

Add your first vehicle to start tracking service history.

[ Add vehicle ]

---

## Loading

Use dark skeleton loaders.

Use Bklit loading patterns for charts where appropriate.

---

## Responsive

Desktop-first.

Tablet supported.

Mobile supported for mechanics, administrators and owners.

On mobile:
sidebar becomes drawer or bottom navigation.

Mobile bottom navigation:

Home
Orders
Calendar
Clients
More

Each item must have an icon.

---

## Automotive Design Language

Automotive identity should come from content and data.

Use:
- vehicles
- VIN
- mileage
- service history
- parts
- diagnostics
- work orders

Do NOT use:
- racing UI
- carbon textures
- speedometers
- excessive car imagery
- metallic gradients
- cyberpunk neon

GarageOS is software, not a racing dashboard.

---

## Strict Rules

DO NOT:

- use Bootstrap
- use Material UI
- use random icon libraries
- use emojis as UI icons
- make light mode the default
- use excessive gradients
- use glassmorphism
- use huge rounded cards
- use heavy shadows
- use random colors
- create custom charts instead of Bklit
- change visual language between pages
- create independent design systems per page

Every page must look like part of the same GarageOS product.

---

## Design Goal

GarageOS should feel:

Premium
Fast
Technical
Professional
Dense
Minimal
Data-driven
Automotive

The final impression should be:

"This is a serious operating system for a real automotive business."