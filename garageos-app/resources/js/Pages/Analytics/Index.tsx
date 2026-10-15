import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowDownRight, ArrowUpRight, BarChart3, BriefcaseBusiness, Check, CreditCard, Download, UsersRound, Wrench, type LucideIcon } from 'lucide-react';
import { Bar } from '../../components/charts/bar';
import { BarChart } from '../../components/charts/bar-chart';
import { BarXAxis } from '../../components/charts/bar-x-axis';
import { BarYAxis } from '../../components/charts/bar-y-axis';
import { chartCssVars } from '../../components/charts/chart-context';
import { Grid } from '../../components/charts/grid';
import { ChartTooltip } from '../../components/charts/tooltip/chart-tooltip';
import AppLayout from '../../Layouts/AppLayout';

type TrendPoint = { date: string; label: string; revenue: number; expenses: number; orders: number; average_check: number };
type MetricData = { revenue: number; revenueChange: number | null; expenses: number; expensesChange: number | null; operatingResult: number; operatingResultChange: number | null; orders: number; ordersChange: number | null; averageCheck: number; debt: number; activeOrders: number; overdueOrders: number };
type StatusData = { slug: string; name: string; count: number };
type ServiceData = { name: string; revenue: number; quantity: number };
type MechanicData = { id: number; name: string; active_orders: number; completed_orders: number };
type Props = { period: string; periodOptions: { value: string; label: string }[]; metrics: MetricData; trend: TrendPoint[]; statuses: StatusData[]; topServices: ServiceData[]; mechanics: MechanicData[] };

const money = (value: number) => `₸${value.toLocaleString('ru-RU')}`;

export default function Index({ period, periodOptions, metrics, trend, statuses, topServices, mechanics }: Props) {
    const periodLabel = periodOptions.find((item) => item.value === period)?.label ?? '30 дней';

    return (
        <AppLayout>
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Показатели сервиса</p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">Аналитика</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Деньги, заказы и загрузка команды в одном обзоре.</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <div aria-label="Период отчёта" className="inline-flex w-fit rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-1" role="group">
                        {periodOptions.map((option) => (
                            <Link aria-current={period === option.value ? 'page' : undefined} className={`rounded px-3 py-1.5 text-xs transition-colors ${period === option.value ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)] hover:text-[var(--garage-text)]'}`} href={`/analytics?period=${option.value}`} key={option.value} preserveScroll>
                                {option.label}
                            </Link>
                        ))}
                    </div>
                    <a className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]" download href={`/analytics/export?period=${period}`}><Download size={14} />CSV</a>
                </div>
            </div>

            <section className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <MetricCard label="Получено" value={money(metrics.revenue)} caption={`${changeLabel(metrics.revenueChange)} к предыдущему периоду`} icon={CreditCard} change={metrics.revenueChange} />
                <MetricCard label="Расходы" value={money(metrics.expenses)} caption={`${changeLabel(metrics.expensesChange)} к предыдущему периоду`} icon={CreditCard} change={metrics.expensesChange} warning={metrics.expenses > 0} />
                <MetricCard label="Операционный итог" value={money(metrics.operatingResult)} caption={`${changeLabel(metrics.operatingResultChange)} к предыдущему периоду`} icon={BarChart3} change={metrics.operatingResultChange} warning={metrics.operatingResult < 0} />
                <MetricCard label="Новые заказ-наряды" value={String(metrics.orders)} caption={`${changeLabel(metrics.ordersChange)} к предыдущему периоду`} icon={BriefcaseBusiness} change={metrics.ordersChange} />
                <MetricCard label="Средний чек" value={money(metrics.averageCheck)} caption={`за ${periodLabel.toLocaleLowerCase('ru')}`} icon={BarChart3} />
                <MetricCard label="Долг клиентов" value={money(metrics.debt)} caption={`${metrics.activeOrders} активных заказов`} icon={AlertTriangle} warning={metrics.debt > 0} />
            </section>

            <section className="mt-4 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="flex flex-col justify-between gap-2 border-b border-[var(--garage-border-subtle)] px-4 py-3.5 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-sm font-semibold">Поступления и расходы</h2>
                        <p className="mt-1 text-xs text-[var(--garage-text-muted)]">Поступления по успешным платежам · {periodLabel.toLocaleLowerCase('ru')}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3 text-[10px] text-[var(--garage-text-muted)]">
                        <span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--garage-accent)]" />Поступления</span>
                        <span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--garage-error)]" />Расходы</span>
                        {metrics.overdueOrders > 0 && <span className="inline-flex items-center gap-1.5 text-[var(--garage-warning)]"><AlertTriangle size={14} />{metrics.overdueOrders} просроченных</span>}
                    </div>
                </div>
                <div className="px-4 pb-4 pt-3">
                    <BarChart className="w-full" data={trend} xDataKey="label" aspectRatio="3 / 1">
                        <Grid horizontal />
                        <Bar dataKey="revenue" fill={chartCssVars.linePrimary} />
                        <Bar dataKey="expenses" fill="var(--chart-3)" />
                        <BarXAxis maxLabels={12} />
                        <ChartTooltip />
                    </BarChart>
                    {!trend.some((point) => point.revenue > 0 || point.expenses > 0) && <p className="mt-3 text-center text-xs text-[var(--garage-text-muted)]">За этот период поступлений и расходов не было.</p>}
                </div>
            </section>

            <section className="mt-4 grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                        <div><h2 className="text-sm font-semibold">Услуги по выручке</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Позиции заказов за {periodLabel.toLocaleLowerCase('ru')}</p></div>
                        <Wrench className="text-[var(--garage-text-muted)]" size={16} />
                    </div>
                    {topServices.length ? (
                        <div className="p-3">
                            <BarChart className="w-full" data={topServices} xDataKey="name" orientation="horizontal" aspectRatio="2 / 1">
                                <Grid vertical />
                                <Bar dataKey="revenue" fill={chartCssVars.linePrimary} />
                                <BarYAxis maxLabels={5} />
                                <ChartTooltip />
                            </BarChart>
                        </div>
                    ) : <EmptyPanel text="В выбранном периоде пока нет выполненных работ." />}
                </article>

                <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                        <div><h2 className="text-sm font-semibold">Этапы заказов</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Распределение за {periodLabel.toLocaleLowerCase('ru')}</p></div>
                        <BriefcaseBusiness className="text-[var(--garage-text-muted)]" size={16} />
                    </div>
                    {statuses.some((status) => status.count > 0) ? <div className="p-3">
                        <BarChart className="w-full" data={statuses.filter((status) => status.count > 0)} xDataKey="name" orientation="horizontal" aspectRatio="1.4 / 1">
                            <Grid vertical />
                            <Bar dataKey="count" fill={chartCssVars.lineSecondary} />
                            <BarYAxis maxLabels={10} />
                            <ChartTooltip />
                        </BarChart>
                    </div> : <p className="py-5 text-center text-xs text-[var(--garage-text-muted)]">Заказов за этот период нет.</p>}
                </article>
            </section>

            <section className="mt-4 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                    <div><h2 className="text-sm font-semibold">Загрузка мастеров</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Активные и завершённые заказы за выбранный период.</p></div>
                    <UsersRound className="text-[var(--garage-text-muted)]" size={16} />
                </div>
                {mechanics.length ? (
                    <div className="p-3">
                        <div className="mb-3 flex flex-wrap gap-3 text-[10px] text-[var(--garage-text-muted)]">
                            <span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--garage-accent)]" />В работе</span>
                            <span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--chart-3)]" />Завершено</span>
                        </div>
                        <BarChart className="w-full" data={mechanics} xDataKey="name" orientation="horizontal" aspectRatio="2 / 1">
                            <Grid vertical />
                            <Bar dataKey="active_orders" fill={chartCssVars.linePrimary} />
                            <Bar dataKey="completed_orders" fill="var(--chart-3)" />
                            <BarYAxis maxLabels={5} />
                            <ChartTooltip />
                        </BarChart>
                    </div>
                ) : <EmptyPanel text="Добавьте механиков и назначайте их на заказ-наряды, чтобы видеть загрузку." />}
            </section>
        </AppLayout>
    );
}

function MetricCard({ label, value, caption, icon: Icon, change, warning }: { label: string; value: string; caption: string; icon: LucideIcon; change?: number | null; warning?: boolean }) {
    const ChangeIcon = change !== undefined && change !== null && change < 0 ? ArrowDownRight : ArrowUpRight;
    return (
        <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
            <div className="flex items-center justify-between gap-2"><p className="text-xs text-[var(--garage-text-secondary)]">{label}</p><Icon className={warning ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-text-muted)]'} size={15} /></div>
            <p className="mt-3 truncate text-2xl font-semibold tabular-nums">{value}</p>
            <p className={`mt-1.5 flex items-center gap-1 text-[10px] ${warning ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-text-muted)]'}`}>{change !== undefined && change !== null && <ChangeIcon className={change > 0 ? 'text-[var(--garage-success)]' : change < 0 ? 'text-[var(--garage-error)]' : 'text-[var(--garage-text-muted)]'} size={12} />}{change === null ? 'Нет данных для сравнения' : caption}</p>
        </article>
    );
}

function EmptyPanel({ text }: { text: string }) {
    return <div className="px-4 py-8 text-center text-xs text-[var(--garage-text-muted)]">{text}</div>;
}

function changeLabel(value: number | null) {
    if (value === null) return 'без сравнения';
    return `${value > 0 ? '+' : ''}${value}%`;
}

function countLabel(value: number, forms: [string, string, string]) {
    const whole = Math.abs(Math.trunc(value));
    const lastTwo = whole % 100;
    const last = whole % 10;
    const form = lastTwo >= 11 && lastTwo <= 14 ? forms[2] : last === 1 ? forms[0] : last >= 2 && last <= 4 ? forms[1] : forms[2];
    return `${value.toLocaleString('ru-RU')} ${form}`;
}

function initials(name: string) {
    return name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
}
