import { Form, Link } from '@inertiajs/react';
import { Archive, Clock3, Plus, RotateCcw, Search, Wrench, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

type Service = {
    id: number;
    name: string;
    description?: string;
    price: number;
    duration_minutes: number;
    category_id?: number | null;
    category?: { name: string } | null;
};
type Category = { id: number; name: string };
export default function Index({
    services,
    categories,
    archived,
}: {
    services: Service[];
    categories: Category[];
    archived: boolean;
}) {
    const [query, setQuery] = useState('');
    const [categoryFilter, setCategoryFilter] = useState<number | 'all'>('all');
    const [createOpen, setCreateOpen] = useState(false);
    const visibleServices = useMemo(() => {
        const normalizedQuery = query.trim().toLocaleLowerCase('ru');
        return services.filter((service) => {
            const matchesCategory = categoryFilter === 'all' || service.category_id === categoryFilter;
            const matchesQuery = !normalizedQuery || [service.name, service.description ?? '', service.category?.name ?? '']
                .some((value) => value.toLocaleLowerCase('ru').includes(normalizedQuery));
            return matchesCategory && matchesQuery;
        });
    }, [services, query, categoryFilter]);
    const averagePrice = services.length
        ? Math.round(services.reduce((total, service) => total + service.price, 0) / services.length)
        : 0;

    return (
        <AppLayout>
            <div className="flex items-end justify-between gap-4">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Управление
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold">Услуги</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                        Каталог работ и базовые цены сервиса.
                    </p>
                </div>
                <div className="flex gap-2">
                    <Link
                        className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]"
                        href={archived ? '/services' : '/services?archived=1'}
                    >
                        {archived ? 'Активные услуги' : 'Архив'}
                    </Link>
                    {!archived && (
                        <button
                            aria-expanded={createOpen}
                            className="inline-flex h-9 items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                            onClick={() => setCreateOpen((open) => !open)}
                            type="button"
                        >
                            <Plus size={15} />
                            {createOpen ? 'Скрыть форму' : 'Новая услуга'}
                        </button>
                    )}
                </div>
            </div>
            {!archived && (
                <section aria-label="Сводка каталога услуг" className="mt-5 grid gap-3 sm:grid-cols-3">
                    <SummaryCard label="Активные услуги" value={services.length.toLocaleString('ru-RU')} detail="доступны при записи" />
                    <SummaryCard label="Категории" value={new Set(services.map((service) => service.category_id).filter(Boolean)).size.toLocaleString('ru-RU')} detail="используются в каталоге" />
                    <SummaryCard label="Средняя цена" value={formatMoney(averagePrice)} detail="по активным услугам" />
                </section>
            )}
            <div className="mt-5 space-y-4">
                {createOpen && !archived && (
                    <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4" id="new-service">
                        <div className="flex items-center justify-between gap-3"><div><h2 className="text-sm font-semibold">Новая услуга</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Добавьте позицию в каталог работ.</p></div><button aria-label="Закрыть форму новой услуги" className="grid size-8 place-items-center rounded-md text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)]" onClick={() => setCreateOpen(false)} type="button"><X size={15} /></button></div>
                        <Form action="/services" method="post" className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            {({ processing, errors }) => <>
                                <Field label="Название" name="name" error={errors.name} />
                                <label className="block text-xs font-medium">Категория<select className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)]" defaultValue="" name="category_id"><option value="">Без категории</option>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select>{errors.category_id && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{errors.category_id}</span>}</label>
                                <Field label="Цена, ₸" name="price" type="number" error={errors.price} />
                                <Field label="Длительность, минут" name="duration_minutes" type="number" defaultValue="60" error={errors.duration_minutes} />
                                <label className="block text-xs font-medium sm:col-span-2 xl:col-span-3">Описание<textarea className="mt-1.5 min-h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 py-2 text-sm text-[var(--garage-text)]" name="description" />{errors.description && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{errors.description}</span>}</label>
                                <button className="h-10 self-end rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50" disabled={processing} type="submit">{processing ? 'Сохраняем...' : 'Добавить услугу'}</button>
                            </>}
                        </Form>
                    </section>
                )}
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    {!archived && <div className="space-y-3 border-b border-[var(--garage-border-subtle)] p-4">
                        <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div><h2 className="text-sm font-semibold">Каталог услуг</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">{visibleServices.length} из {services.length} позиций</p></div>
                            <label className="flex h-9 w-full items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)] sm:max-w-72"><Search size={14} /><input aria-label="Поиск по услугам" className="min-w-0 flex-1 bg-transparent text-xs text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]" onChange={(event) => setQuery(event.target.value)} placeholder="Название, описание, категория" value={query} /></label>
                        </div>
                        {categories.length > 0 && <div aria-label="Фильтр по категории" className="flex gap-1 overflow-x-auto">
                            {[{ id: 'all' as const, name: 'Все', count: services.length }, ...categories.map((category) => ({ id: category.id, name: category.name, count: services.filter((service) => service.category_id === category.id).length }))].map((category) => <button aria-pressed={categoryFilter === category.id} className={`inline-flex shrink-0 items-center gap-2 rounded-md px-3 py-1.5 text-xs transition-colors ${categoryFilter === category.id ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]'}`} key={category.id} onClick={() => setCategoryFilter(category.id)} type="button">{category.name}<span className="text-[10px] text-[var(--garage-text-muted)]">{category.count}</span></button>)}
                        </div>}
                    </div>}
                    {visibleServices.length ? (
                        <div className="divide-y divide-[var(--garage-border-subtle)]">
                            {visibleServices.map((service) => (
                                <details
                                    className="group px-4 py-4"
                                    key={service.id}
                                >
                                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <span className="grid size-9 shrink-0 place-items-center rounded-md bg-[var(--garage-surface-elevated)] text-[var(--garage-accent)]">
                                                <Wrench size={16} />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {service.name}
                                                </p>
                                                <p className="mt-1 truncate text-xs text-[var(--garage-text-muted)]">
                                                    <Clock3
                                                        className="mr-1 inline"
                                                        size={12}
                                                    />
                                                    {service.duration_minutes}{' '}
                                                    мин.
                                                    {service.category?.name
                                                        ? ` · ${service.category.name}`
                                                        : ''}
                                                </p>
                                                {service.description && <p className="mt-1 max-w-2xl truncate text-[11px] text-[var(--garage-text-secondary)]">{service.description}</p>}
                                            </div>
                                        </div>
                                        <div className="shrink-0 text-right"><p className="text-sm font-semibold tabular-nums">{formatMoney(service.price)}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">за услугу</p></div>
                                    </summary>
                                    {archived ? (
                                        <Form
                                            action={`/services/${service.id}/restore`}
                                            className="mt-3 border-t border-[var(--garage-border-subtle)] pt-3"
                                            method="post"
                                        >
                                            <button
                                                className="inline-flex h-8 items-center gap-2 text-xs text-[var(--garage-success)]"
                                                type="submit"
                                            >
                                                <RotateCcw size={13} />
                                                Восстановить услугу
                                            </button>
                                        </Form>
                                    ) : (
                                        <>
                                            <Form
                                                action={`/services/${service.id}`}
                                                className="mt-3 grid gap-3 border-t border-[var(--garage-border-subtle)] pt-3 sm:grid-cols-2"
                                                method="put"
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <Field
                                                            label="Название"
                                                            name="name"
                                                            defaultValue={
                                                                service.name
                                                            }
                                                            error={errors.name}
                                                        />
                                                        <Field
                                                            label="Цена, ₸"
                                                            name="price"
                                                            type="number"
                                                            defaultValue={String(
                                                                service.price,
                                                            )}
                                                            error={errors.price}
                                                        />
                                                        <Field
                                                            label="Длительность, минут"
                                                            name="duration_minutes"
                                                            type="number"
                                                            defaultValue={String(
                                                                service.duration_minutes,
                                                            )}
                                                            error={
                                                                errors.duration_minutes
                                                            }
                                                        />
                                                        <label className="block text-xs font-medium">
                                                            Категория
                                                            <select
                                                                className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)]"
                                                                defaultValue={
                                                                    service.category_id ??
                                                                    ''
                                                                }
                                                                name="category_id"
                                                            >
                                                                <option value="">
                                                                    Без
                                                                    категории
                                                                </option>
                                                                {categories.map(
                                                                    (
                                                                        category,
                                                                    ) => (
                                                                        <option
                                                                            key={
                                                                                category.id
                                                                            }
                                                                            value={
                                                                                category.id
                                                                            }
                                                                        >
                                                                            {
                                                                                category.name
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                            {errors.category_id && (
                                                                <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                                                                    {
                                                                        errors.category_id
                                                                    }
                                                                </span>
                                                            )}
                                                        </label>
                                                        <label className="block text-xs font-medium sm:col-span-2">
                                                            Описание
                                                            <textarea
                                                                className="mt-1.5 min-h-20 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 py-2 text-sm text-[var(--garage-text)]"
                                                                defaultValue={
                                                                    service.description ??
                                                                    ''
                                                                }
                                                                name="description"
                                                            />
                                                            {errors.description && (
                                                                <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                                                                    {
                                                                        errors.description
                                                                    }
                                                                </span>
                                                            )}
                                                        </label>
                                                        <button
                                                            className="h-9 rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50"
                                                            disabled={
                                                                processing
                                                            }
                                                            type="submit"
                                                        >
                                                            Сохранить
                                                        </button>
                                                    </>
                                                )}
                                            </Form>
                                            <Form
                                                action={`/services/${service.id}`}
                                                className="mt-2 border-t border-[var(--garage-border-subtle)] pt-2"
                                                method="delete"
                                                onSubmit={(event) => {
                                                    if (
                                                        !window.confirm(
                                                            `Архивировать услугу «${service.name}»? Исторические заказы не изменятся.`,
                                                        )
                                                    )
                                                        event.preventDefault();
                                                }}
                                            >
                                                <button
                                                    className="inline-flex h-8 items-center gap-2 text-xs text-[var(--garage-error)]"
                                                    type="submit"
                                                >
                                                    <Archive size={13} />
                                                    Архивировать
                                                </button>
                                            </Form>
                                        </>
                                    )}
                                </details>
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-56 place-items-center p-8 text-center">
                            <div>
                                <Wrench
                                    className="mx-auto text-[var(--garage-text-muted)]"
                                    size={24}
                                />
                                <p className="mt-3 text-sm font-medium">
                                    {query || categoryFilter !== 'all' ? 'Услуги не найдены' : 'Услуг пока нет'}
                                </p>
                                <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                    {query || categoryFilter !== 'all' ? 'Измените запрос или выберите другую категорию.' : 'Добавьте первую услугу, чтобы принимать записи.'}
                                </p>
                            </div>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value, detail }: { label: string; value: string; detail: string }) {
    return (
        <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
            <p className="text-xs text-[var(--garage-text-secondary)]">{label}</p>
            <p className="mt-3 text-2xl font-semibold tabular-nums">{value}</p>
            <p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">{detail}</p>
        </article>
    );
}

function formatMoney(value: number) {
    return `₸${value.toLocaleString('ru-RU')}`;
}

function Field({
    label,
    name,
    type = 'text',
    defaultValue,
    error,
}: {
    label: string;
    name: string;
    type?: string;
    defaultValue?: string;
    error?: string;
}) {
    return (
        <label className="block text-xs font-medium">
            {label}
            <input
                className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm outline-none focus:border-[var(--garage-accent)]"
                defaultValue={defaultValue}
                name={name}
                type={type}
            />
            {error && (
                <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                    {error}
                </span>
            )}
        </label>
    );
}
