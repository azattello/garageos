import { ArrowUpRight, CalendarDays, CarFront, Check, CheckCircle2, ClipboardList, Clock3, CreditCard, FileText, Plus, UserRound, Wrench, X } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Area } from '../components/charts/area';
import { AreaChart } from '../components/charts/area-chart';
import { chartCssVars } from '../components/charts/chart-context';
import { Grid } from '../components/charts/grid';
import { ChartTooltip } from '../components/charts/tooltip/chart-tooltip';
import { XAxis } from '../components/charts/x-axis';
import AppLayout from '../Layouts/AppLayout';

type MetricData = { revenue: number; activeOrders: number; readyOrders: number; appointmentsToday: number; newClients: number; debt: number };
type AnalyticsData = { period: string; trend: { date: string; label: string; revenue: number; orders: number; average_check: number }[] };
type Appointment = { start_at: string; client: { name: string }; car: { make: string; model: string; plate_number?: string }; service: { name: string } };
type OnboardingTask = { key: string; title: string; description: string; href: string; completed: boolean };
type Props = { metrics: MetricData; schedule: Appointment[]; analytics: AnalyticsData; onboarding: OnboardingTask[] };

const money = (value: number) => `₸${value.toLocaleString('ru-RU')}`;

export default function Dashboard({ metrics, schedule, analytics, onboarding }: Props) {
    const { props } = usePage<any>();
    const displayName = props.auth?.user?.name || 'Пользователь';
    const userId = props.auth?.user?.id;
    const [onboardingDismissed, setOnboardingDismissed] = useState(false);
    const completedSteps = onboarding.filter((task) => task.completed).length;
    const onboardingComplete = completedSteps === onboarding.length;
    const onboardingStorageKey = `GarageOS-onboarding-dismissed-${userId ?? 'user'}`;

    useEffect(() => {
        try {
            setOnboardingDismissed(window.localStorage.getItem(onboardingStorageKey) === 'true');
        } catch {
            setOnboardingDismissed(false);
        }
    }, [onboardingStorageKey]);

    const dismissOnboarding = () => {
        setOnboardingDismissed(true);
        try {
            window.localStorage.setItem(onboardingStorageKey, 'true');
        } catch {
            setOnboardingDismissed(true);
        }
    };
    const cards = [
        { label: 'Выручка', value: money(metrics.revenue), note: 'успешные оплаты', icon: CreditCard },
        { label: 'Активные заказы', value: String(metrics.activeOrders), note: 'в работе сейчас', icon: ClipboardList },
        { label: 'Готовы к выдаче', value: String(metrics.readyOrders), note: 'готовые автомобили', icon: CheckCircle2 },
        { label: 'Автомобилей сегодня', value: String(metrics.appointmentsToday), note: 'записи на сегодня', icon: CarFront },
    ];
    const chartTrend = analytics.trend.map((item) => ({ ...item, date: new Date(`${item.date}T12:00:00`) }));

    const quickActions = [
        { label: 'Новая заявка', href: '/appointments', icon: CalendarDays, accent: true },
        { label: 'Новый клиент', href: '/clients', icon: UserRound },
        { label: 'Новый автомобиль', href: '/clients', icon: CarFront },
        { label: 'Новый заказ', href: '/orders', icon: ClipboardList },
    ];

    return <AppLayout>
        <section className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Операционный обзор</p><h1 className="mt-2 text-2xl font-semibold tracking-tight">Сегодня, {displayName}</h1><p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Ближайшие заявки, активные заказы и состояние сервисного дня.</p></div><Link className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[var(--garage-accent)] px-3.5 text-xs font-semibold text-[var(--garage-background)]" href="/appointments"><Plus size={15} />Новая заявка</Link></section>

        {!onboardingComplete && !onboardingDismissed && <section className="mt-5 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4 sm:p-5" aria-label="Настройка рабочего пространства">
            <div className="flex items-start justify-between gap-3">
                <div><p className="text-[10px] font-medium uppercase tracking-[0.14em] text-[var(--garage-accent)]">Быстрый старт</p><h2 className="mt-1 text-base font-semibold">Подготовьте GarageOS к работе</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Выполнено {completedSteps} из {onboarding.length} шагов</p></div>
                <button aria-label="Пропустить быстрый старт" className="rounded-md p-1.5 text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]" onClick={dismissOnboarding} title="Пропустить" type="button"><X size={15} /></button>
            </div>
            <div aria-label={`Прогресс: ${completedSteps} из ${onboarding.length}`} className="mt-3 h-1 overflow-hidden rounded-full bg-[var(--garage-surface-elevated)]" role="progressbar" aria-valuemax={onboarding.length} aria-valuemin={0} aria-valuenow={completedSteps}><div className="h-full rounded-full bg-[var(--garage-accent)] transition-[width] duration-200" style={{ width: `${onboarding.length ? (completedSteps / onboarding.length) * 100 : 100}%` }} /></div>
            <div className="mt-3 grid gap-x-6 sm:grid-cols-2">
                {onboarding.map((task) => <OnboardingItem key={task.key} task={task} />)}
            </div>
        </section>}

        <section className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{cards.map(({ label, value, note, icon: Icon }) => <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4" key={label}><div className="flex items-center justify-between"><p className="text-xs text-[var(--garage-text-secondary)]">{label}</p><Icon className="text-[var(--garage-text-muted)]" size={16} strokeWidth={1.8} /></div><p className="mt-4 text-[30px] font-semibold tracking-tight">{value}</p><p className="mt-2 text-xs text-[var(--garage-text-muted)]">{note}</p></article>)}</section>

        <section className="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">{quickActions.map(({ label, href, icon: Icon, accent }) => <Link className={`flex items-center gap-3 rounded-[10px] border p-3.5 transition-colors ${accent ? 'border-[var(--garage-accent)] bg-[rgba(184,255,61,0.08)] text-[var(--garage-text)]' : 'border-[var(--garage-border)] bg-[var(--garage-surface)] text-[var(--garage-text-secondary)] hover:border-[var(--garage-text-muted)]'}`} href={href} key={label}><span className={`grid size-9 place-items-center rounded-md ${accent ? 'bg-[var(--garage-accent)] text-[var(--garage-background)]' : 'bg-[var(--garage-surface-secondary)] text-[var(--garage-accent)]'}`}><Icon size={16} /></span><div><p className="text-[11px] uppercase tracking-[0.12em] text-[var(--garage-text-muted)]">Быстро</p><p className="mt-1 text-sm font-medium">{label}</p></div></Link>)}</section>

        <section className="mt-6 grid gap-4 xl:grid-cols-[1.35fr_0.65fr]"><article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]"><div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5"><div><h2 className="text-sm font-semibold">Расписание на сегодня</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Реальные записи организации</p></div><Link className="text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]" href="/appointments">Открыть календарь</Link></div>{schedule.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{schedule.map((item) => <div className="grid gap-3 px-4 py-3.5 sm:grid-cols-[54px_1fr_auto] sm:items-center" key={`${item.start_at}-${item.client.name}`}><p className="text-xs font-semibold">{new Date(item.start_at).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}</p><div><p className="text-sm font-medium">{item.client.name}</p><p className="mt-1 text-xs text-[var(--garage-text-muted)]">{item.car.make} {item.car.model} · {item.service.name}</p></div><ArrowUpRight className="text-[var(--garage-text-muted)]" size={15} /></div>)}</div> : <div className="grid min-h-44 place-items-center p-8 text-center"><div><Clock3 className="mx-auto text-[var(--garage-text-muted)]" size={22} /><p className="mt-3 text-sm font-medium">На сегодня записей нет</p><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Расписание появится после создания первой записи.</p></div></div>}</article><article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><h2 className="text-sm font-semibold">Контроль</h2><div className="mt-4 space-y-3"><Control label="Новые клиенты сегодня" value={String(metrics.newClients)} href="/clients" /><Control label="Общий долг" value={money(metrics.debt)} href="/orders" warning={metrics.debt > 0} /></div></article></section>

        <section className="mt-6 grid gap-4 xl:grid-cols-[1.35fr_0.65fr]"><article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]"><div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5"><div><h2 className="text-sm font-semibold">Динамика выручки</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Последние 7 дней · успешные оплаты</p></div><Link className="text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]" href="/analytics">Вся аналитика</Link></div><div className="px-4 pb-4 pt-2"><AreaChart className="w-full" data={chartTrend} xDataKey="date" aspectRatio="3.5 / 1"><Grid horizontal /><Area dataKey="revenue" fill={chartCssVars.linePrimary} fillOpacity={0.32} /><XAxis numTicks={7} tickMode="data" /><ChartTooltip /></AreaChart></div></article><article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]"><div className="border-b border-[var(--garage-border-subtle)] px-4 py-3.5"><h2 className="text-sm font-semibold">Показатели периода</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Последние 7 дней</p></div><div className="space-y-4 p-4"><AnalyticsStat label="Заказов" value={String(analytics.trend.reduce((sum, item) => sum + item.orders, 0))} /><AnalyticsStat label="Выручка" value={money(analytics.trend.reduce((sum, item) => sum + item.revenue, 0))} /><AnalyticsStat label="Средний чек" value={money(Math.round(analytics.trend.reduce((sum, item) => sum + item.revenue, 0) / Math.max(analytics.trend.reduce((sum, item) => sum + item.orders, 0), 1)))} /></div></article></section>
    </AppLayout>;
}

function AnalyticsStat({ label, value }: { label: string; value: string }) { return <div className="flex items-end justify-between border-b border-[var(--garage-border-subtle)] pb-3"><span className="text-xs text-[var(--garage-text-secondary)]">{label}</span><strong className="text-sm">{value}</strong></div>; }
function Control({ label, value, href, warning }: { label: string; value: string; href: string; warning?: boolean }) { return <Link className="flex items-center justify-between rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3 hover:border-[var(--garage-text-muted)]" href={href}><span className="text-xs text-[var(--garage-text-secondary)]">{label}</span><span className={`text-sm font-semibold ${warning ? 'text-[var(--garage-warning)]' : ''}`}>{value}</span></Link>; }
function OnboardingItem({ task }: { task: OnboardingTask }) {
    const content = <><span className={`grid size-7 shrink-0 place-items-center rounded-md ${task.completed ? 'bg-[rgba(114,224,106,0.1)] text-[var(--garage-success)]' : 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text-muted)]'}`}>{task.completed ? <Check size={14} /> : <ArrowUpRight size={14} />}</span><span className="min-w-0 flex-1"><span className={`block truncate text-xs font-medium ${task.completed ? 'text-[var(--garage-text-muted)]' : 'text-[var(--garage-text)]'}`}>{task.title}</span><span className="mt-0.5 block truncate text-[10px] text-[var(--garage-text-muted)]">{task.description}</span></span>{task.completed && <span className="text-[10px] text-[var(--garage-success)]">Готово</span>}</>;
    return task.completed ? <div className="flex items-center gap-2.5 py-2">{content}</div> : <Link className="flex items-center gap-2.5 rounded-md py-2 transition-colors hover:bg-[var(--garage-surface-secondary)]" href={task.href}>{content}</Link>;
}
