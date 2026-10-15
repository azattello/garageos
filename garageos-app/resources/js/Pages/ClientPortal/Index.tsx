import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CarFront, ClipboardList } from 'lucide-react';
import ClientPortalLayout from '../../Layouts/ClientPortalLayout';

type Car = {
    id: number;
    make: string;
    model: string;
    year?: number | null;
    plate?: string | null;
    mileage?: number | null;
    last_visit_at?: string | null;
};
type Order = {
    id: number;
    number: string;
    status?: string | null;
    status_slug?: string | null;
    status_updated_at?: string | null;
    opened_at?: string | null;
    car: string;
    plate?: string | null;
    total: number;
    paid: number;
    balance: number;
};
type Props = {
    clientName: string;
    organizationName: string;
    phone?: string | null;
    cars: Car[];
    orders: Order[];
};
const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;

export default function Index({
    clientName,
    organizationName,
    cars,
    orders,
}: Props) {
    return (
        <ClientPortalLayout
            clientName={clientName}
            organizationName={organizationName}
        >
            <Head title="Мой кабинет" />
            <header className="mb-7">
                <p className="text-xs font-medium uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                    Клиентский кабинет
                </p>
                <h1 className="mt-2 text-2xl font-semibold">
                    Здравствуйте, {clientName}
                </h1>
            </header>

            <section id="cars">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <CarFront
                            className="text-[var(--garage-accent)]"
                            size={17}
                        />
                        <h2 className="text-base font-semibold">
                            Мои автомобили
                        </h2>
                    </div>
                    <span className="text-xs text-[var(--garage-text-muted)]">
                        {cars.length}
                    </span>
                </div>
                {cars.length ? (
                    <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {cars.map((car) => (
                            <Link
                                className="group block rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4 hover:border-[var(--garage-accent)]"
                                href={`/portal/cars/${car.id}`}
                                key={car.id}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold">
                                            {car.make} {car.model}
                                            {car.year ? ` · ${car.year}` : ''}
                                        </p>
                                        <p className="mt-1 text-xs text-[var(--garage-text-secondary)]">
                                            {car.plate || 'Госномер не указан'}
                                        </p>
                                    </div>
                                    <ArrowRight
                                        className="shrink-0 text-[var(--garage-text-muted)] group-hover:text-[var(--garage-accent)]"
                                        size={15}
                                    />
                                </div>
                                <div className="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-[var(--garage-text-muted)]">
                                    {car.mileage !== null &&
                                        car.mileage !== undefined && (
                                            <span>
                                                {car.mileage.toLocaleString(
                                                    'ru-RU',
                                                )}{' '}
                                                км
                                            </span>
                                        )}
                                    <span>
                                        Последний визит:{' '}
                                        {car.last_visit_at
                                            ? formatDate(car.last_visit_at)
                                            : 'не было'}
                                    </span>
                                </div>
                                <p className="mt-3 text-xs font-medium text-[var(--garage-accent)]">
                                    Открыть автомобиль
                                </p>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        icon={CarFront}
                        text="Пока нет автомобилей. Они появятся, когда сервис добавит ваше авто."
                    />
                )}
            </section>

            <section className="mt-8">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <ClipboardList
                            className="text-[var(--garage-accent)]"
                            size={17}
                        />
                        <h2 className="text-base font-semibold">
                            Текущие заказы
                        </h2>
                    </div>
                    <Link
                        className="text-xs text-[var(--garage-info)] hover:underline"
                        href="/portal/orders"
                    >
                        Все заказы
                    </Link>
                </div>
                {orders.length ? (
                    <div className="mt-3 divide-y divide-[var(--garage-border-subtle)] rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        {orders.map((order) => (
                            <Link
                                className="flex flex-col gap-3 p-4 hover:bg-[var(--garage-surface-secondary)] sm:flex-row sm:items-center sm:justify-between"
                                href={`/portal/orders/${order.id}`}
                                key={order.id}
                            >
                                <div className="min-w-0">
                                    <p className="text-xs font-semibold">
                                        {order.number} · {order.car}
                                    </p>
                                    <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">
                                        {order.plate || 'Госномер не указан'} ·{' '}
                                        {order.status || 'Статус уточняется'}
                                    </p>
                                    {order.status_updated_at && (
                                        <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">
                                            Обновлён{' '}
                                            {formatDate(
                                                order.status_updated_at,
                                            )}
                                        </p>
                                    )}
                                </div>
                                <div className="flex items-center justify-between gap-3 sm:justify-end">
                                    <span className="text-xs text-[var(--garage-text-secondary)]">
                                        Итого {money(order.total)}
                                    </span>
                                    <span
                                        className={`text-xs font-semibold ${order.balance > 0 ? 'text-[var(--garage-warning)]' : 'text-[var(--garage-success)]'}`}
                                    >
                                        К оплате {money(order.balance)}
                                    </span>
                                    <ArrowRight
                                        className="text-[var(--garage-text-muted)]"
                                        size={14}
                                    />
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        icon={ClipboardList}
                        text="Текущих заказов нет. История обслуживания появится здесь."
                    />
                )}
            </section>
        </ClientPortalLayout>
    );
}

function EmptyState({
    icon: Icon,
    text,
}: {
    icon: typeof CarFront;
    text: string;
}) {
    return (
        <div className="mt-3 flex items-center gap-3 rounded-md border border-dashed border-[var(--garage-border)] px-4 py-6 text-sm text-[var(--garage-text-muted)]">
            <Icon size={17} />
            {text}
        </div>
    );
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'short',
        timeZone: 'Asia/Almaty',
    }).format(new Date(value));
}
