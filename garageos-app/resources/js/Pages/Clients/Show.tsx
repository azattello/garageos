import { Form, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarPlus,
    CarFront,
    ClipboardList,
    CreditCard,
    MessageCircle,
    Plus,
    type LucideIcon,
    Phone,
    UserRound,
} from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type Car = {
    id: number;
    make: string;
    model: string;
    year?: number;
    plate_number?: string;
    vin?: string;
    mileage?: number;
    active: boolean;
};
type Order = {
    id: number;
    number: string;
    total: number;
    paid_total: number;
    balance: number;
    car?: Car;
    status?: { name: string };
};
type TimelineEvent = {
    id: string;
    type: string;
    title: string;
    detail: string;
    occurred_at: string;
    url: string;
    image?: string;
};
type Client = {
    id: number;
    name: string;
    phone: string;
    email?: string;
    comment?: string;
    portalAccess: boolean;
    cars: Car[];
    summary: {
        cars: number;
        orders: number;
        total: number;
        paid: number;
        balance: number;
        completedVisits: number;
        lastVisitAt?: string | null;
        needsFollowUp: boolean;
    };
};
type Props = {
    client: Client;
    timeline: {
        data: TimelineEvent[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;

export default function Show({ client, timeline }: Props) {
    const stats: Array<[string, string | number, LucideIcon]> = [
        ['Автомобили', client.summary.cars, CarFront],
        ['Заказы', client.summary.orders, ClipboardList],
        ['Сумма', money(client.summary.total), CreditCard],
        ['Оплачено', money(client.summary.paid), CreditCard],
        ['Долг', money(client.summary.balance), CreditCard],
    ];

    return (
        <AppLayout>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <Link
                        className="inline-flex items-center gap-2 text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]"
                        href="/clients"
                    >
                        <ArrowLeft size={14} />
                        Клиенты
                    </Link>
                    <div className="mt-4 flex items-center gap-3">
                        <span className="grid size-11 place-items-center rounded-full bg-[var(--garage-surface-elevated)] text-sm font-semibold text-[var(--garage-accent)]">
                            {client.name.slice(0, 2).toUpperCase()}
                        </span>
                        <div>
                            <p className="text-xs uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                                Карточка клиента
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {client.name}
                            </h1>
                        </div>
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    <a
                        className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs text-[var(--garage-text-secondary)]"
                        href={`tel:${client.phone}`}
                    >
                        <Phone size={14} />
                        Позвонить
                    </a>
                    <Link
                        className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs text-[var(--garage-text-secondary)]"
                        href={`/clients?client_id=${client.id}#new-car`}
                    >
                        <Plus size={14} />
                        Добавить автомобиль
                    </Link>
                    <Link
                        className="inline-flex h-9 items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                        href={`/appointments?client_id=${client.id}`}
                    >
                        <ClipboardList size={14} />
                        Новая запись
                    </Link>
                    {whatsappHref(client.phone, client.name) && <a aria-label={`Написать ${client.name} в WhatsApp`} className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs text-[var(--garage-text-secondary)]" href={whatsappHref(client.phone, client.name) ?? undefined} rel="noreferrer" target="_blank"><MessageCircle size={14} />Написать</a>}
                </div>
            </div>

            <section aria-label="Возврат клиента" className={`mt-4 flex flex-col justify-between gap-3 rounded-[10px] border p-4 sm:flex-row sm:items-center ${client.summary.needsFollowUp ? 'border-[var(--garage-warning)]/30 bg-[var(--garage-warning)]/5' : 'border-[var(--garage-border)] bg-[var(--garage-surface)]'}`}>
                <div><p className={`text-xs font-semibold ${client.summary.needsFollowUp ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-text)]'}`}>{client.summary.needsFollowUp ? 'Клиенту можно напомнить о сервисе' : 'История обслуживания'}</p><p className="mt-1 text-xs text-[var(--garage-text-muted)]">{client.summary.completedVisits} завершённых визитов · последний: {formatLastVisit(client.summary.lastVisitAt)}</p></div>
                <Link className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" href={`/appointments?client_id=${client.id}`}><CalendarPlus size={14} />Создать запись</Link>
            </section>

            <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-y border-[var(--garage-border)] py-3">
                <div>
                    <p className="text-sm font-medium">Клиентский кабинет</p>
                    <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                        Автомобили, история заказов и фото, доступные клиенту.
                    </p>
                </div>
                {client.portalAccess ? (
                    <span className="text-xs text-[var(--garage-success)]">
                        Доступ активирован
                    </span>
                ) : (
                    <Form
                        action={`/clients/${client.id}/portal-access`}
                        method="post"
                    >
                        {({ processing, errors }) => (
                            <div>
                                <button
                                    className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50"
                                    disabled={processing || !client.email}
                                    type="submit"
                                >
                                    {processing
                                        ? 'Создаём доступ...'
                                        : 'Открыть доступ'}
                                </button>
                                {errors.email && (
                                    <p className="mt-1 text-[10px] text-[var(--garage-error)]">
                                        {errors.email}
                                    </p>
                                )}
                            </div>
                        )}
                    </Form>
                )}
            </div>

            <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                {stats.map(([label, value, Icon]) => (
                    <article
                        className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"
                        key={String(label)}
                    >
                        <div className="flex items-center justify-between">
                            <p className="text-xs text-[var(--garage-text-secondary)]">
                                {label}
                            </p>
                            <Icon
                                className="text-[var(--garage-text-muted)]"
                                size={16}
                            />
                        </div>
                        <p
                            className={`mt-3 text-xl font-semibold ${label === 'Долг' && client.summary.balance > 0 ? 'text-[var(--garage-warning)]' : ''}`}
                        >
                            {value}
                        </p>
                    </article>
                ))}
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-[0.85fr_1.15fr]">
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                        <h2 className="text-sm font-semibold">
                            Контактные данные
                        </h2>
                    </div>
                    <div className="space-y-3 p-4 text-sm">
                        <p className="flex items-center gap-2 text-[var(--garage-text-secondary)]">
                            <Phone
                                size={15}
                                className="text-[var(--garage-text-muted)]"
                            />
                            {client.phone}
                        </p>
                        <p className="flex items-center gap-2 text-[var(--garage-text-secondary)]">
                            <UserRound
                                size={15}
                                className="text-[var(--garage-text-muted)]"
                            />
                            {client.email || 'Email не указан'}
                        </p>
                        {client.comment && (
                            <p className="border-t border-[var(--garage-border-subtle)] pt-3 text-xs text-[var(--garage-text-muted)]">
                                {client.comment}
                            </p>
                        )}
                    </div>
                    <div className="border-t border-[var(--garage-border-subtle)] px-4 py-3">
                        <h2 className="text-sm font-semibold">Автомобили</h2>
                        {client.cars.length ? (
                            <div className="mt-3 space-y-2">
                                {client.cars.map((car) => (
                                    <Link
                                        className="block rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3 hover:border-[var(--garage-text-muted)]"
                                        href={`/cars/${car.id}`}
                                        key={car.id}
                                    >
                                        <p className="text-sm font-medium">
                                            {car.make} {car.model}
                                            {!car.active && (
                                                <span className="ml-2 text-[10px] text-[var(--garage-warning)]">
                                                    Архив
                                                </span>
                                            )}
                                        </p>
                                        <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                            {car.plate_number ||
                                                'Номер не указан'}{' '}
                                            ·{' '}
                                            {car.mileage
                                                ? `${car.mileage.toLocaleString('ru-RU')} км`
                                                : 'Пробег не указан'}
                                        </p>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-2 text-xs text-[var(--garage-text-muted)]">
                                Автомобилей пока нет.
                            </p>
                        )}
                    </div>
                </section>
                <section className="overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                        <h2 className="text-sm font-semibold">
                            История обслуживания
                        </h2>
                    </div>
                    {timeline.data.length ? (
                        <div className="divide-y divide-[var(--garage-border-subtle)]">
                            {timeline.data.map((event) => (
                                <article className="flex gap-3 px-4 py-3.5" key={event.id}>
                                    {event.image && <img alt="" className="size-14 shrink-0 rounded-md border border-[var(--garage-border)] object-cover" loading="lazy" src={event.image} />}
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                            <Link className="truncate text-sm font-medium hover:text-[var(--garage-accent)]" href={event.url}>{event.title}</Link>
                                            <time className="shrink-0 text-[10px] text-[var(--garage-text-muted)]" dateTime={event.occurred_at}>{new Date(event.occurred_at).toLocaleString('ru-RU')}</time>
                                        </div>
                                        <p className="mt-1 text-xs text-[var(--garage-text-secondary)]">{event.detail}</p>
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-48 place-items-center p-8 text-sm text-[var(--garage-text-muted)]">
                            История обслуживания пока пуста.
                        </div>
                    )}
                    {timeline.links.length > 3 && <nav aria-label="Страницы истории" className="flex flex-wrap gap-1 border-t border-[var(--garage-border-subtle)] px-4 py-3">
                        {timeline.links.map((link) => link.url ? <Link aria-current={link.active ? 'page' : undefined} className={`rounded border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} preserveScroll>{paginationLabel(link.label)}</Link> : <span className="rounded border border-[var(--garage-border-subtle)] px-2 py-1 text-[10px] text-[var(--garage-text-muted)]" key={link.label}>{paginationLabel(link.label)}</span>)}
                    </nav>}
                </section>
            </div>
        </AppLayout>
    );
}

function paginationLabel(label: string) {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}

function formatLastVisit(value?: string | null) {
    if (!value) return 'визитов пока нет';
    return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(value));
}

function whatsappHref(phone: string, name: string) {
    const digits = phone.replace(/\D/g, '');
    if (digits.length < 10) return null;
    const message = encodeURIComponent(`Здравствуйте, ${name}! Подскажите, актуально ли записать автомобиль на обслуживание?`);
    return `https://wa.me/${digits}?text=${message}`;
}
