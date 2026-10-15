import { Form, Head, Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import ClientPortalLayout from '../../Layouts/ClientPortalLayout';

type Order = {
    id: number;
    number: string;
    status?: string | null;
    opened_at: string;
    car: string;
    plate?: string | null;
    total: number;
    paid: number;
    balance: number;
};
type Props = {
    clientName: string;
    organizationName: string;
    carId: number | null;
    cars: {
        id: number;
        make: string;
        model: string;
        plate_number?: string | null;
    }[];
    orders: Order[];
};
const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;

export default function History({
    clientName,
    organizationName,
    carId,
    cars,
    orders,
}: Props) {
    return (
        <ClientPortalLayout
            clientName={clientName}
            organizationName={organizationName}
        >
            <Head title="История обслуживания" />
            <header>
                <p className="text-xs uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                    Мой кабинет
                </p>
                <h1 className="mt-1 text-2xl font-semibold">
                    История обслуживания
                </h1>
            </header>
            <Form
                action="/portal/history"
                className="mt-5 max-w-lg"
                method="get"
            >
                <label className="block text-xs font-medium">
                    Автомобиль
                    <select
                        className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-sm"
                        defaultValue={carId ?? ''}
                        name="car_id"
                    >
                        <option value="">Все автомобили</option>
                        {cars.map((car) => (
                            <option key={car.id} value={car.id}>
                                {car.make} {car.model} ·{' '}
                                {car.plate_number || 'без номера'}
                            </option>
                        ))}
                    </select>
                </label>
                <button
                    className="mt-2 h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs"
                    type="submit"
                >
                    Показать
                </button>
            </Form>
            {orders.length ? (
                <div className="mt-5 space-y-2">
                    {orders.map((order) => (
                        <Link
                            className="flex items-center justify-between gap-4 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4 hover:bg-[var(--garage-surface-secondary)]"
                            href={`/portal/orders/${order.id}`}
                            key={order.id}
                        >
                            <div className="min-w-0">
                                <p className="text-xs font-semibold">
                                    {formatDate(order.opened_at)} · {order.car}
                                </p>
                                <p className="mt-1 truncate text-xs text-[var(--garage-text-secondary)]">
                                    {order.number} · {order.status || 'Закрыт'}{' '}
                                    · {order.plate || 'без номера'}
                                </p>
                            </div>
                            <span className="shrink-0 text-xs font-semibold">
                                {money(order.total)}
                            </span>
                        </Link>
                    ))}
                </div>
            ) : (
                <div className="mt-5 flex items-center gap-3 rounded-md border border-dashed border-[var(--garage-border)] p-5 text-sm text-[var(--garage-text-muted)]">
                    <ClipboardList size={17} />
                    История обслуживания пока пуста.
                </div>
            )}
        </ClientPortalLayout>
    );
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'short',
        timeZone: 'Asia/Almaty',
    }).format(new Date(value));
}
