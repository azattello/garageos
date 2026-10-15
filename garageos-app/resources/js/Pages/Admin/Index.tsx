import { Form, Link } from '@inertiajs/react';
import { Area } from '../../components/charts/area';
import { AreaChart } from '../../components/charts/area-chart';
import { Bar } from '../../components/charts/bar';
import { BarChart } from '../../components/charts/bar-chart';
import { BarXAxis } from '../../components/charts/bar-x-axis';
import { chartCssVars } from '../../components/charts/chart-context';
import { Grid } from '../../components/charts/grid';
import { ChartTooltip } from '../../components/charts/tooltip/chart-tooltip';
import { XAxis } from '../../components/charts/x-axis';
import AdminLayout from '../../Layouts/AdminLayout';

type Trend = { date: string; label: string; revenue: number; expenses: number; net: number; organizations: number; subscriptions: number; users: number };
type ChangeRequest = { id: number; organization: { id: number; name: string }; requester: { name: string; email: string }; current_plan: string; requested_plan: string; quoted_amount: number; currency: string; requested_at: string };
type Props = {
    metrics: { organizations: number; activeOrganizations: number; blockedOrganizations: number; archivedOrganizations: number; activeSubscriptions: number; trialingSubscriptions: number; expiredSubscriptions: number; suspendedSubscriptions: number; cancelledSubscriptions: number; users: number; mrr: number; arr: number; arpu: number | null; payingOrganizations: number; revenueMonth: number; expensesMonth: number; netMonth: number; revenueYear: number; expensesYear: number; netYear: number; newOrganizationsMonth: number; newOrganizationsChange: number | null; newSubscriptionsMonth: number };
    trend: Trend[];
    planDistribution: { name: string; count: number; mrr: number }[];
    recentOrganizations: { id: number; name: string; status: string; owner?: string; plan?: string; subscription_status?: string; created_at?: string | null }[];
    recentActivity: { id: number; action: string; actor: string; created_at?: string | null }[];
    pendingPlanChanges: { data: ChangeRequest[]; links?: { url: string | null; label: string; active: boolean }[]; total: number };
};

const money = (value: number | null) => value === null ? '—' : `${value.toLocaleString('ru-RU')} ₸`;
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('ru-RU', { dateStyle: 'medium' }).format(new Date(value)) : '—';

export default function Index({ metrics, trend, planDistribution, recentOrganizations, recentActivity, pendingPlanChanges }: Props) {
    const chartTrend = trend.map((point) => ({ ...point, date: new Date(`${point.date}T00:00:00`) }));

    return <AdminLayout title="Обзор платформы">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">GarageOS SaaS</p><h1 className="mt-1 text-2xl font-semibold">Обзор платформы</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Текущие показатели организаций, подписок и SaaS-денежного потока.</p></div><span className="border border-[var(--garage-border)] px-2.5 py-1 text-[10px] text-[var(--garage-text-muted)]">Данные на {formatDate(new Date().toISOString())}</span></div>

        <section className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Metric label="Организации" value={String(metrics.organizations)} detail={`${metrics.activeOrganizations} активны · ${metrics.blockedOrganizations} заблокированы · ${metrics.archivedOrganizations} в архиве`} />
            <Metric label="MRR" value={money(metrics.mrr)} detail={`ARR ${money(metrics.arr)} · ARPU ${money(metrics.arpu)}`} />
            <Metric label="Доход · месяц" value={money(metrics.revenueMonth)} detail={`С начала года ${money(metrics.revenueYear)}`} />
            <Metric label="Расходы · месяц" value={money(metrics.expensesMonth)} detail={`С начала года ${money(metrics.expensesYear)}`} />
            <Metric label="Чистый результат · месяц" value={money(metrics.netMonth)} detail={`Годовой итог ${money(metrics.netYear)}`} />
            <Metric label="Подписки active" value={String(metrics.activeSubscriptions)} detail={`${metrics.payingOrganizations} платящих организаций`} />
            <Metric label="Trial" value={String(metrics.trialingSubscriptions)} detail={`${metrics.expiredSubscriptions} истекли · ${metrics.suspendedSubscriptions} приостановлены`} />
            <Metric label="Новые организации" value={String(metrics.newOrganizationsMonth)} detail={`${metrics.newOrganizationsChange === null ? 'Нет базы для сравнения' : `${metrics.newOrganizationsChange >= 0 ? '+' : ''}${metrics.newOrganizationsChange}% к прошлому месяцу`} · ${metrics.newSubscriptionsMonth} подписок создано`} />
        </section>

        <section className="mt-7 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
            <div className="min-w-0 border-t border-[var(--garage-border)] pt-4"><div className="flex items-end justify-between gap-3"><div><h2 className="text-sm font-semibold">Денежный поток</h2><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">Поступления и SaaS-расходы за последние 12 месяцев</p></div><Link className="text-[10px] text-[var(--garage-accent)]" href="/admin/finance">Финансы</Link></div>{chartTrend.some((point) => point.revenue || point.expenses) ? <div className="mt-4"><AreaChart data={chartTrend} xDataKey="date" aspectRatio="2.4 / 1" margin={{ top: 18, right: 12, bottom: 36, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Area dataKey="revenue" fill={chartCssVars.linePrimary} fillOpacity={0.3} /><Area dataKey="expenses" fill={chartCssVars.lineSecondary} fillOpacity={0.2} /><XAxis numTicks={6} /><ChartTooltip /></AreaChart><div className="mt-3 flex gap-4 text-[10px] text-[var(--garage-text-muted)]"><span><i className="mr-2 inline-block size-2 bg-[var(--chart-line-primary)]" />Доходы</span><span><i className="mr-2 inline-block size-2 bg-[var(--chart-line-secondary)]" />Расходы</span></div></div> : <Empty>Финансовых операций пока нет.</Empty>}</div>
            <div className="min-w-0 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Активные тарифы</h2>{planDistribution.some((plan) => plan.count > 0) ? <div className="mt-4"><BarChart data={planDistribution} xDataKey="name" aspectRatio="1.8 / 1" margin={{ top: 16, right: 10, bottom: 34, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Bar dataKey="count" fill={chartCssVars.linePrimary} /><BarXAxis maxLabels={4} /><ChartTooltip showCrosshair={false} /></BarChart></div> : <Empty>Активных платных подписок нет.</Empty>}{planDistribution.map((plan) => <div className="flex justify-between border-t border-[var(--garage-border-subtle)] py-2 text-[10px]" key={plan.name}><span>{plan.name}</span><span>{plan.count} · {money(plan.mrr)} MRR</span></div>)}</div>
        </section>

        <section className="mt-7 border-t border-[var(--garage-border)] pt-4"><div className="flex items-center justify-between gap-3"><div><h2 className="text-sm font-semibold">Заявки на тариф</h2><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">Подтверждение фиксирует SaaS payment и активирует подписку</p></div><Link className="text-[10px] text-[var(--garage-accent)]" href="/admin/subscriptions">Все подписки</Link></div>{pendingPlanChanges.data.length ? <div className="mt-3 divide-y divide-[var(--garage-border-subtle)]">{pendingPlanChanges.data.map((change) => <div className="grid gap-3 py-4 xl:grid-cols-[1fr_1fr_1.2fr] xl:items-center" key={change.id}><div><Link className="text-xs font-medium hover:text-[var(--garage-accent)]" href={`/admin/organizations/${change.organization.id}`}>{change.organization.name}</Link><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{change.requester.name} · {change.requester.email}</p></div><p className="text-xs">{change.current_plan} → <strong>{change.requested_plan}</strong><span className="ml-2 text-[10px] text-[var(--garage-text-muted)]">{money(change.quoted_amount)} / мес.</span></p><div className="flex flex-wrap gap-2"><Form action={`/admin/subscription-requests/${change.id}`} method="post" className="flex min-w-0 flex-1 gap-2"><input name="decision" type="hidden" value="approve" /><input className="h-8 min-w-0 flex-1 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-[10px]" name="payment_reference" placeholder="Reference платежа" required /><button className="h-8 bg-[var(--garage-success)] px-2.5 text-[10px] font-semibold text-[var(--garage-background)]" type="submit">Подтвердить</button></Form><Form action={`/admin/subscription-requests/${change.id}`} method="post" onSubmit={(event) => { if (!window.confirm(`Отклонить заявку организации «${change.organization.name}»?`)) event.preventDefault(); }}><input name="decision" type="hidden" value="reject" /><button className="h-8 border border-[var(--garage-border)] px-2.5 text-[10px] text-[var(--garage-error)]" type="submit">Отклонить</button></Form></div></div>)}</div> : <Empty>Ожидающих заявок нет.</Empty>}{pendingPlanChanges.links && <Pagination links={pendingPlanChanges.links} />}</section>

        <section className="mt-7 grid gap-6 xl:grid-cols-2"><div className="border-t border-[var(--garage-border)] pt-4"><div className="flex justify-between"><h2 className="text-sm font-semibold">Новые организации</h2><Link className="text-[10px] text-[var(--garage-accent)]" href="/admin/organizations">Каталог</Link></div>{recentOrganizations.length ? <div className="mt-2 divide-y divide-[var(--garage-border-subtle)]">{recentOrganizations.map((organization) => <div className="grid grid-cols-[1fr_auto] gap-3 py-3" key={organization.id}><div><Link className="text-xs font-medium hover:text-[var(--garage-accent)]" href={`/admin/organizations/${organization.id}`}>{organization.name}</Link><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{organization.owner || 'Владелец не назначен'} · {organization.plan || 'Без тарифа'} · {organization.subscription_status || '—'}</p></div><span className="text-[10px] text-[var(--garage-text-muted)]">{formatDate(organization.created_at)}</span></div>)}</div> : <Empty>Организаций пока нет.</Empty>}</div><div className="border-t border-[var(--garage-border)] pt-4"><div className="flex justify-between"><h2 className="text-sm font-semibold">Системная активность</h2><Link className="text-[10px] text-[var(--garage-accent)]" href="/admin/audit-logs">Журнал аудита</Link></div>{recentActivity.length ? <div className="mt-2 divide-y divide-[var(--garage-border-subtle)]">{recentActivity.map((event) => <div className="flex justify-between gap-3 py-3" key={event.id}><div><p className="text-xs">{event.action}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{event.actor}</p></div><span className="text-[10px] text-[var(--garage-text-muted)]">{formatDate(event.created_at)}</span></div>)}</div> : <Empty>Событий пока нет.</Empty>}</div></section>
    </AdminLayout>;
}

function Metric({ label, value, detail }: { label: string; value: string; detail: string }) {
    return <article className="border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><p className="text-[10px] text-[var(--garage-text-muted)]">{label}</p><p className="mt-3 break-words text-xl font-semibold">{value}</p><p className="mt-1 text-[10px] leading-4 text-[var(--garage-text-muted)]">{detail}</p></article>;
}

function Empty({ children }: { children: React.ReactNode }) {
    return <div className="grid min-h-24 place-items-center text-center text-xs text-[var(--garage-text-muted)]">{children}</div>;
}

function Pagination({ links }: { links: { url: string | null; label: string; active: boolean }[] }) {
    return <div className="mt-3 flex flex-wrap gap-1">{links.map((link) => link.url ? <Link className={`border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</div>;
}
