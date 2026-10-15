import { Form, Link } from '@inertiajs/react';
import { CarFront, RotateCcw, Search } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type Car = {
    id: number;
    make: string;
    model: string;
    year?: number | null;
    plate_number?: string | null;
    vin?: string | null;
    active: boolean;
    completed_visits_count: number;
    last_service_at?: string | null;
    client?: { id: number; name: string; phone: string } | null;
};

type Props = {
    filters: { q: string };
    archived: boolean;
    summary: { cars: number; serviced90Days: number; withoutHistory: number };
    cars: {
        data: Car[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
};

export default function Index({ filters, archived, cars, summary }: Props) {
    return (
        <AppLayout>
            <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Рабочее пространство
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Автомобили
                    </h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                        Автопарк клиентов и история обслуживания.
                    </p>
                </div>
                <div className="flex gap-2">
                    <Link
                        className="inline-flex h-9 items-center justify-center rounded-md border border-[var(--garage-border)] px-3 text-xs font-medium text-[var(--garage-text-secondary)] hover:border-[var(--garage-accent)] hover:text-[var(--garage-text)]"
                        href={archived ? '/cars' : '/cars?archived=1'}
                    >
                        {archived ? 'Активные' : 'Архив'}
                    </Link>
                    {!archived && (
                        <a
                            className="inline-flex h-9 items-center justify-center rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                            href="/clients#new-car"
                        >
                            Добавить автомобиль
                        </a>
                    )}
                </div>
            </header>

            {!archived && <section aria-label="Аналитика автопарка" className="mt-5 grid gap-3 sm:grid-cols-3">
                <SummaryCard label="Активные автомобили" value={summary.cars} detail="в базе сервиса" />
                <SummaryCard label="Заезжали за 90 дней" value={summary.serviced90Days} detail="по завершённым заказам" />
                <SummaryCard label="Без истории сервиса" value={summary.withoutHistory} detail="ещё не было закрытых заказов" />
            </section>}

            <section className="mt-5 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <form
                    action="/cars"
                    className="flex flex-col gap-3 border-b border-[var(--garage-border-subtle)] p-4 sm:flex-row sm:items-center sm:justify-between"
                    method="get"
                >
                    <label className="flex h-9 min-w-0 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)] sm:w-96">
                        <Search size={14} />
                        <input
                            aria-label="Поиск автомобилей"
                            className="min-w-0 flex-1 bg-transparent text-xs text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]"
                            defaultValue={filters.q}
                            name="q"
                            placeholder="Марка, модель, номер, VIN или клиент"
                        />
                    </label>
                    <span className="text-xs text-[var(--garage-text-muted)]">
                        {cars.total} автомобилей
                    </span>
                </form>

                {cars.data.length ? (
                    <div className="divide-y divide-[var(--garage-border-subtle)]">
                        {cars.data.map((car) => (
                            <div
                                className="flex flex-col gap-3 px-4 py-3.5 hover:bg-[var(--garage-surface-secondary)] sm:flex-row sm:items-center sm:justify-between"
                                key={car.id}
                            >
                                <Link
                                    className="flex min-w-0 items-center gap-3"
                                    href={`/cars/${car.id}`}
                                >
                                    <span className="grid size-9 shrink-0 place-items-center rounded-md bg-[var(--garage-surface-elevated)] text-[var(--garage-accent)]">
                                        <CarFront size={16} />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {car.make} {car.model}
                                            {car.year ? ` · ${car.year}` : ''}
                                            {archived && (
                                                <span className="ml-2 text-[10px] text-[var(--garage-warning)]">
                                                    Архив
                                                </span>
                                            )}
                                        </p>
                                        <p className="mt-1 truncate text-xs text-[var(--garage-text-muted)]">
                                            {car.client?.name ??
                                                'Клиент не указан'}
                                            {car.client?.phone
                                                ? ` · ${car.client.phone}`
                                                : ''}
                                        </p>
                                            <p className="mt-1 text-[10px] text-[var(--garage-text-secondary)]">{car.completed_visits_count} визитов · последний: {formatLastVisit(car.last_service_at)}</p>
                                    </div>
                                </Link>
                                <div className="flex gap-4 pl-12 text-xs text-[var(--garage-text-secondary)] sm:pl-0">
                                    <span>
                                        {car.plate_number || 'Без номера'}
                                    </span>
                                    {car.vin && (
                                        <span className="max-w-44 truncate text-[var(--garage-text-muted)]">
                                            VIN {car.vin}
                                        </span>
                                    )}
                                </div>
                                {archived && (
                                    <Form
                                        action={`/cars/${car.id}/restore`}
                                        method="post"
                                    >
                                        <button
                                            className="inline-flex h-8 items-center gap-1.5 rounded border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-success)]"
                                            type="submit"
                                        >
                                            <RotateCcw size={12} />
                                            Восстановить
                                        </button>
                                    </Form>
                                )}
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="grid min-h-48 place-items-center p-8 text-center">
                        <div>
                            <CarFront
                                className="mx-auto text-[var(--garage-text-muted)]"
                                size={24}
                            />
                            <p className="mt-3 text-sm font-medium">
                                {filters.q
                                    ? 'Автомобили не найдены'
                                    : 'Автомобилей пока нет'}
                            </p>
                            <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                {filters.q
                                    ? 'Измените запрос и попробуйте ещё раз.'
                                    : 'Добавьте автомобиль из карточки клиента или раздела клиентов.'}
                            </p>
                        </div>
                    </div>
                )}

                {cars.links.length > 3 && (
                    <nav
                        aria-label="Страницы автомобилей"
                        className="flex flex-wrap gap-1 border-t border-[var(--garage-border-subtle)] px-4 py-3"
                    >
                        {cars.links.map((link) =>
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
        </AppLayout>
    );
}

function SummaryCard({ label, value, detail }: { label: string; value: number; detail: string }) {
    return <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><p className="text-xs text-[var(--garage-text-secondary)]">{label}</p><p className="mt-3 text-2xl font-semibold tabular-nums">{value.toLocaleString('ru-RU')}</p><p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">{detail}</p></article>;
}

function formatLastVisit(value?: string | null) {
    if (!value) return 'нет завершённых визитов';
    return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value));
}
