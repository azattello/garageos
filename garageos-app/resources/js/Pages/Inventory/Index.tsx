import { Form, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowDownLeft, ArrowUpRight, Boxes, ChevronDown, ChevronLeft, ChevronRight, ChevronUp, Package, Plus, Search, SlidersHorizontal, X, type LucideIcon } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

type Part = { id: number; name: string; sku?: string | null; supplier?: string | null; cost_price: number; sale_price: number; stock: string | number; reserved_stock: string | number; available_stock: string | number; is_low_stock: boolean; min_stock: string | number; unit: string; active: boolean };
type Transaction = { id: number; type: string; quantity: number; part: string; unit: string; user?: string | null; note?: string | null; created_at: string };
type Props = {
    parts: { data: Part[]; total: number; current_page: number; last_page: number; per_page: number; from: number | null; to: number | null };
    filters: { q: string; lowStock: boolean; sort: string; direction: 'asc' | 'desc'; perPage: number };
    summary: { parts: number; lowStock: number; stockValue: number };
    transactions: Transaction[];
};
const money = (value: number) => `₸${value.toLocaleString('ru-RU')}`;
const transactionLabels: Record<string, string> = { receive: 'Приход', reserve: 'Резерв', release: 'Снятие резерва', issue: 'Списание', return: 'Возврат', adjustment: 'Корректировка' };

export default function Index({ parts, filters, summary, transactions }: Props) {
    const [newPartOpen, setNewPartOpen] = useState(false);

    return (
        <AppLayout>
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Запасы сервиса</p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">Склад</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Остатки запчастей, себестоимость и движения по складу.</p>
                </div>
                <button aria-expanded={newPartOpen} className="inline-flex h-9 items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" onClick={() => setNewPartOpen((open) => !open)} type="button"><Plus size={14} />{newPartOpen ? 'Скрыть форму' : 'Новая запчасть'}</button>
            </div>

            <section className="mt-6 grid gap-3 sm:grid-cols-3">
                <SummaryCard label="Номенклатура" value={String(summary.parts)} detail="активных позиций" icon={Boxes} />
                <SummaryCard label="Низкий остаток" value={String(summary.lowStock)} detail="нужно пополнить" icon={AlertTriangle} warning={summary.lowStock > 0} />
                <SummaryCard label="Стоимость запаса" value={money(summary.stockValue)} detail="по закупочной цене" icon={Package} />
            </section>

            <div className="mt-5 space-y-4">
                <section className="min-w-0 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="flex flex-col gap-3 border-b border-[var(--garage-border-subtle)] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div><h2 className="text-sm font-semibold">Запчасти и расходники</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">{parts.total} позиций · поиск по всему складу</p></div>
                        <Form action="/inventory" method="get" className="flex flex-wrap items-center gap-2">
                            <input name="sort" type="hidden" value={filters.sort} />
                            <input name="direction" type="hidden" value={filters.direction} />
                            <label className="flex h-9 min-w-0 flex-1 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)] sm:w-56 sm:flex-none"><Search size={14} /><input aria-label="Поиск по названию, SKU или поставщику" className="min-w-0 flex-1 bg-transparent text-xs text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]" defaultValue={filters.q} name="q" placeholder="Название, SKU, поставщик" /></label>
                            <label className={`inline-flex h-9 cursor-pointer items-center gap-2 rounded-md border px-2.5 text-[10px] ${filters.lowStock ? 'border-[var(--garage-warning)] text-[var(--garage-warning)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`}><input className="accent-[var(--garage-warning)]" defaultChecked={filters.lowStock} name="low_stock" type="checkbox" value="1" /><SlidersHorizontal size={13} />Низкий остаток</label>
                            <label className="sr-only" htmlFor="inventory-page-size">Строк на странице</label>
                            <select aria-label="Строк на странице" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-[10px] text-[var(--garage-text-secondary)]" defaultValue={filters.perPage} id="inventory-page-size" name="per_page"><option value="25">25 строк</option><option value="50">50 строк</option><option value="100">100 строк</option></select>
                            <button className="inline-flex h-9 items-center gap-1.5 rounded-md bg-[var(--garage-surface-elevated)] px-3 text-[10px] font-medium text-[var(--garage-text)]" type="submit"><Search size={13} />Найти</button>
                        </Form>
                    </div>

                    {parts.data.length ? <>
                        <div className="hidden overflow-hidden md:block">
                        <table className="w-full table-fixed border-collapse text-left">
                            <colgroup><col className="w-[32%]" /><col className="w-[13%]" /><col className="w-[14%]" /><col className="w-[11%]" /><col className="w-[12%]" /><col className="w-[12%]" /><col className="w-[6%]" /></colgroup>
                            <thead className="sticky top-0 z-10 bg-[var(--garage-surface-secondary)] text-[10px] font-medium uppercase text-[var(--garage-text-muted)]">
                                <tr className="border-b border-[var(--garage-border-subtle)]">
                                    <SortableHeader label="Позиция" field="name" filters={filters} />
                                    <SortableHeader label="Доступно" field="available_stock" filters={filters} />
                                    <th className="px-3 py-3">Всего / резерв</th>
                                    <SortableHeader label="Минимум" field="min_stock" filters={filters} />
                                    <SortableHeader label="Закупка" field="cost_price" filters={filters} />
                                    <SortableHeader label="Продажа" field="sale_price" filters={filters} />
                                    <th className="px-3 py-3"><span className="sr-only">Действия</span></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-[var(--garage-border-subtle)]">
                        {parts.data.map((part) => {
                            const lowStock = part.is_low_stock;
                            return <tr className="group h-14 transition-colors hover:bg-[var(--garage-surface-secondary)]/70" key={part.id}>
                                <td className="px-3 py-2"><div className="flex min-w-0 items-center gap-2.5"><span className={`grid size-8 shrink-0 place-items-center rounded-md ${lowStock ? 'bg-[rgba(245,196,81,0.1)] text-[var(--garage-warning)]' : 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text-muted)]'}`}><Package size={14} /></span><div className="min-w-0"><p className="truncate text-xs font-semibold text-[var(--garage-text)]">{part.name}</p><p className="mt-0.5 truncate text-[10px] text-[var(--garage-text-muted)]">{part.sku || 'Без SKU'}{part.supplier ? ` · ${part.supplier}` : ''}</p></div></div></td>
                                <td className="px-3 py-2"><span className={`text-xs font-semibold tabular-nums ${lowStock ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-text)]'}`}>{formatQuantity(part.available_stock)} {part.unit}</span>{lowStock && <span className="mt-0.5 block text-[9px] text-[var(--garage-warning)]">Пополнить</span>}</td>
                                <td className="px-3 py-3 text-[10px] tabular-nums text-[var(--garage-text-secondary)]">{formatQuantity(part.stock)} <span className="text-[var(--garage-text-muted)]">/ {formatQuantity(part.reserved_stock)}</span></td>
                                <td className="px-3 py-3 text-xs tabular-nums text-[var(--garage-text-secondary)]">{formatQuantity(part.min_stock)} {part.unit}</td>
                                <td className="px-3 py-3 text-xs tabular-nums text-[var(--garage-text-secondary)]">{money(part.cost_price)}</td>
                                <td className="px-3 py-3 text-xs font-medium tabular-nums text-[var(--garage-text)]">{money(part.sale_price)}</td>
                                <td className="px-3 py-2 text-right"><StockAction part={part} /></td>
                            </tr>;
                        })}
                            </tbody>
                        </table>
                        </div>
                        <div className="divide-y divide-[var(--garage-border-subtle)] md:hidden">
                            {parts.data.map((part) => <article className="flex items-center justify-between gap-3 px-3 py-3" key={part.id}>
                                <div className="min-w-0 flex-1">
                                    <div className="flex min-w-0 items-center gap-2"><Package className={part.is_low_stock ? 'shrink-0 text-[var(--garage-warning)]' : 'shrink-0 text-[var(--garage-text-muted)]'} size={14} /><p className="truncate text-xs font-semibold">{part.name}</p></div>
                                    <p className="mt-1 truncate pl-[22px] text-[10px] text-[var(--garage-text-muted)]">{part.sku || 'Без SKU'}{part.supplier ? ` · ${part.supplier}` : ''}</p>
                                    <p className="mt-1 pl-[22px] text-[10px] text-[var(--garage-text-secondary)]"><strong className={part.is_low_stock ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-text)]'}>{formatQuantity(part.available_stock)} {part.unit}</strong> доступно · {formatQuantity(part.stock)} всего · {formatQuantity(part.reserved_stock)} резерв</p>
                                    <p className="mt-0.5 pl-[22px] text-[10px] text-[var(--garage-text-muted)]">Мин. {formatQuantity(part.min_stock)} · закупка {money(part.cost_price)} · продажа {money(part.sale_price)}</p>
                                </div>
                                <StockAction part={part} />
                            </article>)}
                        </div>
                    </> : <div className="grid min-h-40 place-items-center p-6 text-center"><div><Package className="mx-auto text-[var(--garage-text-muted)]" size={22} /><p className="mt-3 text-sm font-medium">{filters.q || filters.lowStock ? 'Ничего не найдено' : 'Склад пока пуст'}</p><p className="mt-1 text-xs text-[var(--garage-text-muted)]">{filters.q || filters.lowStock ? 'Измените запрос или снимите фильтр низкого остатка.' : 'Добавьте первую позицию, чтобы вести остатки и списывать детали в заказ.'}</p></div></div>}

                    <footer className="flex flex-col gap-3 border-t border-[var(--garage-border-subtle)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-[10px] text-[var(--garage-text-muted)]">Показано {parts.from ?? 0}–{parts.to ?? 0} из {parts.total}</p>
                        <div className="flex items-center gap-2">
                            <Link aria-label="Предыдущая страница" className={`grid size-8 place-items-center rounded-md border border-[var(--garage-border)] ${parts.current_page <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-[var(--garage-surface-secondary)]'}`} href={inventoryHref(filters, parts.current_page - 1)}><ChevronLeft size={14} /></Link>
                            <span className="min-w-16 text-center text-[10px] tabular-nums text-[var(--garage-text-secondary)]">{parts.current_page} / {parts.last_page}</span>
                            <Link aria-label="Следующая страница" className={`grid size-8 place-items-center rounded-md border border-[var(--garage-border)] ${parts.current_page >= parts.last_page ? 'pointer-events-none opacity-40' : 'hover:bg-[var(--garage-surface-secondary)]'}`} href={inventoryHref(filters, parts.current_page + 1)}><ChevronRight size={14} /></Link>
                        </div>
                    </footer>
                </section>

                {newPartOpen && <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4" id="new-part">
                        <div className="flex items-center justify-between gap-2"><div className="flex items-center gap-2"><Plus className="text-[var(--garage-accent)]" size={16} /><h2 className="text-sm font-semibold">Новая запчасть</h2></div><button aria-label="Закрыть форму новой запчасти" className="grid size-8 place-items-center rounded-md text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)]" onClick={() => setNewPartOpen(false)} type="button"><X size={15} /></button></div>
                        <Form action="/inventory/parts" method="post" className="mt-4 space-y-3">
                            {({ processing, errors }) => <>
                                <Field label="Название" name="name" error={errors.name} />
                                <div className="grid grid-cols-2 gap-2"><Field label="SKU" name="sku" error={errors.sku} /><Field label="Ед. изм." name="unit" defaultValue="шт" error={errors.unit} /></div>
                                <Field label="Поставщик" name="supplier" error={errors.supplier} />
                                <div className="grid grid-cols-2 gap-2"><Field label="Закупочная цена" name="cost_price" type="number" min="0" error={errors.cost_price} /><Field label="Цена продажи" name="sale_price" type="number" min="0" error={errors.sale_price} /></div>
                                <div className="grid grid-cols-2 gap-2"><Field label="Начальный остаток" name="initial_stock" type="number" min="0" step="0.01" defaultValue="0" error={errors.initial_stock} /><Field label="Минимум" name="min_stock" type="number" min="0" step="0.01" defaultValue="0" error={errors.min_stock} /></div>
                                <button className="h-9 w-full rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50" disabled={processing} type="submit">{processing ? 'Сохраняем...' : 'Добавить на склад'}</button>
                            </>}
                        </Form>
                    </section>}

                    <details className="overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        <summary className="flex cursor-pointer list-none items-center justify-between px-4 py-3 [&::-webkit-details-marker]:hidden"><span><span className="block text-sm font-semibold">Последние движения</span><span className="mt-1 block text-[10px] text-[var(--garage-text-muted)]">Журнал последних операций по складу</span></span><ArrowDownLeft className="text-[var(--garage-text-muted)]" size={15} /></summary>
                        {transactions.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{transactions.map((transaction) => <div className="flex items-start justify-between gap-3 px-4 py-3" key={transaction.id}><div className="min-w-0"><p className="truncate text-xs font-medium">{transaction.part}</p><p className="mt-1 truncate text-[10px] text-[var(--garage-text-muted)]">{transactionLabels[transaction.type] || transaction.type}{transaction.user ? ` · ${transaction.user}` : ''} · {formatDate(transaction.created_at)}</p>{transaction.note && <p className="mt-1 truncate text-[10px] text-[var(--garage-text-muted)]">{transaction.note}</p>}</div><span className={`shrink-0 text-xs font-semibold tabular-nums ${transaction.quantity < 0 ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-success)]'}`}>{transaction.quantity > 0 ? '+' : ''}{formatQuantity(transaction.quantity)} {transaction.unit}</span></div>)}</div> : <p className="px-4 py-5 text-xs text-[var(--garage-text-muted)]">Операций пока нет.</p>}
                    </details>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value, detail, icon: Icon, warning }: { label: string; value: string; detail: string; icon: LucideIcon; warning?: boolean }) {
    return <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><div className="flex items-center justify-between"><p className="text-xs text-[var(--garage-text-secondary)]">{label}</p><Icon className={warning ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-text-muted)]'} size={15} /></div><p className={`mt-3 text-2xl font-semibold tabular-nums ${warning ? 'text-[var(--garage-warning)]' : ''}`}>{value}</p><p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">{detail}</p></article>;
}

type InventoryFilters = Props['filters'];
type SortField = 'name' | 'available_stock' | 'min_stock' | 'cost_price' | 'sale_price';

function SortableHeader({ label, field, filters }: { label: string; field: SortField; filters: InventoryFilters }) {
    const active = filters.sort === field;
    const direction = active && filters.direction === 'asc' ? 'desc' : 'asc';
    const Icon = active ? filters.direction === 'asc' ? ChevronUp : ChevronDown : null;

    return (
        <th className="px-3 py-3" scope="col">
            <Link aria-label={`Сортировать по полю «${label}» ${direction === 'asc' ? 'по возрастанию' : 'по убыванию'}`} className="inline-flex items-center gap-1.5 whitespace-nowrap transition-colors hover:text-[var(--garage-text)]" href={inventoryHref({ ...filters, sort: field, direction }, 1)}>
                {label}{Icon ? <Icon size={12} /> : <span className="size-3" />}
            </Link>
        </th>
    );
}

function inventoryHref(filters: InventoryFilters, page: number) {
    const params = new URLSearchParams({
        page: String(page),
        per_page: String(filters.perPage),
        sort: filters.sort,
        direction: filters.direction,
    });
    if (filters.q) params.set('q', filters.q);
    if (filters.lowStock) params.set('low_stock', '1');
    return `/inventory?${params.toString()}`;
}

function StockAction({ part }: { part: Part }) {
    return (
        <details className="group relative inline-block text-left">
            <summary aria-label={`Изменить остаток: ${part.name}`} className="grid size-8 cursor-pointer list-none place-items-center rounded-md border border-[var(--garage-border)] text-[var(--garage-text-secondary)] transition-colors hover:border-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)] [&::-webkit-details-marker]:hidden"><SlidersHorizontal size={13} /></summary>
            <Form action={`/inventory/parts/${part.id}/stock`} method="post" className="absolute right-0 top-9 z-30 w-[min(16rem,calc(100vw-2rem))] space-y-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-3 text-left shadow-xl">
                {({ processing, errors }) => <>
                    <select aria-label="Тип движения" className="h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue="receive" name="type"><option value="receive">Приход</option><option value="issue">Списание</option><option value="return">Возврат</option><option value="adjustment">Фактический остаток</option></select>
                    <input aria-label="Количество или фактический остаток" className="h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" min="0" name="quantity" placeholder="Количество" step="0.01" type="number" />
                    <input aria-label="Комментарий к движению" className="h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" name="note" placeholder="Комментарий, необязательно" />
                    {errors.quantity && <p className="text-[10px] text-[var(--garage-error)]">{errors.quantity}</p>}
                    <button className="h-8 w-full rounded-md bg-[var(--garage-accent)] text-[10px] font-semibold text-[var(--garage-background)] disabled:opacity-50" disabled={processing} type="submit">{processing ? 'Сохраняем...' : 'Записать движение'}</button>
                </>}
            </Form>
        </details>
    );
}

function Field({ label, name, type = 'text', defaultValue, min, step, error }: { label: string; name: string; type?: string; defaultValue?: string; min?: string; step?: string; error?: string }) {
    return <label className="block text-xs font-medium">{label}<input className="mt-1.5 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 text-xs outline-none focus:border-[var(--garage-accent)]" defaultValue={defaultValue} min={min} name={name} step={step} type={type} />{error && <span className="mt-1 block text-[10px] text-[var(--garage-error)]">{error}</span>}</label>;
}

function formatQuantity(value: number | string) {
    return Number(value).toLocaleString('ru-RU', { maximumFractionDigits: 2 });
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}
