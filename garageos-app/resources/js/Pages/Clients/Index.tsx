import { Form, Link } from '@inertiajs/react';
import { CarFront, CalendarPlus, MessageCircle, Phone, Plus, Search, Users } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type Client = {
    id: number;
    name: string;
    phone: string;
    email?: string;
    cars_count: number;
    completed_visits_count: number;
    last_visit_at?: string | null;
    needs_follow_up: boolean;
    created_at: string;
};
type Props = {
    clients: {
        data: Client[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: { q: string; segment: 'all' | 'follow_up' };
    summary: { clients: number; servicedClients: number; repeatClients: number; followUp: number };
    selectedClientId: number | null;
};

export default function Index({ clients, filters, summary, selectedClientId }: Props) {
    const repeatRate = summary.servicedClients > 0 ? Math.round((summary.repeatClients / summary.servicedClients) * 100) : 0;

    return (
        <AppLayout>
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Рабочее пространство
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Клиенты
                    </h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                        Клиентская база и история обслуживания.
                    </p>
                </div>
                <a
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-[var(--garage-accent)] px-3.5 text-xs font-semibold text-[var(--garage-background)]"
                    href="#new-client"
                >
                    <Plus size={15} />
                    Добавить клиента
                </a>
            </div>
            <section aria-label="Аналитика клиентской базы" className="mt-5 grid gap-3 sm:grid-cols-3">
                <SummaryCard label="Клиентская база" value={summary.clients.toLocaleString('ru-RU')} detail="всего клиентов" />
                <SummaryCard label="Возвращаются" value={summary.repeatClients.toLocaleString('ru-RU')} detail={`${repeatRate}% клиентов с завершённым визитом`} />
                <SummaryCard label="Пора связаться" value={summary.followUp.toLocaleString('ru-RU')} detail="без визитов 90+ дней" warning={summary.followUp > 0} />
            </section>
            <div className="mt-6 grid gap-4 lg:grid-cols-[1fr_320px]">
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="flex flex-col justify-between gap-3 border-b border-[var(--garage-border-subtle)] px-4 py-3 sm:flex-row sm:items-center">
                        <form
                            action="/clients"
                            className="flex gap-2"
                            method="get"
                        >
                            <input name="segment" type="hidden" value={filters.segment} />
                            <label className="flex h-9 min-w-0 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)] sm:w-72">
                                <Search size={15} />
                                <input
                                    aria-label="Поиск клиентов"
                                    className="min-w-0 flex-1 bg-transparent text-xs text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]"
                                    defaultValue={filters.q}
                                    name="q"
                                    placeholder="Имя, телефон, email, авто или VIN"
                                />
                            </label>
                            <button
                                className="h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]"
                                type="submit"
                            >
                                Найти
                            </button>
                        </form>
                        <span className="text-xs text-[var(--garage-text-muted)]">
                            {clients.total} клиентов
                        </span>
                    </div>
                    <nav aria-label="Сегменты клиентов" className="flex gap-1 overflow-x-auto border-b border-[var(--garage-border-subtle)] px-4 py-2">
                        <Link aria-current={filters.segment === 'all' ? 'page' : undefined} className={`inline-flex shrink-0 items-center gap-2 rounded-md px-3 py-1.5 text-xs ${filters.segment === 'all' ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)]'}`} href={clientListHref(filters.q, 'all')}>Все клиенты<span className="text-[10px] text-[var(--garage-text-muted)]">{summary.clients}</span></Link>
                        <Link aria-current={filters.segment === 'follow_up' ? 'page' : undefined} className={`inline-flex shrink-0 items-center gap-2 rounded-md px-3 py-1.5 text-xs ${filters.segment === 'follow_up' ? 'bg-[var(--garage-warning)]/10 text-[var(--garage-warning)]' : 'text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)]'}`} href={clientListHref(filters.q, 'follow_up')}>Пора связаться<span className="text-[10px]">{summary.followUp}</span></Link>
                    </nav>
                    {clients.data.length ? (
                        <div className="divide-y divide-[var(--garage-border-subtle)]">
                            {clients.data.map((client) => (
                                <article className="flex flex-col gap-3 px-4 py-3.5 transition-colors hover:bg-[var(--garage-surface-secondary)] sm:flex-row sm:items-center" key={client.id}>
                                    <Link className="flex min-w-0 flex-1 items-center gap-3" href={`/clients/${client.id}`}>
                                        <span className="grid size-9 shrink-0 place-items-center rounded-full bg-[var(--garage-surface-elevated)] text-xs font-semibold text-[var(--garage-accent)]">{client.name.slice(0, 2).toUpperCase()}</span>
                                        <span className="min-w-0 flex-1">
                                            <span className="flex flex-wrap items-center gap-2"><span className="truncate text-sm font-medium">{client.name}</span>{client.needs_follow_up && <span className="rounded bg-[var(--garage-warning)]/10 px-1.5 py-0.5 text-[9px] font-medium text-[var(--garage-warning)]">Пора связаться</span>}</span>
                                            <span className="mt-1 block truncate text-xs text-[var(--garage-text-muted)]">{client.phone}{client.email ? ` · ${client.email}` : ''}</span>
                                            <span className="mt-1 block text-[10px] text-[var(--garage-text-secondary)]">{client.completed_visits_count} визитов · последний: {formatLastVisit(client.last_visit_at)}</span>
                                        </span>
                                    </Link>
                                    <div className="flex items-center justify-between gap-3 sm:justify-end">
                                        <span className="flex shrink-0 items-center gap-1.5 text-xs text-[var(--garage-text-secondary)]"><CarFront size={14} />{client.cars_count}</span>
                                        <div className="flex items-center gap-1">
                                            <a aria-label={`Позвонить ${client.name}`} className="grid size-8 place-items-center rounded-md border border-[var(--garage-border)] text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]" href={`tel:${client.phone}`} title="Позвонить"><Phone size={14} /></a>
                                            {whatsappHref(client.phone, client.name) && <a aria-label={`Написать ${client.name} в WhatsApp`} className="grid size-8 place-items-center rounded-md border border-[var(--garage-border)] text-[var(--garage-text-secondary)] hover:text-[var(--garage-success)]" href={whatsappHref(client.phone, client.name) ?? undefined} rel="noreferrer" target="_blank" title="WhatsApp"><MessageCircle size={14} /></a>}
                                            <Link aria-label={`Создать запись для ${client.name}`} className="grid size-8 place-items-center rounded-md bg-[var(--garage-accent)] text-[var(--garage-background)]" href={`/appointments?client_id=${client.id}`} title="Новая запись"><CalendarPlus size={14} /></Link>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-56 place-items-center p-8 text-center">
                            <div>
                                <Users
                                    className="mx-auto text-[var(--garage-text-muted)]"
                                    size={24}
                                />
                                <p className="mt-3 text-sm font-medium">
                                    Клиентов пока нет
                                </p>
                                <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                    {filters.q || filters.segment === 'follow_up'
                                        ? 'Измените запрос или добавьте нового клиента.'
                                        : 'Добавьте первого клиента, чтобы начать историю обслуживания.'}
                                </p>
                            </div>
                        </div>
                    )}
                    {clients.links.length > 3 && (
                        <nav
                            aria-label="Страницы клиентов"
                            className="flex flex-wrap gap-1 border-t border-[var(--garage-border-subtle)] px-4 py-3"
                        >
                            {clients.links.map((link) =>
                                link.url ? (
                                    <Link
                                        className={`rounded border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`}
                                        href={link.url}
                                        key={link.label}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : null,
                            )}
                        </nav>
                    )}
                </section>
                <div className="space-y-4">
                    <section
                        className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"
                        id="new-client"
                    >
                        <div className="flex items-center gap-2">
                            <Plus
                                className="text-[var(--garage-accent)]"
                                size={16}
                            />
                            <h2 className="text-sm font-semibold">
                                Новый клиент
                            </h2>
                        </div>
                        <Form
                            action="/clients"
                            method="post"
                            className="mt-5 space-y-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Field
                                        label="Имя"
                                        name="name"
                                        error={errors.name}
                                    />
                                    <Field
                                        label="Телефон"
                                        name="phone"
                                        error={errors.phone}
                                    />
                                    <Field
                                        label="Email"
                                        name="email"
                                        type="email"
                                        error={errors.email}
                                    />
                                    <button
                                        className="mt-2 h-9 w-full rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50"
                                        disabled={processing}
                                        type="submit"
                                    >
                                        {processing
                                            ? 'Сохраняем...'
                                            : 'Сохранить клиента'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </section>
                    <section
                        className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"
                        id="new-car"
                    >
                        <div className="flex items-center gap-2">
                            <CarFront
                                className="text-[var(--garage-accent)]"
                                size={16}
                            />
                            <h2 className="text-sm font-semibold">
                                Новый автомобиль
                            </h2>
                        </div>
                        <Form
                            action="/cars"
                            method="post"
                            className="mt-5 space-y-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <label className="block text-xs font-medium">
                                        Владелец
                                        <select
                                            className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none focus:border-[var(--garage-accent)]"
                                            name="client_id"
                                            defaultValue={
                                                selectedClientId ?? ''
                                            }
                                        >
                                            <option
                                                className="bg-[var(--garage-surface)]"
                                                disabled
                                                value=""
                                            >
                                                Выберите клиента
                                            </option>
                                            {clients.data.map((client) => (
                                                <option
                                                    className="bg-[var(--garage-surface)]"
                                                    key={client.id}
                                                    value={client.id}
                                                >
                                                    {client.name} ·{' '}
                                                    {client.phone}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.client_id && (
                                            <span className="mt-1 block text-[11px] font-normal text-[var(--garage-error)]">
                                                {errors.client_id}
                                            </span>
                                        )}
                                    </label>
                                    <Field
                                        label="Марка"
                                        name="make"
                                        error={errors.make}
                                    />
                                    <Field
                                        label="Модель"
                                        name="model"
                                        error={errors.model}
                                    />
                                    <Field
                                        label="Госномер"
                                        name="plate_number"
                                        error={errors.plate_number}
                                    />
                                    <Field
                                        label="VIN"
                                        name="vin"
                                        error={errors.vin}
                                    />
                                    <button
                                        className="mt-2 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] text-xs font-semibold text-[var(--garage-text)] disabled:opacity-50"
                                        disabled={processing}
                                        type="submit"
                                    >
                                        {processing
                                            ? 'Сохраняем...'
                                            : 'Добавить автомобиль'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value, detail, warning }: { label: string; value: string; detail: string; warning?: boolean }) {
    return <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><p className="text-xs text-[var(--garage-text-secondary)]">{label}</p><p className={`mt-3 text-2xl font-semibold tabular-nums ${warning ? 'text-[var(--garage-warning)]' : ''}`}>{value}</p><p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">{detail}</p></article>;
}

function clientListHref(query: string, segment: 'all' | 'follow_up') {
    const params = new URLSearchParams();
    if (query) params.set('q', query);
    if (segment !== 'all') params.set('segment', segment);
    return `/clients${params.size ? `?${params.toString()}` : ''}`;
}

function formatLastVisit(value?: string | null) {
    if (!value) return 'не обслуживался';
    return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value));
}

function whatsappHref(phone: string, name: string) {
    const digits = phone.replace(/\D/g, '');
    if (digits.length < 10) return null;
    const message = encodeURIComponent(`Здравствуйте, ${name}! Подскажите, актуально ли записать автомобиль на обслуживание?`);
    return `https://wa.me/${digits}?text=${message}`;
}

function Field({
    label,
    name,
    type = 'text',
    error,
}: {
    label: string;
    name: string;
    type?: string;
    error?: string;
}) {
    return (
        <label className="block text-xs font-medium">
            {label}
            <input
                className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm outline-none focus:border-[var(--garage-accent)]"
                name={name}
                type={type}
            />
            {error && (
                <span className="mt-1 block text-[11px] font-normal text-[var(--garage-error)]">
                    {error}
                </span>
            )}
        </label>
    );
}
