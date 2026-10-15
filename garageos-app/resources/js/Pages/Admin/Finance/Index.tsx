import { Form, Link } from '@inertiajs/react';
import { Area } from '../../../components/charts/area';
import { AreaChart } from '../../../components/charts/area-chart';
import { Bar } from '../../../components/charts/bar';
import { BarChart } from '../../../components/charts/bar-chart';
import { BarXAxis } from '../../../components/charts/bar-x-axis';
import { Grid } from '../../../components/charts/grid';
import { chartCssVars } from '../../../components/charts/chart-context';
import { XAxis } from '../../../components/charts/x-axis';
import { ChartTooltip } from '../../../components/charts/tooltip/chart-tooltip';
import AdminLayout from '../../../Layouts/AdminLayout';

type Page<T> = { data: T[]; links?: { url: string | null; label: string; active: boolean }[] };
type Revenue = { id: number; amount: number; currency: string; category: string; organization?: string; description?: string; date: string };
type Expense = { id: number; amount: number; currency: string; category: string; vendor?: string; description?: string; date: string };
type Props = {
    period: string;
    from: string;
    to: string;
    periodOptions: { value: string; label: string }[];
    metrics: { revenue: number; expenses: number; net: number };
    trend: { date: string; label: string; revenue: number; expenses: number; net: number }[];
    revenueByCategory: { name: string; amount: number }[];
    expensesByCategory: { name: string; amount: number }[];
    revenueByPlan: { name: string; amount: number }[];
    revenueRows: Page<Revenue>;
    expenseRows: Page<Expense>;
    organizations: { id: number; name: string }[];
    revenueCategories: Record<string, string>;
    expenseCategories: Record<string, string>;
};

const money = (amount: number, currency = 'KZT') => `${amount.toLocaleString('ru-RU')} ${currency === 'KZT' ? '₸' : currency}`;

export default function Index(props: Props) {
    const chartTrend = props.trend.map((point) => ({ ...point, date: new Date(`${point.date}T00:00:00`) }));

    return <AdminLayout title="Финансы SaaS">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Финансы GarageOS</p><h1 className="mt-1 text-2xl font-semibold">Доходы и расходы платформы</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Финансы автосервисов сюда не входят.</p></div>
            <Form action="/admin/finance" method="get" className="flex flex-wrap items-end gap-2">
                <label className="grid gap-1 text-[10px] text-[var(--garage-text-muted)]">Период<select className="h-9 min-w-32 border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-xs text-[var(--garage-text)]" defaultValue={props.period} name="period">{props.periodOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select></label>
                {props.period === 'custom' && <><label className="grid gap-1 text-[10px] text-[var(--garage-text-muted)]">С даты<input className="h-9 border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-xs" defaultValue={props.from} name="from" type="date" /></label><label className="grid gap-1 text-[10px] text-[var(--garage-text-muted)]">По дату<input className="h-9 border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-xs" defaultValue={props.to} name="to" type="date" /></label></>}
                <button className="h-9 bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" type="submit">Применить</button>
            </Form>
        </div>

        <section className="mt-6 grid gap-3 sm:grid-cols-3">
            <Metric label="Доход" value={money(props.metrics.revenue)} detail={`${props.from} — ${props.to}`} />
            <Metric label="Расходы" value={money(props.metrics.expenses)} detail={`${props.from} — ${props.to}`} />
            <Metric label="Чистый результат" value={money(props.metrics.net)} detail="Доходы минус расходы" />
        </section>

        <section className="mt-7 grid gap-6 xl:grid-cols-[1.4fr_1fr_1fr]">
            <div className="min-w-0 border-t border-[var(--garage-border)] pt-4">
                <h2 className="text-sm font-semibold">Движение средств</h2>
                {chartTrend.some((point) => point.revenue || point.expenses || point.net) ? <div className="mt-4"><AreaChart data={chartTrend} xDataKey="date" className="w-full" aspectRatio="2.1 / 1" margin={{ top: 18, right: 12, bottom: 36, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Area dataKey="revenue" fill={chartCssVars.linePrimary} fillOpacity={0.28} /><Area dataKey="expenses" fill={chartCssVars.lineSecondary} fillOpacity={0.2} /><Area dataKey="net" fill={chartCssVars.indicatorSecondaryColor} fillOpacity={0.14} /><XAxis numTicks={6} /><ChartTooltip /></AreaChart><div className="mt-3 flex flex-wrap gap-4 text-[10px] text-[var(--garage-text-muted)]"><span><i className="mr-2 inline-block size-2 bg-[var(--chart-line-primary)]" />Доход</span><span><i className="mr-2 inline-block size-2 bg-[var(--chart-line-secondary)]" />Расходы</span><span><i className="mr-2 inline-block size-2 bg-[var(--chart-indicator-secondary-color)]" />Чистый результат</span></div></div> : <Empty>За выбранный период финансовых операций нет.</Empty>}
            </div>
            <div className="min-w-0 border-t border-[var(--garage-border)] pt-4">
                <h2 className="text-sm font-semibold">Доход по категориям</h2>
                {props.revenueByCategory.length ? <div className="mt-4"><BarChart data={props.revenueByCategory} xDataKey="name" aspectRatio="1.7 / 1" margin={{ top: 16, right: 10, bottom: 34, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Bar dataKey="amount" fill={chartCssVars.linePrimary} /><BarXAxis maxLabels={5} /><ChartTooltip showCrosshair={false} /></BarChart></div> : <Empty>Нет доходов по категориям.</Empty>}
            </div>
            <div className="min-w-0 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Доход по тарифу</h2>{props.revenueByPlan.length ? <div className="mt-4"><BarChart data={props.revenueByPlan} xDataKey="name" aspectRatio="1.7 / 1" margin={{ top: 16, right: 10, bottom: 34, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Bar dataKey="amount" fill={chartCssVars.indicatorSecondaryColor} /><BarXAxis maxLabels={5} /><ChartTooltip showCrosshair={false} /></BarChart></div> : <Empty>У поступлений пока нет связи с тарифом.</Empty>}</div>
        </section>

        <section className="mt-6 grid gap-6 xl:grid-cols-2">
            <div className="min-w-0 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Расходы по категориям</h2>{props.expensesByCategory.length ? <div className="mt-4"><BarChart data={props.expensesByCategory} xDataKey="name" aspectRatio="1.7 / 1" margin={{ top: 16, right: 10, bottom: 34, left: 8 }}><Grid horizontal stroke={chartCssVars.grid} /><Bar dataKey="amount" fill={chartCssVars.lineSecondary} /><BarXAxis maxLabels={5} /><ChartTooltip showCrosshair={false} /></BarChart></div> : <Empty>Нет расходов по категориям.</Empty>}</div>
            <div className="border-t border-[var(--garage-border)] pt-4"><div className="flex flex-wrap items-center justify-between gap-3"><div><h2 className="text-sm font-semibold">Новая запись</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Суммы учитываются в тенге.</p></div></div>
                <div className="mt-4 grid gap-5 md:grid-cols-2">
                    <Form action="/admin/finance/revenues" method="post" className="grid content-start gap-3 border-l-2 border-[var(--garage-accent)] pl-4"><h3 className="text-xs font-semibold">Добавить доход</h3><Field label="Сумма, ₸"><input className={inputClass} min="1" name="amount" required type="number" /></Field><Field label="Дата"><input className={inputClass} defaultValue={props.to} name="received_at" required type="date" /></Field><Field label="Категория"><select className={inputClass} name="category">{Object.entries(props.revenueCategories).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field><Field label="Организация"><select className={inputClass} name="organization_id"><option value="">Без привязки</option>{props.organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}</select></Field><Field label="Описание"><input className={inputClass} maxLength={2000} name="description" /></Field><button className={primaryButton} type="submit">Сохранить доход</button></Form>
                    <Form action="/admin/finance/expenses" method="post" className="grid content-start gap-3 border-l-2 border-[var(--garage-info)] pl-4"><h3 className="text-xs font-semibold">Добавить расход</h3><Field label="Сумма, ₸"><input className={inputClass} min="1" name="amount" required type="number" /></Field><Field label="Дата"><input className={inputClass} defaultValue={props.to} name="expense_date" required type="date" /></Field><Field label="Категория"><select className={inputClass} name="category">{Object.entries(props.expenseCategories).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field><Field label="Поставщик"><input className={inputClass} maxLength={255} name="vendor" /></Field><Field label="Описание"><input className={inputClass} maxLength={2000} name="description" /></Field><button className={primaryButton} type="submit">Сохранить расход</button></Form>
                </div>
            </div>
        </section>

        <Ledger title="История доходов" empty="Доходов за выбранный период нет.">
            <div className="divide-y divide-[var(--garage-border-subtle)]">{props.revenueRows.data.map((row) => <div className="grid gap-2 py-3 text-xs sm:grid-cols-[1fr_1fr_auto] sm:items-center" key={row.id}><div><p className="font-medium">{row.category}{row.organization ? ` · ${row.organization}` : ''}</p><p className="mt-1 text-[var(--garage-text-muted)]">{row.description || 'Без описания'}</p></div><p className="text-[var(--garage-text-muted)]">{row.date}</p><p className="font-semibold text-[var(--garage-accent)]">{money(row.amount, row.currency)}</p></div>)}</div>
            <Pagination links={props.revenueRows.links} />
        </Ledger>
        <Ledger title="История расходов" empty="Расходов за выбранный период нет.">
            <div className="divide-y divide-[var(--garage-border-subtle)]">{props.expenseRows.data.map((row) => <div className="grid gap-2 py-3 text-xs sm:grid-cols-[1fr_1fr_auto] sm:items-center" key={row.id}><div><p className="font-medium">{row.category}{row.vendor ? ` · ${row.vendor}` : ''}</p><p className="mt-1 text-[var(--garage-text-muted)]">{row.description || 'Без описания'}</p></div><p className="text-[var(--garage-text-muted)]">{row.date}</p><p className="font-semibold">{money(row.amount, row.currency)}</p></div>)}</div>
            <Pagination links={props.expenseRows.links} />
        </Ledger>
    </AdminLayout>;
}

const inputClass = 'h-9 w-full min-w-0 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-xs text-[var(--garage-text)]';
const primaryButton = 'h-9 bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]';

function Metric({ label, value, detail }: { label: string; value: string; detail: string }) {
    return <article className="border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><p className="text-xs text-[var(--garage-text-muted)]">{label}</p><p className="mt-3 break-words text-xl font-semibold">{value}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{detail}</p></article>;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}

function Empty({ children }: { children: React.ReactNode }) {
    return <div className="grid min-h-32 place-items-center text-center text-xs text-[var(--garage-text-muted)]">{children}</div>;
}

function Ledger({ title, empty, children }: { title: string; empty: string; children: React.ReactNode }) {
    return <section className="mt-7 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">{title}</h2><div className="mt-2">{children}</div></section>;
}

function Pagination({ links }: { links?: { url: string | null; label: string; active: boolean }[] }) {
    return links?.length ? <div className="mt-3 flex flex-wrap gap-1">{links.map((link) => link.url ? <Link className={`border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</div> : null;
}