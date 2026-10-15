import { Form, Link } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, Banknote, ChartNoAxesCombined, CircleDollarSign, Plus, Receipt, TrendingUp, type LucideIcon } from 'lucide-react';
import { Bar } from '../../components/charts/bar';
import { BarChart } from '../../components/charts/bar-chart';
import { BarXAxis } from '../../components/charts/bar-x-axis';
import { BarYAxis } from '../../components/charts/bar-y-axis';
import { chartCssVars } from '../../components/charts/chart-context';
import { Grid } from '../../components/charts/grid';
import { ChartTooltip } from '../../components/charts/tooltip/chart-tooltip';
import AppLayout from '../../Layouts/AppLayout';

type Metric = { income: number; incomeChange: number | null; expenses: number; expensesChange: number | null; profit: number; profitChange: number | null; debt: number; averageCheck: number; orders: number };
type TrendPoint = { date: string; label: string; income: number; expenses: number; profit: number };
type Category = { id: number; name: string };
type CategoryTotal = { id: number; name: string; amount: number };
type Expense = { id: number; amount: number; description?: string | null; expense_date: string; category_id: number | null; category: string; creator?: string | null };
type Debtor = { client_id: number; name: string; phone?: string | null; debt: number; open_orders: number; last_order_at: string; archived: boolean };
type Paginator<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
type Props = { period: string; from: string; to: string; periodOptions: { value: string; label: string }[]; metrics: Metric; debtors: Debtor[]; trend: TrendPoint[]; categories: Category[]; categoryBreakdown: CategoryTotal[]; expenses: Paginator<Expense> };
const money = (value: number) => `₸${value.toLocaleString('ru-RU')}`;

export default function Index({ period, from, to, periodOptions, metrics, debtors, trend, categories, categoryBreakdown, expenses }: Props) {
    const periodLabel = periodOptions.find((item) => item.value === period)?.label ?? '30 дней';

    return (
        <AppLayout>
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Деньги сервиса</p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">Финансы</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Поступления, расходы и операционный результат.</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <div aria-label="Период отчёта" className="inline-flex w-fit rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-1" role="group">
                        {periodOptions.filter((option) => option.value !== 'custom').map((option) => <Link aria-current={period === option.value ? 'page' : undefined} className={`rounded px-3 py-1.5 text-xs transition-colors ${period === option.value ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)] hover:text-[var(--garage-text)]'}`} href={`/finance?period=${option.value}`} key={option.value} preserveScroll>{option.label}</Link>)}
                    </div>
                    <form action="/finance" className="flex flex-wrap items-center gap-1.5" method="get"><input name="period" type="hidden" value="custom" /><input aria-label="Начало периода" className="h-8 rounded border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-[10px]" defaultValue={from} name="from" type="date" /><input aria-label="Конец периода" className="h-8 rounded border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-[10px]" defaultValue={to} name="to" type="date" /><button className="h-8 rounded-md border border-[var(--garage-border)] px-2.5 text-[10px]" type="submit">Показать</button></form>
                </div>
            </div>

            <section className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <MetricCard label="Поступления" value={money(metrics.income)} caption={changeCaption(metrics.incomeChange)} change={metrics.incomeChange} icon={Banknote} />
                <MetricCard label="Расходы" value={money(metrics.expenses)} caption={changeCaption(metrics.expensesChange)} change={metrics.expensesChange} icon={Receipt} />
                <MetricCard label="Операционный итог" value={money(metrics.profit)} caption={changeCaption(metrics.profitChange)} change={metrics.profitChange} icon={TrendingUp} warning={metrics.profit < 0} />
                <MetricCard label="Средний чек" value={money(metrics.averageCheck)} caption={`${metrics.orders} заказов в периоде`} icon={CircleDollarSign} />
                <MetricCard label="Долг клиентов" value={money(metrics.debt)} caption="Положительный остаток по заказам" icon={CircleDollarSign} warning={metrics.debt > 0} />
            </section>

            <section className="mt-4 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3.5"><h2 className="text-sm font-semibold">Должники</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Клиенты с положительным долгом · максимум 100 записей</p></div>
                {debtors.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{debtors.map((debtor) => <div className="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_100px_100px] sm:items-center" key={debtor.client_id}><div className="min-w-0">{debtor.archived ? <span className="text-sm font-medium">{debtor.name} · архив</span> : <Link className="text-sm font-medium hover:text-[var(--garage-accent)]" href={`/clients/${debtor.client_id}`}>{debtor.name}</Link>}<p className="mt-1 text-xs text-[var(--garage-text-muted)]">{debtor.phone || 'Телефон не указан'} · {debtor.open_orders} заказов</p></div><span className="text-xs text-[var(--garage-text-muted)]">{formatDate(debtor.last_order_at.slice(0, 10))}</span><strong className="text-sm tabular-nums text-[var(--garage-warning)]">{money(debtor.debt)}</strong></div>)}</div> : <EmptyState text="Клиентов с долгом нет." />}
            </section>

            <section className="mt-4 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                    <div><h2 className="text-sm font-semibold">Движение денег</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Успешные оплаты и записанные расходы · {periodLabel.toLocaleLowerCase('ru')}</p></div>
                    <ChartNoAxesCombined className="text-[var(--garage-text-muted)]" size={16} />
                </div>
                <div className="px-4 pb-4 pt-3">
                    <BarChart className="w-full" data={trend} xDataKey="label" aspectRatio="3 / 1">
                        <Grid horizontal />
                        <Bar dataKey="income" fill={chartCssVars.linePrimary} />
                        <Bar dataKey="expenses" fill="var(--chart-3)" />
                        <BarXAxis maxLabels={12} />
                        <ChartTooltip />
                    </BarChart>
                    {!trend.some((point) => point.income > 0 || point.expenses > 0) && <p className="mt-3 text-center text-xs text-[var(--garage-text-muted)]">За этот период поступлений и расходов нет.</p>}
                    <div className="mt-3 flex gap-4 text-[10px] text-[var(--garage-text-muted)]"><span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--garage-accent)]" />Поступления</span><span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--garage-error)]" />Расходы</span></div>
                </div>
            </section>

            <section className="mt-4 grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                <div className="space-y-4">
                    <article className="overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5"><div><h2 className="text-sm font-semibold">Расходы по категориям</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">За {periodLabel.toLocaleLowerCase('ru')}</p></div><Receipt className="text-[var(--garage-text-muted)]" size={15} /></div>
                        {categoryBreakdown.length ? <div className="p-3"><BarChart className="w-full" data={categoryBreakdown} xDataKey="name" orientation="horizontal" aspectRatio="2 / 1"><Grid vertical /><Bar dataKey="amount" fill={chartCssVars.linePrimary} /><BarYAxis maxLabels={10} /><ChartTooltip /></BarChart></div> : <EmptyState text="В этом периоде расходы ещё не записаны." />}
                    </article>

                    <article className="overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5"><div><h2 className="text-sm font-semibold">Расходы</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">{periodLabel} · {expenses.total} записей</p></div><Receipt className="text-[var(--garage-text-muted)]" size={15} /></div>
                        {expenses.data.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{expenses.data.map((expense) => <details className="px-4 py-3" key={expense.id}>
                            <summary className="flex cursor-pointer list-none items-center justify-between gap-3"><div className="min-w-0"><p className="truncate text-xs font-medium">{expense.description || expense.category}</p><p className="mt-1 truncate text-[10px] text-[var(--garage-text-muted)]">{expense.category} · {formatDate(expense.expense_date)}{expense.creator ? ` · ${expense.creator}` : ''}</p></div><span className="shrink-0 text-xs font-semibold tabular-nums text-[var(--garage-error)]">−{money(expense.amount)}</span></summary>
                            <Form action={`/finance/expenses/${expense.id}?period=${period}&from=${from}&to=${to}`} method="put" className="mt-3 grid gap-2 border-t border-[var(--garage-border-subtle)] pt-3 sm:grid-cols-2">
                                {({ processing, errors }) => <><label className="text-[10px] text-[var(--garage-text-muted)]">Категория<select className="mt-1 h-8 w-full rounded border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs text-[var(--garage-text)]" defaultValue={expense.category_id ?? ''} name="category_id">{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select>{errors.category_id && <span className="text-[10px] text-[var(--garage-error)]">{errors.category_id}</span>}</label><label className="text-[10px] text-[var(--garage-text-muted)]">Сумма<input className="mt-1 h-8 w-full rounded border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue={expense.amount} min="1" name="amount" type="number" />{errors.amount && <span className="text-[var(--garage-error)]">{errors.amount}</span>}</label><label className="text-[10px] text-[var(--garage-text-muted)]">Дата<input className="mt-1 h-8 w-full rounded border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue={expense.expense_date} name="expense_date" type="date" />{errors.expense_date && <span className="text-[var(--garage-error)]">{errors.expense_date}</span>}</label><label className="text-[10px] text-[var(--garage-text-muted)]">Комментарий<input className="mt-1 h-8 w-full rounded border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue={expense.description ?? ''} name="description" />{errors.description && <span className="text-[var(--garage-error)]">{errors.description}</span>}</label><div className="flex items-center gap-3 sm:col-span-2"><button className="h-8 rounded-md bg-[var(--garage-accent)] px-3 text-[10px] font-semibold text-[var(--garage-background)]" disabled={processing} type="submit">Сохранить</button><span className="text-[10px] text-[var(--garage-text-muted)]">{processing ? 'Сохранение...' : ''}</span></div></>}
                            </Form>
                            <Form action={`/finance/expenses/${expense.id}?period=${period}&from=${from}&to=${to}`} method="post" className="mt-2"><input name="_method" type="hidden" value="delete" /><button className="text-[10px] text-[var(--garage-error)]" type="submit">Архивировать расход</button></Form>
                        </details>)}</div> : <EmptyState text="За выбранный период расходов нет." />}
                        {expenses.links.length > 3 && <nav aria-label="Страницы расходов" className="flex flex-wrap gap-1 border-t border-[var(--garage-border-subtle)] px-4 py-3">{expenses.links.map((link) => link.url ? <Link className={`rounded border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</nav>}
                    </article>
                </div>

                <aside className="space-y-4">
                    <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                        <div className="flex items-center gap-2"><Plus className="text-[var(--garage-accent)]" size={16} /><h2 className="text-sm font-semibold">Записать расход</h2></div>
                        <Form action={`/finance/expenses?period=${period}`} method="post" className="mt-4 space-y-3">
                            {({ processing, errors }) => <>
                                <label className="block text-xs font-medium">Категория<select className="mt-1.5 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 text-xs text-[var(--garage-text)]" defaultValue="" name="category_id"><option disabled value="">Выберите категорию</option>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select>{errors.category_id && <span className="mt-1 block text-[10px] text-[var(--garage-error)]">{errors.category_id}</span>}</label>
                                <label className="block text-xs font-medium">Сумма, ₸<input className="mt-1.5 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 text-xs" min="1" name="amount" type="number" />{errors.amount && <span className="mt-1 block text-[10px] text-[var(--garage-error)]">{errors.amount}</span>}</label>
                                <label className="block text-xs font-medium">Дата<input className="mt-1.5 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 text-xs" defaultValue={todayInput()} name="expense_date" type="date" />{errors.expense_date && <span className="mt-1 block text-[10px] text-[var(--garage-error)]">{errors.expense_date}</span>}</label>
                                <label className="block text-xs font-medium">Комментарий<input className="mt-1.5 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 text-xs" name="description" placeholder="Например, масло для сервиса" />{errors.description && <span className="mt-1 block text-[10px] text-[var(--garage-error)]">{errors.description}</span>}</label>
                                <button className="h-9 w-full rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50" disabled={processing || !categories.length} type="submit">{processing ? 'Сохраняем...' : 'Сохранить расход'}</button>
                            </>}
                        </Form>
                    </section>

                    <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                        <div className="flex items-center gap-2"><Plus className="text-[var(--garage-text-muted)]" size={15} /><h2 className="text-sm font-semibold">Категории расходов</h2></div>
                        <div className="mt-3 space-y-2">{categories.map((category) => <details key={category.id}><summary className="cursor-pointer text-[10px] text-[var(--garage-text-secondary)]">{category.name}</summary><Form action={`/finance/categories/${category.id}`} method="put" className="mt-1 flex gap-2">{({ processing, errors }) => <><input className="h-8 min-w-0 flex-1 rounded border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue={category.name} name="name" /><button className="h-8 rounded border border-[var(--garage-border)] px-2 text-[10px]" disabled={processing} type="submit">Переименовать</button>{errors.name && <span className="text-[10px] text-[var(--garage-error)]">{errors.name}</span>}</>}</Form></details>)}</div>
                        <Form action="/finance/categories" method="post" className="mt-3 flex gap-2">
                            {({ processing, errors }) => <><div className="min-w-0 flex-1"><input className="h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 text-xs" name="name" placeholder="Новая категория" />{errors.name && <span className="mt-1 block text-[10px] text-[var(--garage-error)]">{errors.name}</span>}</div><button aria-label="Добавить категорию" className="grid size-9 shrink-0 place-items-center rounded-md border border-[var(--garage-border)] text-[var(--garage-text-secondary)] hover:border-[var(--garage-accent)]" disabled={processing} title="Добавить категорию" type="submit"><Plus size={14} /></button></>}
                        </Form>
                        <div className="mt-3 flex flex-wrap gap-2">{categories.map((category) => <Form action={`/finance/categories/${category.id}/archive`} method="post" key={`archive-${category.id}`}><input name="_method" type="hidden" value="delete" /><button className="text-[9px] text-[var(--garage-text-muted)] hover:text-[var(--garage-error)]" type="submit">Архивировать {category.name}</button></Form>)}</div>
                    </section>
                </aside>
            </section>
        </AppLayout>
    );
}

function MetricCard({ label, value, caption, icon: Icon, change, warning }: { label: string; value: string; caption?: string; icon: LucideIcon; change?: number | null; warning?: boolean }) {
    const TrendIcon = change !== undefined && change !== null && change < 0 ? ArrowDownRight : ArrowUpRight;
    return <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><div className="flex items-center justify-between"><p className="text-xs text-[var(--garage-text-secondary)]">{label}</p><Icon className={warning ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-text-muted)]'} size={15} /></div><p className={`mt-3 truncate text-2xl font-semibold tabular-nums ${warning ? 'text-[var(--garage-warning)]' : ''}`}>{value}</p><p className="mt-1.5 flex items-center gap-1 text-[10px] text-[var(--garage-text-muted)]">{change !== undefined && change !== null && <TrendIcon className={change > 0 ? 'text-[var(--garage-success)]' : change < 0 ? 'text-[var(--garage-error)]' : 'text-[var(--garage-text-muted)]'} size={12} />}{caption || (change === undefined ? 'На текущий момент' : change === null ? 'Нет данных для сравнения' : `${change > 0 ? '+' : ''}${change}% к предыдущему периоду`)}</p></article>;
}

function EmptyState({ text }: { text: string }) {
    return <p className="px-4 py-6 text-center text-xs text-[var(--garage-text-muted)]">{text}</p>;
}

function changeCaption(change: number | null) {
    return change === null ? '' : `${change > 0 ? '+' : ''}${change}% к предыдущему периоду`;
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short' }).format(new Date(`${value}T12:00:00`));
}

function todayInput() {
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
}
