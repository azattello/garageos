import { Link, router } from '@inertiajs/react';
import { ArrowRight, ClipboardList } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

type Order = {
    id: number;
    number: string;
    total: number;
    client: { name: string };
    car: { make: string; model: string; plate_number?: string };
    status: { name: string; slug: string };
    mechanic?: { name: string };
    nextStatuses: { slug: string; name: string }[];
};
type Props = { orders: Order[] };
type Column = { slug: string; title: string };
const columns: Column[] = [
    { slug: 'new', title: 'Новые' },
    { slug: 'accepted', title: 'Приняты' },
    { slug: 'diagnostics', title: 'Диагностика' },
    { slug: 'in_progress', title: 'В работе' },
    { slug: 'waiting_parts', title: 'Запчасти' },
    { slug: 'ready', title: 'Готовы' },
    { slug: 'delivered', title: 'Выданы' },
    { slug: 'closed', title: 'Закрыты' },
    { slug: 'cancelled', title: 'Отменены' },
];

export default function Kanban({ orders }: Props) {
    const [draggedOrderId, setDraggedOrderId] = useState<number | null>(null);
    const [processingOrderId, setProcessingOrderId] = useState<number | null>(null);
    const [transitionErrors, setTransitionErrors] = useState<Record<number, string>>({});

    const move = (order: Order, slug: string) => {
        const target = order.nextStatuses.find((status) => status.slug === slug);
        if (!target) {
            const allowed = order.nextStatuses.map((status) => status.name).join(', ');
            setTransitionErrors((current) => ({
                ...current,
                [order.id]: allowed
                    ? `Этот переход недоступен. Следующий этап: ${allowed}.`
                    : 'Заказ находится на конечном этапе.',
            }));
            setDraggedOrderId(null);
            return;
        }

        setTransitionErrors((current) => ({ ...current, [order.id]: '' }));
        setProcessingOrderId(order.id);
        router.post(
            `/orders/${order.id}/status`,
            { status: target.slug },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setTransitionErrors((current) => ({
                        ...current,
                        [order.id]: errors.status ?? 'Не удалось изменить этап. Проверьте заказ и повторите.',
                    }));
                },
                onFinish: () => setProcessingOrderId(null),
            },
        );
        setDraggedOrderId(null);
    };

    return (
        <AppLayout>
            <div className="flex items-end justify-between gap-4">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Рабочее пространство
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold">
                        Kanban заказов
                    </h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                        Выберите допустимый следующий этап или перетащите карточку в колонку.
                    </p>
                </div>
                <a
                    className="inline-flex h-9 items-center rounded-md border border-[var(--garage-border)] px-3 text-xs font-medium"
                    href="/orders"
                >
                    Список заказов
                </a>
            </div>
            <div className="mt-6 grid auto-cols-[minmax(250px,1fr)] grid-flow-col gap-3 overflow-x-auto pb-4">
                {columns.map((column) => (
                    <section
                        className="min-w-[250px] rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]"
                        key={column.slug}
                        onDragOver={(event) => event.preventDefault()}
                        onDrop={() => {
                            const draggedOrder = orders.find((order) => order.id === draggedOrderId);
                            if (draggedOrder) move(draggedOrder, column.slug);
                        }}
                    >
                        <header className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-3 py-3">
                            <h2 className="text-xs font-semibold uppercase tracking-[0.12em]">
                                {column.title}
                            </h2>
                            <span className="rounded-full bg-[var(--garage-surface-elevated)] px-2 py-0.5 text-[10px] text-[var(--garage-text-muted)]">
                                {
                                    orders.filter((order) => order.status.slug === column.slug).length
                                }
                            </span>
                        </header>
                        <div className="min-h-40 space-y-2 p-2">
                            {orders
                                .filter(
                                    (order) =>
                                        order.status.slug === column.slug,
                                )
                                .map((order) => (
                                    <article
                                        className="cursor-grab rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3 active:cursor-grabbing"
                                        draggable
                                        onDragStart={() => setDraggedOrderId(order.id)}
                                        onDragEnd={() => setDraggedOrderId(null)}
                                        key={order.id}
                                    >
                                        <div className="flex items-center justify-between">
                                            <Link className="text-xs font-semibold text-[var(--garage-accent)] hover:underline" href={`/orders/${order.id}`}>
                                                {order.number}
                                            </Link>
                                            <span className="text-xs font-semibold">
                                                ₸
                                                {order.total.toLocaleString(
                                                    'ru-RU',
                                                )}
                                            </span>
                                        </div>
                                        <p className="mt-3 text-sm font-medium">
                                            {order.car.make} {order.car.model}
                                        </p>
                                        <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                            {order.client.name} ·{' '}
                                            {order.car.plate_number ||
                                                'номер не указан'}
                                        </p>
                                        <p className="mt-3 flex items-center gap-1 text-[11px] text-[var(--garage-text-secondary)]">
                                            <ClipboardList size={12} />
                                            {order.mechanic?.name ||
                                                'Мастер не назначен'}
                                        </p>
                                        {order.nextStatuses.length ? (
                                            <label className="mt-3 flex items-center gap-2 text-[10px] text-[var(--garage-text-muted)]">
                                                <ArrowRight size={12} />
                                                <select
                                                    aria-label={`Следующий этап для ${order.number}`}
                                                    className="h-8 min-w-0 flex-1 rounded border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-[10px] text-[var(--garage-text)]"
                                                    disabled={processingOrderId === order.id}
                                                    onChange={(event) => {
                                                        if (event.target.value) move(order, event.target.value);
                                                    }}
                                                    value=""
                                                >
                                                    <option value="">Следующий этап</option>
                                                    {order.nextStatuses.map((status) => (
                                                        <option key={status.slug} value={status.slug}>{status.name}</option>
                                                    ))}
                                                </select>
                                            </label>
                                        ) : <p className="mt-3 text-[10px] text-[var(--garage-text-muted)]">Конечный этап</p>}
                                        {transitionErrors[order.id] && <p className="mt-2 text-[10px] leading-4 text-[var(--garage-error)]" role="alert">{transitionErrors[order.id]}</p>}
                                    </article>
                                ))}
                        </div>
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
