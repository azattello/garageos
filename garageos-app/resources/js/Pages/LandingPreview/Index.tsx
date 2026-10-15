import { Head, Link } from '@inertiajs/react';
import { ArrowDown, ArrowRight, ArrowUpRight, Banknote, CalendarDays, CarFront, Check, ChevronLeft, ChevronRight, ClipboardList, Menu, Package, Search, Users, Wrench, X } from 'lucide-react';
import { useState } from 'react';
import '../../../css/landing-preview.css';

type Module = 'overview' | 'orders' | 'clients' | 'appointments' | 'inventory' | 'finance';

const navigation: { id: Module; label: string; icon: typeof ClipboardList }[] = [
    { id: 'overview', label: 'Обзор', icon: Banknote },
    { id: 'orders', label: 'Заказы', icon: ClipboardList },
    { id: 'clients', label: 'Клиенты', icon: Users },
    { id: 'appointments', label: 'Записи', icon: CalendarDays },
    { id: 'inventory', label: 'Склад', icon: Package },
    { id: 'finance', label: 'Финансы', icon: Banknote },
];

const money = (value: number) => `${new Intl.NumberFormat('ru-RU').format(value)} ₸`;

function AppFrame({ active, title, children }: { active: Module; title: string; children: React.ReactNode }) {
    return (
        <div className="lp-app-frame">
            <div className="lp-app-topbar">
                <div className="lp-app-brand"><span>G</span><b>GarageOS</b><small>AutoMaster · Алматы</small></div>
                <div className="lp-app-top-actions"><span className="lp-live-dot" />Рабочее пространство <span className="lp-avatar">АН</span></div>
            </div>
            <div className="lp-app-layout">
                <nav className="lp-app-sidebar" aria-label="Разделы демонстрации">
                    <p>РАБОЧЕЕ ПРОСТРАНСТВО</p>
                    {navigation.map(({ id, label, icon: Icon }) => <a className={active === id ? 'is-active' : ''} href={`#${id}`} key={id}><Icon size={15} />{label}</a>)}
                    <div className="lp-sidebar-workshop"><span>СЕГОДНЯ В СЕРВИСЕ</span><b>8 автомобилей</b><small>3 мастера на смене</small></div>
                </nav>
                <section className="lp-app-content">
                    <div className="lp-app-heading"><div><span>GarageOS / {title.toUpperCase()}</span><h3>{title}</h3></div><span className="lp-demo-pill"><i /> Демо-данные</span></div>
                    {children}
                </section>
            </div>
        </div>
    );
}

function Metric({ label, value, change }: { label: string; value: string; change?: string }) {
    return <div className="lp-metric"><span>{label}</span><b>{value}</b>{change && <small>{change}</small>}</div>;
}

function OverviewScreen() {
    const [period, setPeriod] = useState<'Сегодня' | '7 дней' | 'Месяц'>('Сегодня');
    const [selectedOrder, setSelectedOrder] = useState('ЗН-1042');
    const orders = [
        { number: 'ЗН-1042', car: 'Toyota Camry 70', client: 'Азамат Нурланов', task: 'Замена масла', status: 'В работе', tone: 'amber' },
        { number: 'ЗН-1041', car: 'Hyundai Tucson', client: 'Ерлан Садыков', task: 'Диагностика подвески', status: 'Ожидает', tone: 'blue' },
        { number: 'ЗН-1040', car: 'Kia Sportage', client: 'Даурен Алиев', task: 'Тормозные колодки', status: 'Готов', tone: 'green' },
    ];
    return <AppFrame active="overview" title="Обзор">
        <div className="lp-demo-toolbar"><span>Понедельник, 12 октября</span><div className="lp-segment">{(['Сегодня', '7 дней', 'Месяц'] as const).map((item) => <button className={period === item ? 'is-selected' : ''} key={item} onClick={() => setPeriod(item)}>{item}</button>)}</div></div>
        <div className="lp-metrics"><Metric label="Заказ-наряды" value={period === 'Сегодня' ? '12' : period === '7 дней' ? '68' : '247'} change="↑ 12% к прошлому периоду" /><Metric label="Выручка" value={period === 'Сегодня' ? '284 000 ₸' : period === '7 дней' ? '1,8 млн ₸' : '6,4 млн ₸'} change="↑ 8% к прошлому периоду" /><Metric label="В работе" value="8" /><Metric label="Средний чек" value="26 400 ₸" /></div>
        <div className="lp-panel lp-order-panel"><div className="lp-panel-heading"><div><b>Сейчас в работе</b><span>Смена · 3 мастера</span></div><a href="#orders">Все заказы <ArrowUpRight size={13} /></a></div>
            <div className="lp-order-list">{orders.map((order) => <button className={`lp-order-row ${selectedOrder === order.number ? 'is-picked' : ''}`} key={order.number} onClick={() => setSelectedOrder(order.number)}><span className="lp-order-number">{order.number}</span><span className="lp-order-copy"><b>{order.car}</b><small>{order.client} · {order.task}</small></span><span className={`lp-status ${order.tone}`}>{order.status}</span><ChevronRight size={15} /></button>)}</div>
            <div className="lp-selection-note"><Check size={13} /> Выбран {selectedOrder}. История и детали заказа доступны в один клик.</div>
        </div>
    </AppFrame>;
}

function OrdersScreen() {
    const [filter, setFilter] = useState('Все');
    const [selected, setSelected] = useState('ЗН-1042');
    const [status, setStatus] = useState('В работе');
    const rows = [
        { number: 'ЗН-1042', car: 'Toyota Camry 70', client: 'Азамат Нурланов', task: 'Масло и фильтры', sum: 35000, state: status, tone: 'amber' },
        { number: 'ЗН-1041', car: 'Hyundai Tucson', client: 'Ерлан Садыков', task: 'Диагностика подвески', sum: 18000, state: 'Ожидает', tone: 'blue' },
        { number: 'ЗН-1040', car: 'Kia Sportage', client: 'Даурен Алиев', task: 'Тормозные колодки', sum: 42000, state: 'Готов', tone: 'green' },
    ];
    const visibleRows = rows.filter((row) => filter === 'Все' || row.state === filter);
    return <AppFrame active="orders" title="Заказ-наряды">
        <div className="lp-demo-toolbar"><div className="lp-segment">{['Все', 'В работе', 'Ожидает', 'Готов'].map((item) => <button className={filter === item ? 'is-selected' : ''} key={item} onClick={() => setFilter(item)}>{item}</button>)}</div><button className="lp-mini-primary" onClick={() => { setFilter('Все'); setSelected('ЗН-1042'); }}>+ Новый заказ</button></div>
        <div className="lp-panel lp-table-panel"><div className="lp-table-heading"><span>Номер / автомобиль</span><span>Клиент и работы</span><span>Статус</span><span>Сумма</span></div>
            {visibleRows.map((row) => <button className={`lp-table-row ${selected === row.number ? 'is-picked' : ''}`} key={row.number} onClick={() => { setSelected(row.number); setStatus(row.number === 'ЗН-1042' ? (status === 'В работе' ? 'Готов' : 'В работе') : row.state); }}><span><b>{row.number}</b><small>{row.car}</small></span><span><b>{row.client}</b><small>{row.task}</small></span><span className={`lp-status ${row.tone}`}>{row.number === 'ЗН-1042' ? status : row.state}</span><b>{money(row.sum)}</b></button>)}
            {visibleRows.length === 0 && <p className="lp-empty">В этом статусе заказов пока нет.</p>}
        </div><p className="lp-screen-hint">Нажмите на заказ: выбранный заказ-наряд можно перевести между статусами.</p>
    </AppFrame>;
}

function ClientsScreen() {
    const [query, setQuery] = useState('');
    const [selected, setSelected] = useState('Азамат Нурланов');
    const clients = [
        { name: 'Азамат Нурланов', phone: '+7 701 234 56 78', car: 'Toyota Camry 70 · 2021', plate: '777 ABC 02', orders: 12, spent: 486000 },
        { name: 'Ерлан Садыков', phone: '+7 707 555 12 34', car: 'Hyundai Tucson · 2020', plate: '042 KAZ 02', orders: 7, spent: 231000 },
        { name: 'Даурен Алиев', phone: '+7 702 444 88 11', car: 'Kia Sportage · 2022', plate: '808 DAA 02', orders: 4, spent: 174000 },
    ];
    const found = clients.filter((client) => `${client.name} ${client.phone} ${client.plate}`.toLowerCase().includes(query.toLowerCase()));
    const client = clients.find((item) => item.name === selected) ?? found[0];
    return <AppFrame active="clients" title="Клиенты и автомобили">
        <label className="lp-search"><Search size={15} /><input aria-label="Поиск клиента" onChange={(event) => setQuery(event.target.value)} placeholder="Имя, телефон или госномер" value={query} /><kbd>⌘ K</kbd></label>
        <div className="lp-client-layout"><div className="lp-panel lp-client-list"><div className="lp-panel-heading"><div><b>Клиентская база</b><span>{found.length} клиента</span></div></div>{found.map((item) => <button className={`lp-client-row ${selected === item.name ? 'is-picked' : ''}`} key={item.name} onClick={() => setSelected(item.name)}><span className="lp-client-initials">{item.name.split(' ').map((part) => part[0]).join('')}</span><span><b>{item.name}</b><small>{item.phone}</small></span></button>)}{found.length === 0 && <p className="lp-empty">Совпадений нет.</p>}</div>
            {client ? <div className="lp-panel lp-client-detail"><div className="lp-client-profile"><span className="lp-client-initials large">{client.name.split(' ').map((part) => part[0]).join('')}</span><div><b>{client.name}</b><small>{client.phone} · клиент с марта 2024</small></div><button aria-label="Открыть клиента" className="lp-icon-button"><ArrowUpRight size={16} /></button></div><div className="lp-client-stats"><span><small>Заказов</small><b>{client.orders}</b></span><span><small>В сервисе</small><b>{money(client.spent)}</b></span></div><div className="lp-car-card"><CarFront size={19} /><span><b>{client.car}</b><small>{client.plate}</small></span><ArrowUpRight size={14} /></div><div className="lp-history"><b>Последняя работа</b><span>Замена масла и фильтров <small>12 октября · 35 000 ₸</small></span></div></div> : <div className="lp-panel lp-client-detail lp-empty">Выберите клиента</div>}
        </div>
    </AppFrame>;
}

function AppointmentsScreen() {
    const [week, setWeek] = useState(0);
    const [slot, setSlot] = useState<string | null>(null);
    const days = ['Пн 12', 'Вт 13', 'Ср 14', 'Чт 15', 'Пт 16'];
    return <AppFrame active="appointments" title="Записи и расписание">
        <div className="lp-demo-toolbar"><div><b className="lp-week-title">{week === 0 ? '12–16 октября 2026' : week < 0 ? '5–9 октября 2026' : '19–23 октября 2026'}</b><span className="lp-toolbar-subtitle">Загрузка команды на неделю</span></div><div className="lp-week-controls"><button aria-label="Предыдущая неделя" onClick={() => { setWeek(week - 1); setSlot(null); }}><ChevronLeft size={16} /></button><button onClick={() => { setWeek(0); setSlot(null); }}>Сегодня</button><button aria-label="Следующая неделя" onClick={() => { setWeek(week + 1); setSlot(null); }}><ChevronRight size={16} /></button></div></div>
        <div className="lp-panel lp-calendar"><div className="lp-calendar-grid"><span className="lp-calendar-label">МАСТЕР</span>{days.map((day) => <span className="lp-calendar-day" key={day}>{day}</span>)}
                {['Арман', 'Руслан', 'Тимур'].map((mechanic, index) => <div className="lp-calendar-row" key={mechanic}><b>{mechanic}<small>{index === 0 ? 'Диагностика' : index === 1 ? 'Механик' : 'Электрик'}</small></b>{days.map((day, dayIndex) => { const key = `${mechanic}-${dayIndex}`; const booked = (dayIndex + index) % 3 !== 1; return <button className={`${booked ? 'is-booked' : 'is-open'} ${slot === key ? 'is-selected' : ''}`} key={day} onClick={() => setSlot(key)}>{booked ? <><b>{dayIndex % 2 ? 'Camry · ТО' : 'Tucson · ремонт'}</b><small>{dayIndex % 2 ? '10:00–12:00' : '09:00–11:00'}</small></> : <><span>+</span><small>Свободно</small></>}</button>; })}</div>)}
            </div><div className="lp-calendar-footer"><span><i className="booked-dot" /> Занято <i className="open-dot" /> Свободное окно</span><b>{slot ? `Выбрано: ${days[Number(slot.split('-')[1])]}` : 'Нажмите на ячейку, чтобы посмотреть время'}</b></div>
        </div>
    </AppFrame>;
}

function InventoryScreen() {
    const [lowOnly, setLowOnly] = useState(false);
    const [parts, setParts] = useState([{ name: 'Масло Toyota 5W-30', sku: 'OIL-530', stock: 14, min: 5, unit: 'л', price: 4200 }, { name: 'Фильтр масляный', sku: 'FLT-208', stock: 3, min: 6, unit: 'шт', price: 2800 }, { name: 'Колодки передние Camry', sku: 'BRK-071', stock: 8, min: 4, unit: 'компл.', price: 18500 }, { name: 'Антифриз G12+', sku: 'CLT-120', stock: 2, min: 5, unit: 'л', price: 3100 }]);
    const shown = parts.filter((part) => !lowOnly || part.stock <= part.min);
    return <AppFrame active="inventory" title="Склад и запчасти">
        <div className="lp-demo-toolbar"><span>Остатки и наличие запчастей</span><label className="lp-toggle"><input checked={lowOnly} onChange={(event) => setLowOnly(event.target.checked)} type="checkbox" /><span />Только заканчиваются</label></div>
        <div className="lp-stock-summary"><Metric label="Позиций на складе" value="128" /><Metric label="Заканчиваются" value={String(parts.filter((part) => part.stock <= part.min).length)} /><Metric label="Резерв в заказах" value="14 позиций" /></div>
        <div className="lp-panel lp-stock-table"><div className="lp-stock-heading"><span>Наименование</span><span>Артикул</span><span>Остаток</span><span>Цена</span><span /></div>{shown.map((part) => <div className="lp-stock-row" key={part.sku}><span><b>{part.name}</b><small>Ед. изм.: {part.unit}</small></span><span>{part.sku}</span><span className={part.stock <= part.min ? 'lp-stock-low' : ''}><b>{part.stock} {part.unit}</b><small>минимум {part.min}</small></span><span>{money(part.price)}</span><button aria-label={`Поступление: ${part.name}`} className="lp-icon-button" onClick={() => setParts(parts.map((item) => item.sku === part.sku ? { ...item, stock: item.stock + 5 } : item))} title="Добавить 5 единиц"><Package size={15} /></button></div>)}{shown.length === 0 && <p className="lp-empty">Все позиции в наличии.</p>}</div>
        <p className="lp-screen-hint">Нажмите на значок коробки, чтобы смоделировать поступление 5 единиц на склад.</p>
    </AppFrame>;
}

function FinanceScreen() {
    const [period, setPeriod] = useState('30 дней');
    const [chart, setChart] = useState<'Выручка' | 'Расходы'>('Выручка');
    const multiplier = period === '7 дней' ? 0.28 : period === '90 дней' ? 2.7 : 1;
    const bars = chart === 'Выручка' ? [34, 52, 44, 68, 58, 82, 95] : [22, 34, 28, 47, 40, 56, 62];
    return <AppFrame active="finance" title="Финансы">
        <div className="lp-demo-toolbar"><span>Движение денег по сервису</span><div className="lp-segment">{['7 дней', '30 дней', '90 дней'].map((item) => <button className={period === item ? 'is-selected' : ''} key={item} onClick={() => setPeriod(item)}>{item}</button>)}</div></div>
        <div className="lp-metrics lp-finance-metrics"><Metric label="Выручка" value={money(Math.round(1240000 * multiplier))} change="↑ 12% к периоду" /><Metric label="Расходы" value={money(Math.round(480000 * multiplier))} /><Metric label="Прибыль" value={money(Math.round(760000 * multiplier))} /><Metric label="Средний чек" value="26 400 ₸" /></div>
        <div className="lp-panel lp-chart-panel"><div className="lp-panel-heading"><div><b>Доходы и расходы</b><span>Суммы по дням, ₸</span></div><div className="lp-segment">{(['Выручка', 'Расходы'] as const).map((item) => <button className={chart === item ? 'is-selected' : ''} key={item} onClick={() => setChart(item)}>{item}</button>)}</div></div><div className="lp-chart">{bars.map((height, index) => <div className="lp-chart-column" key={index}><span style={{ height: `${height}%` }} /><small>{['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'][index]}</small></div>)}</div><div className="lp-chart-note"><span><i />{chart} · {period}</span><b>{chart === 'Выручка' ? '1 240 000 ₸' : '480 000 ₸'}</b></div></div>
    </AppFrame>;
}

const painPoints = [
    { title: '«Что делали в прошлый раз?»', text: 'История клиента, его машин и заказов открывается за секунды.', icon: Users },
    { title: 'Машина ждёт, мастер не знает', text: 'Записи, ответственные и статусы собраны в одном расписании.', icon: CalendarDays },
    { title: 'Запчасти закончились в работе', text: 'Остатки и резерв видны до того, как автомобиль встанет.', icon: Package },
    { title: 'Выручка есть, прибыли не видно', text: 'Платежи и расходы складываются в понятную картину.', icon: Banknote },
];

const features: { id: Exclude<Module, 'overview'>; number: string; eyebrow: string; title: string; text: string; points: string[]; Screen: () => React.JSX.Element }[] = [
    { id: 'orders', number: '02', eyebrow: 'ЗАКАЗ-НАРЯДЫ', title: 'Каждый автомобиль проходит понятный путь', text: 'От приёмки до выдачи: состав работ, ответственный мастер, сумма и текущий статус не теряются в переписках.', points: ['Очередь заказов по статусам', 'Работы и запчасти в заказ-наряде', 'Оплаты и остаток по каждому заказу'], Screen: OrdersScreen },
    { id: 'clients', number: '03', eyebrow: 'КЛИЕНТЫ И АВТОМОБИЛИ', title: 'Узнавайте историю раньше, чем клиент успеет напомнить', text: 'Связанные карточки клиентов и машин сохраняют предыдущие работы, контакты и сумму обслуживания.', points: ['Поиск по имени, телефону и госномеру', 'Все автомобили в карточке клиента', 'История обслуживания и заказов'], Screen: ClientsScreen },
    { id: 'appointments', number: '04', eyebrow: 'ЗАПИСИ И РАСПИСАНИЕ', title: 'Заранее видно, кто и когда занят', text: 'Планируйте день по мастерам и свободным окнам. Новая запись связывает клиента, автомобиль и нужную услугу.', points: ['Загрузка команды на одной сетке', 'Свободные интервалы без двойной записи', 'Запись становится основой заказ-наряда'], Screen: AppointmentsScreen },
    { id: 'inventory', number: '05', eyebrow: 'СКЛАД И ЗАПЧАСТИ', title: 'Нужная деталь находится до начала ремонта', text: 'Остатки, минимальный запас и запчасти, зарезервированные под заказ, видны в рабочем потоке.', points: ['Быстрый фильтр заканчивающихся позиций', 'Поступления и движения по складу', 'Резерв деталей для заказов'], Screen: InventoryScreen },
    { id: 'finance', number: '06', eyebrow: 'ФИНАНСЫ', title: 'Понимайте, что осталось после расходов', text: 'Сводка по платежам и расходам показывает выручку, прибыль, средний чек и динамику за период.', points: ['Выручка и расходы по периодам', 'Платежи и задолженность клиентов', 'Расходы по категориям'], Screen: FinanceScreen },
];

export default function LandingPreview() {
    const [menuOpen, setMenuOpen] = useState(false);
    return <div className="preview-landing" id="top">
        <Head title="GarageOS — порядок в работе автосервиса"><meta name="description" content="Интерактивный обзор GarageOS: заказ-наряды, клиенты, расписание, склад и финансы автосервиса." /></Head>
        <header className="lp-header"><nav className="lp-nav"><a className="lp-brand" href="#top"><img src="/logo.jpg" alt="" /><span>GarageOS</span></a><div className={`lp-nav-links ${menuOpen ? 'is-open' : ''}`}><a href="#features" onClick={() => setMenuOpen(false)}>Возможности</a><a href="#pricing" onClick={() => setMenuOpen(false)}>Тарифы</a><a href="/demo">Демо</a><Link href="/login">Войти</Link><Link className="lp-nav-cta" href="/register">Начать работу <ArrowUpRight size={15} /></Link></div><button aria-label={menuOpen ? 'Закрыть меню' : 'Открыть меню'} className="lp-menu" onClick={() => setMenuOpen(!menuOpen)} type="button">{menuOpen ? <X size={19} /> : <Menu size={19} />}</button></nav></header>
        <main>
            <section className="lp-hero"><div className="lp-container"><div className="lp-hero-copy"><p className="lp-eyebrow"><i /> ОПЕРАЦИОННАЯ СИСТЕМА АВТОСЕРВИСА</p><h1>Сервис работает.<br /><span>Вы видите как.</span></h1><div className="lp-hero-bottom"><p>Клиенты, записи, заказы, запчасти и деньги в одном рабочем пространстве. Без потерь между звонком и выдачей автомобиля.</p><div className="lp-hero-actions"><Link className="lp-button lp-button-primary" href="/register">Начать работу <ArrowRight size={16} /></Link><a className="lp-button lp-button-secondary" href="#orders">Смотреть продукт <ArrowDown size={15} /></a></div></div></div><div className="lp-product-label"><span>01 / ОБЗОР СЕРВИСА</span><span>ИНТЕРАКТИВНОЕ ПРЕВЬЮ · ДАННЫЕ ДЕМО</span></div><OverviewScreen /><div className="lp-hero-caption"><span>Рабочий интерфейс собран из компонентов GarageOS</span><span>Нажимайте на периоды и заказ-наряды</span></div></div></section>

            <section className="lp-pains"><div className="lp-container"><div className="lp-pain-heading"><p className="lp-eyebrow">КОГДА ВСЁ ДЕРЖИТСЯ НА ПАМЯТИ</p><h2>Узнаёте свой<br />рабочий день?</h2></div><div className="lp-pain-grid">{painPoints.map(({ title, text, icon: Icon }, index) => <article className="lp-pain-item" key={title}><span className="lp-pain-icon"><Icon size={19} /></span><small>0{index + 1}</small><h3>{title}</h3><p>{text}</p></article>)}</div></div></section>

            <section className="lp-features" id="features"><div className="lp-container"><div className="lp-section-intro"><p className="lp-eyebrow">02 / РАБОЧИЕ ИНСТРУМЕНТЫ</p><h2>Не обещания.<br /><span>Показываем процессы.</span></h2><p>Пять ключевых частей сервиса связаны друг с другом. Посмотрите, как каждая помогает в ежедневной работе.</p></div>{features.map(({ id, number, eyebrow, title, text, points, Screen }, index) => <article className={`lp-feature ${index % 2 ? 'is-reversed' : ''}`} id={id} key={id}><div className="lp-feature-copy"><span className="lp-feature-index">{number} / {eyebrow}</span><h3>{title}</h3><p>{text}</p><ul>{points.map((point) => <li key={point}><Check size={15} />{point}</li>)}</ul><a href="#pricing" className="lp-text-link">Посмотреть тарифы <ArrowRight size={14} /></a></div><div className="lp-feature-screen"><Screen /></div></article>)}</div></section>

            <section className="lp-pricing" id="pricing"><div className="lp-container lp-pricing-layout"><div><p className="lp-eyebrow">GarageOS / ПОДПИСКА</p><h2>Растите в своём темпе.</h2><p>Два действующих тарифа. Можно начать с базовых процессов и подключить склад с финансовой аналитикой по мере роста.</p></div><div className="lp-plan-grid"><article className="lp-plan"><div><span>START</span><b>Для небольшой команды</b></div><p>39 900 <small>₸ / месяц</small></p><ul><li>До 3 сотрудников</li><li>Клиенты, авто и история</li><li>Записи и заказ-наряды</li><li>Базовая аналитика</li></ul><Link href="/register">Выбрать Start <ArrowRight size={15} /></Link></article><article className="lp-plan is-featured"><div><span>BUSINESS</span><b>Для растущего сервиса</b></div><p>69 900 <small>₸ / месяц</small></p><ul><li>До 10 сотрудников</li><li>Всё из тарифа Start</li><li>Склад и учёт расходов</li><li>Расширенная аналитика</li></ul><Link href="/register">Выбрать Business <ArrowRight size={15} /></Link></article></div></div></section>

            <section className="lp-final"><div className="lp-container"><p className="lp-eyebrow">GarageOS / СЛЕДУЮЩИЙ ШАГ</p><h2>Пусть порядок<br />работает на вас.</h2><div><p>Создайте рабочее пространство и настройте GarageOS под процессы своего сервиса.</p><Link className="lp-button lp-button-primary" href="/register">Создать аккаунт <ArrowRight size={16} /></Link></div></div></section>
        </main>
        <footer className="lp-footer"><div className="lp-container"><a className="lp-brand" href="#top"><img src="/logo.jpg" alt="" /><span>GarageOS</span></a><p>Операционная система современного автосервиса</p><div><a href="#features">Возможности</a><a href="#pricing">Тарифы</a><a href="/demo">Демо</a><Link href="/login">Войти</Link></div><small>© {new Date().getFullYear()} GarageOS</small></div></footer>
    </div>;
}