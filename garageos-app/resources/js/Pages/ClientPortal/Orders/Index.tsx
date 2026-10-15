import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ClipboardList } from 'lucide-react';
import ClientPortalLayout from '../../../Layouts/ClientPortalLayout';

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
    view: 'active' | 'all';
    orders: Order[];
};
const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;

export default function Index({
    clientName,
    organizationName,
    view,
    orders,
}: Props) {
    return (
        <ClientPortalLayout
            clientName={clientName}
            organizationName={organizationName}
        >
            <Head title="Заказы" />
            <header>
                <p className="text-xs uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                    Мой кабинет
                </p>
                <h1 className="mt-1 text-2xl font-semibold">Заказы</h1>
            </header>
            <div
                aria-label="Фильтр заказов"
                className="mt-5 inline-flex rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-1"
                role="group"
            >
                <Link
                    aria-current={view === 'active' ? 'page' : undefined}
                    className={`rounded px-3 py-2 text-xs ${view === 'active' ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)]'}`}
                    href="/portal/orders"
                >
                    Текущие
                </Link>
                <Link
                    aria-current={view === 'all' ? 'page' : undefined}
                    className={`rounded px-3 py-2 text-xs ${view === 'all' ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)]'}`}
                    href="/portal/orders?view=all"
                >
                    Все
                </Link>
            </div>
            {orders.length ? (
                <div className="mt-4 divide-y divide-[var(--garage-border-subtle)] rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)]">
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
                                    {formatDate(order.opened_at)}
                                </p>
                                <span className="mt-2 inline-flex rounded border border-[var(--garage-border)] px-2 py-1 text-[10px]">
                                    {order.status || 'Статус уточняется'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-4 sm:justify-end">
                                <span className="text-xs">
                                    {money(order.total)}
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
                <div className="mt-4 flex items-center gap-3 rounded-md border border-dashed border-[var(--garage-border)] p-5 text-sm text-[var(--garage-text-muted)]">
                    <ClipboardList size={17} />
                    {view === 'active'
                        ? 'Текущих заказов нет.'
                        : 'Заказов пока нет.'}
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
