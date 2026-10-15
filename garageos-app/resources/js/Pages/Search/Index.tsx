import { Form, Link } from '@inertiajs/react';
import {
    CarFront,
    ClipboardList,
    Search as SearchIcon,
    Users,
} from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type Props = {
    query: string;
    clients: { id: number; name: string; phone: string }[];
    cars: {
        id: number;
        make: string;
        model: string;
        plate_number?: string;
        vin?: string;
    }[];
    orders: { id: number; number: string; status: { name: string } }[];
};
export default function Index({ query, clients, cars, orders }: Props) {
    return (
        <AppLayout>
            <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                Рабочее пространство
            </p>
            <h1 className="mt-2 text-2xl font-semibold">Глобальный поиск</h1>
            <Form
                action="/search"
                method="get"
                className="mt-6 flex max-w-2xl gap-2"
            >
                <input
                    autoFocus
                    className="h-10 flex-1 rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm outline-none focus:border-[var(--garage-accent)]"
                    defaultValue={query}
                    name="q"
                    placeholder="Клиент, телефон, VIN, госномер или заказ..."
                />
                <button
                    className="inline-flex h-10 items-center gap-2 rounded-md bg-[var(--garage-accent)] px-4 text-xs font-semibold text-[var(--garage-background)]"
                    type="submit"
                >
                    <SearchIcon size={15} />
                    Найти
                </button>
            </Form>
            {query && (
                <div className="mt-6 grid gap-4 lg:grid-cols-3">
                    <Result
                        title="Клиенты"
                        icon={<Users size={16} />}
                        rows={clients.map((item) => ({
                            label: `${item.name} · ${item.phone}`,
                            href: `/clients/${item.id}`,
                        }))}
                    />
                    <Result
                        title="Автомобили"
                        icon={<CarFront size={16} />}
                        rows={cars.map((item) => ({
                            label: `${item.make} ${item.model} · ${item.plate_number || item.vin || 'данные не указаны'}`,
                            href: `/cars/${item.id}`,
                        }))}
                    />
                    <Result
                        title="Заказы"
                        icon={<ClipboardList size={16} />}
                        rows={orders.map((item) => ({
                            label: `${item.number} · ${item.status.name}`,
                            href: `/orders/${item.id}`,
                        }))}
                    />
                </div>
            )}
        </AppLayout>
    );
}
function Result({
    title,
    icon,
    rows,
}: {
    title: string;
    icon: React.ReactNode;
    rows: { label: string; href: string }[];
}) {
    return (
        <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
            <div className="flex items-center gap-2 border-b border-[var(--garage-border-subtle)] px-4 py-3 text-sm font-semibold">
                {icon}
                {title}
            </div>
            {rows.length ? (
                <div className="divide-y divide-[var(--garage-border-subtle)]">
                    {rows.map((row) => (
                        <Link
                            className="block px-4 py-3 text-xs text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]"
                            href={row.href}
                            key={row.href}
                        >
                            {row.label}
                        </Link>
                    ))}
                </div>
            ) : (
                <p className="p-4 text-xs text-[var(--garage-text-muted)]">
                    Совпадений нет.
                </p>
            )}
        </section>
    );
}
