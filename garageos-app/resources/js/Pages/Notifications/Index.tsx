import { Form, Link } from '@inertiajs/react';
import { Bell, Check, CheckCircle2, ClipboardList, Search } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type Notification = {
    id: number;
    title: string;
    body: string;
    read_at?: string;
    created_at: string;
};
type Activity = {
    id: number;
    action: string;
    created_at: string;
    actor: string;
    entity: string;
    subject_id?: number | null;
    before?: unknown;
    after?: unknown;
};
type Props = {
    notifications: { data: Notification[] };
    activities: {
        data: Activity[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    } | null;
    activityFilters: {
        q: string;
        actor: string;
        entity: string;
        from: string;
        to: string;
    };
    activityActors: { id: number; name: string }[];
    activityEntities: { value: string; label: string }[];
    canViewActivities: boolean;
};

export default function Index({
    notifications,
    activities,
    activityFilters,
    activityActors,
    activityEntities,
    canViewActivities,
}: Props) {
    const unreadCount = notifications.data.filter(
        (item) => !item.read_at,
    ).length;

    return (
        <AppLayout>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Система
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold">
                        Уведомления и активность
                    </h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                        События вашей организации и важные действия.
                    </p>
                </div>
                {unreadCount > 0 && (
                    <Form action="/notifications/read-all" method="post">
                        <button
                            type="submit"
                            className="inline-flex items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 py-2 text-xs font-medium text-[var(--garage-text)]"
                        >
                            <Check size={14} />
                            Отметить все прочитанными
                        </button>
                    </Form>
                )}
            </div>

            <div className="mt-6 grid items-start gap-4 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3">
                        <h2 className="text-sm font-semibold">Уведомления</h2>
                        {unreadCount > 0 && (
                            <span className="rounded-full bg-[rgba(184,255,61,0.1)] px-2 py-1 text-[10px] font-medium text-[var(--garage-accent)]">
                                {unreadCount} новых
                            </span>
                        )}
                    </div>
                    {notifications.data.length ? (
                        <div className="divide-y divide-[var(--garage-border-subtle)]">
                            {notifications.data.map((item) => (
                                <div
                                    className="flex items-start justify-between gap-3 px-4 py-4"
                                    key={item.id}
                                >
                                    <div className="flex gap-3">
                                        <Bell
                                            className={`mt-0.5 ${item.read_at ? 'text-[var(--garage-text-muted)]' : 'text-[var(--garage-accent)]'}`}
                                            size={16}
                                        />
                                        <div>
                                            <p className="text-sm font-medium">
                                                {item.title}
                                            </p>
                                            <p className="mt-1 text-xs text-[var(--garage-text-secondary)]">
                                                {item.body}
                                            </p>
                                            <p className="mt-2 text-[10px] uppercase tracking-[0.12em] text-[var(--garage-text-muted)]">
                                                {new Date(
                                                    item.created_at,
                                                ).toLocaleString('ru-RU')}
                                            </p>
                                        </div>
                                    </div>
                                    {!item.read_at && (
                                        <Form
                                            action={`/notifications/${item.id}/read`}
                                            method="post"
                                            className="shrink-0"
                                        >
                                            <button
                                                type="submit"
                                                className="rounded-md border border-[var(--garage-border)] px-2 py-1 text-[10px] font-medium text-[var(--garage-text-secondary)] hover:border-[var(--garage-accent)] hover:text-[var(--garage-text)]"
                                            >
                                                Прочитать
                                            </button>
                                        </Form>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-40 place-items-center p-8 text-center">
                            <div>
                                <CheckCircle2
                                    className="mx-auto text-[var(--garage-text-muted)]"
                                    size={22}
                                />
                                <p className="mt-3 text-sm">
                                    Новых уведомлений нет
                                </p>
                            </div>
                        </div>
                    )}
                </section>

                {canViewActivities && activities && (
                    <section className="min-w-0 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-sm font-semibold">
                                        Журнал действий
                                    </h2>
                                    <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                        {activities.total} событий организации
                                    </p>
                                </div>
                                <ClipboardList
                                    className="text-[var(--garage-text-muted)]"
                                    size={16}
                                />
                            </div>
                            <Form
                                action="/notifications"
                                className="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3"
                                method="get"
                            >
                                <label className="flex h-9 min-w-0 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 text-[var(--garage-text-muted)] sm:col-span-2 xl:col-span-1">
                                    <Search size={13} />
                                    <input
                                        aria-label="Поиск действий"
                                        className="min-w-0 flex-1 bg-transparent text-[11px] text-[var(--garage-text)] outline-none"
                                        defaultValue={activityFilters.q}
                                        name="q"
                                        placeholder="Действие или ID объекта"
                                    />
                                </label>
                                <label className="text-[10px] text-[var(--garage-text-muted)]">
                                    Пользователь
                                    <select
                                        aria-label="Фильтр по пользователю"
                                        className="mt-1 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs text-[var(--garage-text)]"
                                        defaultValue={activityFilters.actor}
                                        name="actor"
                                    >
                                        <option value="">
                                            Все пользователи
                                        </option>
                                        {activityActors.map((actor) => (
                                            <option
                                                key={actor.id}
                                                value={actor.id}
                                            >
                                                {actor.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label className="text-[10px] text-[var(--garage-text-muted)]">
                                    Объект
                                    <select
                                        aria-label="Фильтр по объекту"
                                        className="mt-1 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs text-[var(--garage-text)]"
                                        defaultValue={activityFilters.entity}
                                        name="entity"
                                    >
                                        <option value="">Все объекты</option>
                                        {activityEntities.map((entity) => (
                                            <option
                                                key={entity.value}
                                                value={entity.value}
                                            >
                                                {entity.label}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label className="text-[10px] text-[var(--garage-text-muted)]">
                                    С даты
                                    <input
                                        aria-label="С даты"
                                        className="mt-1 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs text-[var(--garage-text)]"
                                        defaultValue={activityFilters.from}
                                        name="from"
                                        type="date"
                                    />
                                </label>
                                <label className="text-[10px] text-[var(--garage-text-muted)]">
                                    По дату
                                    <input
                                        aria-label="По дату"
                                        className="mt-1 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs text-[var(--garage-text)]"
                                        defaultValue={activityFilters.to}
                                        name="to"
                                        type="date"
                                    />
                                </label>
                                <div className="flex items-end gap-2">
                                    <button
                                        className="h-9 flex-1 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                                        type="submit"
                                    >
                                        Применить
                                    </button>
                                    <Link
                                        className="inline-flex h-9 items-center rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]"
                                        href="/notifications"
                                    >
                                        Сбросить
                                    </Link>
                                </div>
                            </Form>
                        </div>
                        {activities.data.length ? (
                            <div className="divide-y divide-[var(--garage-border-subtle)]">
                                {activities.data.map((item) => (
                                    <article
                                        className="flex gap-3 px-4 py-4"
                                        key={item.id}
                                    >
                                        <ClipboardList
                                            className="mt-0.5 shrink-0 text-[var(--garage-text-muted)]"
                                            size={16}
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="break-words text-xs font-medium">
                                                {item.actor} · {item.action}
                                            </p>
                                            <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">
                                                {item.entity}
                                                {item.subject_id
                                                    ? ` #${item.subject_id}`
                                                    : ''}{' '}
                                                ·{' '}
                                                {new Date(
                                                    item.created_at,
                                                ).toLocaleString('ru-RU')}
                                            </p>
                                            {((item.before !== null &&
                                                item.before !== undefined) ||
                                                (item.after !== null &&
                                                    item.after !==
                                                        undefined)) && (
                                                <details className="mt-2 rounded-md border border-[var(--garage-border-subtle)] px-2.5 py-2">
                                                    <summary className="cursor-pointer text-[10px] font-medium text-[var(--garage-text-secondary)]">
                                                        Изменения до и после
                                                    </summary>
                                                    <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                                        {item.before !== null &&
                                                            item.before !==
                                                                undefined && (
                                                                <div>
                                                                    <p className="text-[9px] font-semibold uppercase text-[var(--garage-text-muted)]">
                                                                        До
                                                                    </p>
                                                                    <pre className="mt-1 max-h-40 overflow-auto whitespace-pre-wrap break-words text-[10px] text-[var(--garage-text-secondary)]">
                                                                        {formatDetails(
                                                                            item.before,
                                                                        )}
                                                                    </pre>
                                                                </div>
                                                            )}
                                                        {item.after !== null &&
                                                            item.after !==
                                                                undefined && (
                                                                <div>
                                                                    <p className="text-[9px] font-semibold uppercase text-[var(--garage-text-muted)]">
                                                                        После
                                                                    </p>
                                                                    <pre className="mt-1 max-h-40 overflow-auto whitespace-pre-wrap break-words text-[10px] text-[var(--garage-text-secondary)]">
                                                                        {formatDetails(
                                                                            item.after,
                                                                        )}
                                                                    </pre>
                                                                </div>
                                                            )}
                                                    </div>
                                                </details>
                                            )}
                                        </div>
                                    </article>
                                ))}
                            </div>
                        ) : (
                            <div className="grid min-h-40 place-items-center p-8 text-center">
                                <div>
                                    <CheckCircle2
                                        className="mx-auto text-[var(--garage-text-muted)]"
                                        size={22}
                                    />
                                    <p className="mt-3 text-sm">
                                        Событий по этим условиям нет
                                    </p>
                                </div>
                            </div>
                        )}
                        {activities.links.length > 3 && (
                            <nav
                                aria-label="Страницы журнала действий"
                                className="flex flex-wrap gap-1 border-t border-[var(--garage-border-subtle)] px-4 py-3"
                            >
                                {activities.links.map((link) =>
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
                )}
            </div>
        </AppLayout>
    );
}

function formatDetails(value: unknown) {
    if (typeof value === 'string') return value;
    if (value === null || value === undefined) return '';
    return JSON.stringify(value, null, 2);
}
