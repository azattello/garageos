import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CarFront, ClipboardList, Image } from 'lucide-react';
import ClientPortalLayout from '../../../Layouts/ClientPortalLayout';

type OrderSummary = {
    id: number;
    number: string;
    status: string | null;
    status_slug: string | null;
    opened_at: string;
    car: string;
    plate?: string | null;
    total: number;
    paid: number;
    balance: number;
};
type OrderDetail = Omit<OrderSummary, 'status' | 'status_slug'> & {
    status: { slug?: string | null; name?: string | null };
    items: {
        type: string;
        name: string;
        quantity: number;
        unit_price: number;
        total: number;
    }[];
    photos: Photo[];
};
type Photo = {
    url: string;
    caption?: string | null;
    category: string;
    created_at?: string | null;
};
type Props = {
    clientName: string;
    organizationName: string;
    car: {
        id: number;
        make: string;
        model: string;
        year?: number | null;
        plate?: string | null;
        mileage?: number | null;
        active: boolean;
    };
    activeOrder: OrderSummary | null;
    orders: OrderDetail[];
};
const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;

export default function Show({
    clientName,
    organizationName,
    car,
    activeOrder,
    orders,
}: Props) {
    const photos = orders.flatMap((order) =>
        order.photos.map((photo) => ({ ...photo, orderNumber: order.number })),
    );
    return (
        <ClientPortalLayout
            clientName={clientName}
            organizationName={organizationName}
        >
            <Head title={`${car.make} ${car.model}`} />
            <Link
                className="inline-flex items-center gap-2 text-xs text-[var(--garage-text-secondary)]"
                href="/portal"
            >
                <ArrowLeft size={14} />
                Мой кабинет
            </Link>
            <header className="mt-4">
                <p className="text-xs uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                    Мой автомобиль
                </p>
                <h1 className="mt-1 text-2xl font-semibold">
                    {car.make} {car.model}
                    {car.year ? ` · ${car.year}` : ''}
                </h1>
                <p className="mt-2 text-sm text-[var(--garage-text-secondary)]">
                    {car.plate || 'Госномер не указан'}
                    {car.mileage !== null && car.mileage !== undefined
                        ? ` · ${car.mileage.toLocaleString('ru-RU')} км`
                        : ''}
                </p>
            </header>
            {activeOrder && (
                <section className="mt-5 rounded-md border border-[var(--garage-accent)]/30 bg-[var(--garage-surface)] p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-[10px] uppercase text-[var(--garage-text-muted)]">
                                Сейчас в сервисе · {activeOrder.number}
                            </p>
                            <p className="mt-2 text-base font-semibold">
                                {activeOrder.status}
                            </p>
                        </div>
                        <ClipboardList
                            className="text-[var(--garage-accent)]"
                            size={17}
                        />
                    </div>
                    <div className="mt-4 flex flex-wrap gap-5 text-xs">
                        <span>Итого {money(activeOrder.total)}</span>
                        <span
                            className={
                                activeOrder.balance > 0
                                    ? 'text-[var(--garage-warning)]'
                                    : 'text-[var(--garage-success)]'
                            }
                        >
                            К оплате {money(activeOrder.balance)}
                        </span>
                    </div>
                    <Link
                        className="mt-4 inline-flex h-9 items-center gap-2 rounded border border-[var(--garage-border)] px-3 text-xs"
                        href={`/portal/orders/${activeOrder.id}`}
                    >
                        Открыть заказ
                    </Link>
                </section>
            )}
            <section className="mt-7">
                <h2 className="text-base font-semibold">
                    История обслуживания
                </h2>
                {orders.length ? (
                    <div className="mt-3 divide-y divide-[var(--garage-border-subtle)] rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        {orders.map((order) => (
                            <Link
                                className="flex flex-wrap items-center justify-between gap-3 p-4 hover:bg-[var(--garage-surface-secondary)]"
                                href={`/portal/orders/${order.id}`}
                                key={order.id}
                            >
                                <div>
                                    <p className="text-xs font-semibold">
                                        {order.number} · {order.status.name}
                                    </p>
                                    <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">
                                        {formatDate(order.opened_at)} ·{' '}
                                        {order.items
                                            .map((item) => item.name)
                                            .join(', ')}
                                    </p>
                                </div>
                                <span className="text-xs font-semibold">
                                    {money(order.total)}
                                </span>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <p className="mt-3 rounded-md border border-dashed border-[var(--garage-border)] p-5 text-sm text-[var(--garage-text-muted)]">
                        История обслуживания пока пуста.
                    </p>
                )}
            </section>
            <section className="mt-7">
                <div className="flex items-center gap-2">
                    <Image className="text-[var(--garage-accent)]" size={16} />
                    <h2 className="text-base font-semibold">Фото по заказам</h2>
                </div>
                {photos.length ? (
                    <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        {photos.map((photo, index) => (
                            <a
                                className="overflow-hidden rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)]"
                                href={photo.url}
                                key={`${photo.url}-${index}`}
                                rel="noreferrer"
                                target="_blank"
                            >
                                <img
                                    alt={photo.caption || photo.category}
                                    className="aspect-[4/3] w-full object-cover"
                                    loading="lazy"
                                    src={photo.url}
                                />
                                <span className="block truncate px-2 py-2 text-[10px] text-[var(--garage-text-secondary)]">
                                    {photo.caption || photo.category} ·{' '}
                                    {photo.orderNumber}
                                </span>
                            </a>
                        ))}
                    </div>
                ) : (
                    <p className="mt-3 text-sm text-[var(--garage-text-muted)]">
                        Фото, доступные клиенту, пока не добавлены.
                    </p>
                )}
            </section>
        </ClientPortalLayout>
    );
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'short',
        timeZone: 'Asia/Almaty',
    }).format(new Date(value));
}
