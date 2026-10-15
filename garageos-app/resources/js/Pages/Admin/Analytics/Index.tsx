import { Form } from '@inertiajs/react';
import { Area } from '../../../components/charts/area';
import { AreaChart } from '../../../components/charts/area-chart';
import { Bar } from '../../../components/charts/bar';
import { BarChart } from '../../../components/charts/bar-chart';
import { BarXAxis } from '../../../components/charts/bar-x-axis';
import { chartCssVars } from '../../../components/charts/chart-context';
import { Grid } from '../../../components/charts/grid';
import { ChartTooltip } from '../../../components/charts/tooltip/chart-tooltip';
import { XAxis } from '../../../components/charts/x-axis';
import AdminLayout from '../../../Layouts/AdminLayout';

type Props = {
    period: string;
    from: string;
    to: string;
    periodOptions: { value: string; label: string }[];
    metrics: { organizations: number; newOrganizations: number; subscriptions: number; newSubscriptions: number; users: number; newUsers: number; mrr: number; arr: number; arpu: number | null; payingOrganizations: number; revenue: number; expenses: number; net: number; expiredSubscriptions: number; cancelledSubscriptions: number };
    trend: { date: string; label: string; revenue: number; expenses: number; net: number; organizations: number; subscriptions: number; users: number }[];
    planDistribution: { name: string; value: number }[];
    revenueByPlan: { name: string; amount: number }[];
    usage: { name: string; value: number }[];
};

const money = (value: number | null) => value === null ? '—' : `${value.toLocaleString('ru-RU')} ₸`;

export default function Index({ period, from, to, periodOptions, metrics, trend, planDistribution, revenueByPlan, usage }: Props) {
    const chartData = trend.map((point) => ({ ...point, date: new Date(`${point.date}T00:00:00`) }));
    const hasGrowth = chartData.some((point) => point.organizations || point.subscriptions || point.users);
    const hasMoney = chartData.some((point) => point.revenue || point.expenses || point.net);

    return <AdminLayout title="Аналитика">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"><div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Platform insights</p><h1 className="mt-1 text-2xl font-semibold">Аналитика SaaS</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Один период применяется ко всем временным рядам.</p></div><Form action="/admin/analytics" method="get" className="flex flex-wrap items-end gap-2"><Field label="Период"><select className={inputClass} defaultValue={period} name="period">{periodOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select></Field>{period === 'custom' && <><Field label="С"><input className={inputClass} defaultValue={from} name="from" type="date" /></Field><Field label="По"><input className={inputClass} defaultValue={to} name="to" type="date" /></Field></>}<button className="h-9 bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" type="submit">Применить</button></Form></div>
        <p className="mt-3 text-[10px] text-[var(--garage-text-muted)]">{from} — {to}</p>

        <section className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Metric label="Организации" value={String(metrics.organizations)} detail={`+${metrics.newOrganizations} за период`} /><Metric label="Подписки active" value={String(metrics.subscriptions)} detail={`+${metrics.newSubscriptions} создано за период`} /><Metric label="Пользователи" value={String(metrics.users)} detail={`+${metrics.newUsers} за период`} /><Metric label="MRR / ARR" value={money(metrics.mrr)} detail={`ARR ${money(metrics.arr)}`} /><Metric label="ARPU" value={money(metrics.arpu)} detail={`${metrics.payingOrganizations} активных платящих организаций`} /><Metric label="Доход периода" value={money(metrics.revenue)} detail="Только realized SaaS revenue" /><Metric label="Расходы периода" value={money(metrics.expenses)} detail="Только SaaS expenses" /><Metric label="Чистый результат" value={money(metrics.net)} detail="Доходы минус расходы" /></section>

        <section className="mt-7 grid gap-6 xl:grid-cols-2"><ChartSection title="Рост платформы" description="Количество созданных организаций, подписок и пользователей за период">{hasGrowth ? <AreaChart data={chartData} xDataKey="date" aspectRatio="2 / 1" margin={{ top: 18, right: 12, bottom: 36, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Area dataKey="organizations" fill={chartCssVars.linePrimary} fillOpacity={0.28} /><Area dataKey="subscriptions" fill={chartCssVars.lineSecondary} fillOpacity={0.24} /><Area dataKey="users" fill={chartCssVars.indicatorSecondaryColor} fillOpacity={0.16} /><XAxis numTicks={6} /><ChartTooltip /></AreaChart> : <Empty>Новых организаций, подписок и пользователей за период нет.</Empty>}</ChartSection>
            <ChartSection title="Денежный поток" description="Поступления и SaaS-расходы GarageOS за выбранный период">{hasMoney ? <AreaChart data={chartData} xDataKey="date" aspectRatio="2 / 1" margin={{ top: 18, right: 12, bottom: 36, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Area dataKey="revenue" fill={chartCssVars.linePrimary} fillOpacity={0.3} /><Area dataKey="expenses" fill={chartCssVars.lineSecondary} fillOpacity={0.2} /><Area dataKey="net" fill={chartCssVars.indicatorSecondaryColor} fillOpacity={0.12} /><XAxis numTicks={6} /><ChartTooltip /></AreaChart> : <Empty>Финансовых операций за период нет.</Empty>}</ChartSection></section>

        <section className="mt-6 grid gap-6 xl:grid-cols-3"><ChartSection title="Распределение тарифов" description="Текущие active subscriptions">{planDistribution.some((item) => item.value > 0) ? <BarChart data={planDistribution} xDataKey="name" aspectRatio="1.7 / 1" margin={{ top: 16, right: 8, bottom: 34, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Bar dataKey="value" fill={chartCssVars.linePrimary} /><BarXAxis maxLabels={4} /><ChartTooltip showCrosshair={false} /></BarChart> : <Empty>Активных подписок пока нет.</Empty>}</ChartSection>
            <ChartSection title="Выручка по тарифу" description="Оплаченные subscription payments за период">{revenueByPlan.length ? <BarChart data={revenueByPlan} xDataKey="name" aspectRatio="1.7 / 1" margin={{ top: 16, right: 8, bottom: 34, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Bar dataKey="amount" fill={chartCssVars.lineSecondary} /><BarXAxis maxLabels={4} /><ChartTooltip showCrosshair={false} /></BarChart> : <Empty>Поступлений по тарифам за период нет.</Empty>}</ChartSection>
            <ChartSection title="Использование продукта" description="Текущее суммарное использование по организациям">{usage.some((item) => item.value > 0) ? <BarChart data={usage} xDataKey="name" aspectRatio="1.7 / 1" margin={{ top: 16, right: 8, bottom: 34, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Bar dataKey="value" fill={chartCssVars.indicatorSecondaryColor} /><BarXAxis maxLabels={5} /><ChartTooltip showCrosshair={false} /></BarChart> : <Empty>Данных об использовании пока нет.</Empty>}</ChartSection></section>

        <section className="mt-7 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Состояние подписок сейчас</h2><div className="mt-3 grid gap-3 sm:grid-cols-2"><Metric label="Истекли" value={String(metrics.expiredSubscriptions)} detail="Текущий статус, не churn за выбранный период" /><Metric label="Отменены" value={String(metrics.cancelledSubscriptions)} detail="Текущий статус, не churn rate" /></div></section>
    </AdminLayout>;
}

const inputClass = 'h-9 min-w-0 border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-xs text-[var(--garage-text)]';

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}

function Metric({ label, value, detail }: { label: string; value: string; detail: string }) {
    return <article className="border border-[var(--garage-border)] bg-[var(--garage-surface)] p-3"><p className="text-[10px] text-[var(--garage-text-muted)]">{label}</p><p className="mt-2 break-words text-lg font-semibold">{value}</p><p className="mt-1 text-[10px] leading-4 text-[var(--garage-text-muted)]">{detail}</p></article>;
}

function ChartSection({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
    return <div className="min-w-0 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">{title}</h2><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{description}</p><div className="mt-3">{children}</div></div>;
}

function Empty({ children }: { children: React.ReactNode }) {
    return <div className="grid min-h-36 place-items-center text-center text-xs text-[var(--garage-text-muted)]">{children}</div>;
}