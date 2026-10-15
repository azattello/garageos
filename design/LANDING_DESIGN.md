# GarageOS Landing Design

## 1. Visual Theme & Atmosphere

**Направление:** industrial operations editorial. Сайт ощущается как цифровой рабочий стол автосервиса: точная типографика, разметка, визуальные статусы и настоящий GarageOS в главной роли.

**Принцип:** реальный рабочий экран важнее декоративных мокапов. Hero показывает live read-only demo внутри собственной browser frame; ниже текст поясняет реальные workflow. Нет стоковой фотографии, fake CRM screenshot, стеклянных карточек или «AI-облаков».

**Ритм:** большой спокойный hero, широкий продуктовый preview, затем чередование плотных светлых и тёмных full-width секций. Контент преимущественно unframed; рамка разрешена для интерактивного browser showcase.

**Motion:** L2, но без scroll-jacking и параллакса. Однократное появление title/browser, короткий stagger небольших элементов и Motion-переход выбранного read-only screen. Chart animation остаётся Bklit. Непрерывные фоновые анимации отсутствуют.

## 2. Color Palette & Roles

Публичный слой использует локальные tokens и не переопределяет глобальную тему tenant-приложения:

```css
.landing-shell {
  --landing-ink: #0b0d0a;
  --landing-surface: #141713;
  --landing-surface-raised: #1d211b;
  --landing-paper: #f3f5ef;
  --landing-paper-muted: #e5e9df;
  --landing-text: #f4f6ef;
  --landing-text-dark: #161a14;
  --landing-muted: #a1a89b;
  --landing-muted-dark: #5d6658;
  --landing-lime: #b8ff3d;
  --landing-blue: #62a8ff;
  --landing-amber: #f5c451;
  --landing-red: #e76f51;
  --landing-border: rgba(226, 233, 216, 0.18);
  --landing-border-dark: rgba(22, 26, 20, 0.16);
  --landing-lime-rgb: 184 255 61;
  --landing-blue-rgb: 98 168 255;
}
```

Lime — брендовый CTA и active state; blue — links/information; amber — attention/maintenance; red — destructive/blocked examples only. Светлые product editorial sections дают контраст тёмному real UI, сохраняя GarageOS lime. Цвет не является единственным носителем статуса.

## 3. Typography Rules

- Display/headings: локально установленный `Geist Variable`, плотный grotesk без внешнего font request.
- UI body: существующий `Inter` из локального `@fontsource-variable/inter`, совпадает с GarageOS application.
- Русский набор остаётся читаемым: body 16–18 px, line-height 1.55–1.7; `letter-spacing: 0`.
- H1: 56–72 px desktop, 38–44 px mobile; названия H2 30–40 px desktop, 25–30 px mobile. Размеры фиксируются по breakpoint, не через `vw`.
- Числа dashboard/цены используют tabular figures; helper captions не опускаются ниже 12 px.

## 4. Component Stylings

**Public nav:** компактная wordmark + ссылки «Возможности», «Тарифы», «FAQ», «Войти»; один заметный signup CTA. На mobile ссылки переходят в простое disclosure меню, touch targets не меньше 44 px.

**Buttons:** primary lime fill/dark text; secondary outlined; tertiary text link с underline/focus. Любой hover имеет keyboard focus-visible и disabled state.

**Browser frame:** тонкий chrome bar с тремя нейтральными window controls, коротким адресом `GarageOS.local/demo`, responsive aspect ratio. Внутри iframe используется действующая `/demo` страница/preview route; запрет действий задаётся на самой публичной demo поверхности, а не подделкой картинки.

**Product tabs:** `Обзор`, `Клиенты`, `Автомобили`, `Записи`, `Заказ-наряды`, `Аналитика`. Segmented navigation переключает preview-композицию/URL, не открывая production tenant mutations.

**Feature rows:** асимметричный editorial Bento, где один showcase занимает больше места, остальные меняют пропорции и содержат реальные области GarageOS (клиент, автомобиль, расписание, заказ, аналитика). Не делать одинаковую маркетинговую сетку из десяти cards.

**Pricing:** ровно два плана из `PlanCatalog`; цена, лимит и features не дублируются в константах. Статус/возможности доступны текстом. CTA ведёт в существующий `/register`.

**FAQ/contact/legal:** компактные раскрывающиеся вопросы, ссылки и читаемые prose columns. Контакты поддерживают только реальные настроенные support fields; отсутствующие поля не фальсифицируются.

## 5. Layout Principles

- Public marketing content ограничен шириной 1280 px с боковыми gutters 24–64 px.
- Header общий для public pages; footer содержит реальные login/register/legal links.
- Hero text не помещается внутрь карточки. На desktop headline располагается над широким product preview; на mobile compact copy сохраняет ширину текста и first-screen CTA.
- Первый экран показывает название GarageOS, category/value proposition, основной CTA и заметную часть реального продукта. После hero в viewport виден заголовок следующей секции.
- Sections — горизонтальные full-width bands; внутренние layouts имеют собственный max-width. Не вкладывать card в card.
- Tabs, кнопки, метрики, элементы окна и preview имеют стабильные размеры; загрузка/смена вкладки не сдвигает layout.

## 6. Depth & Elevation

- В публичной части основное разделение достигается фоном, крупной типографикой и тонкими borders.
- Browser showcase может иметь одну естественную широкую shadow; обычные sections и feature rows не плавают над страницей.
- Radius 4–8 px; browser frame до 10 px. Не добавлять glassmorphism, крупный backdrop blur и градиентные орбы.
- Background texture допустим только как малоконтрастная CSS grid/technical line pattern; она не должна конкурировать с реальным preview.

## 7. Animation & Interaction

- При первом появлении: hero title fade/translate-up, затем browser frame, затем один короткий stagger badges/counters.
- Product tabs обновляют выбранный preview через `motion/react` `AnimatePresence`/layout-safe opacity+translate; animation ~180–280 ms, без route reload где возможно.
- Nav uses обычные Inertia links. Internal CTA не заменяет login/register flow.
- FAQ использует native `<details>` или доступный disclosure.
- Scroll reveal выполняется один раз через `whileInView`/IntersectionObserver; не менять scroll position, не pin-ить длинные блоки.
- `@media (prefers-reduced-motion: reduce)` выключает reveal/stagger/transition duration и оставляет прямое изменение active tab.
- Iframe demo read-only: формы и destructive действия отсутствуют; публичная showcase route не выдаёт tenant credentials.

## 8. Do's and Don'ts

### Do

1. Реиспользовать `BrandLogo`, AppLayout visual language, реальные `PlanCatalog` и Bklit charts.
2. Показывать настоящий read-only preview на seeded или явно fictional demo data.
3. Описывать только существующие clients/cars/appointments/orders/analytics/subscriptions flows.
4. Сохранять auth, registration, trial middleware и Super Admin separation.
5. Использовать реальный runtime support contact только если он настроен.
6. Вести все ссылки login/register/demo через текущие routes.
7. Проверять отсутствие переполнения текста, особенно длинных русских слов, email и цен.
8. Предпочитать CSS transforms/opacity и локальные assets/fonts.

### Don't

1. Не менять API, models, тарифы или feature gates для рекламного copy.
2. Не передвигать root tenant dashboard из auth/trial semantics и сохранять route name `dashboard`.
3. Не показывать реальные tenant data, production contacts, customer PII или доступные формы изменения данных в публичном preview.
4. Не отображать fake MRR/churn/customers/testimonials/partner logos.
5. Не дублировать цены/feature flags вне controller props.
6. Не подменять настоящий product UI набором декоративных dashboard cards.
7. Не добавлять remote JS/CSS/fonts, новый framework или другую chart library.
8. Не использовать autoplay видео, scroll-jacking, heavy 3D или custom cursor.
9. Не создавать новую маркетинговую тему поверх Super Admin/tenant UI.

## 9. Responsive Behavior

- Breakpoints: mobile ≤639 px, tablet 640–1023 px, desktop ≥1024 px, wide ≥1440 px.
- Product window: desktop full browser composition; mobile сохраняет ширину product preview с безопасным horizontal inner scroll/scale container, без горизонтального overflow всей страницы.
- Mobile tabs — горизонтально прокручиваемый segmented row с видимым active state и минимум 44 px высотой.
- Marketing sections переходят в одну колонку; порядок content/media сохраняет смысл; pricing сравнивается вертикально.
- Hero copy и CTA переносятся по строкам. В viewport 320 px не должно быть горизонтального overflow.
- Touch targets ≥44 px; reduced-motion и keyboard focus одинаково доступны на всех breakpoints.