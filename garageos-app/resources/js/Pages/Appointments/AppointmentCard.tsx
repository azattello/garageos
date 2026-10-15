import { Form, Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';

type AppointmentCardData = {
    id: number;
    order_id?: number | null;
    start_at: string;
    end_at: string;
    status: string;
    client: { name: string };
    car: { make: string; model: string; plate_number?: string };
    service: { name: string };
    mechanic?: { name: string };
};

export default function AppointmentCard({
    appointment,
    statusLabel,
    canCreateOrder = false,
    compact = false,
    draggable = false,
    onDragStart,
}: {
    appointment: AppointmentCardData;
    statusLabel: string;
    canCreateOrder?: boolean;
    compact?: boolean;
    draggable?: boolean;
    onDragStart?: React.DragEventHandler<HTMLAnchorElement>;
}) {
    const terminal = ['cancelled', 'completed'].includes(appointment.status);

    return (
        <article className={`group relative border border-[var(--garage-border)] bg-[var(--garage-surface)] transition-colors hover:border-[var(--garage-text-muted)] ${compact ? 'rounded-md' : 'rounded-lg'}`}>
            <div className={`grid gap-3 ${compact ? 'p-2.5' : 'items-start p-3 sm:grid-cols-[68px_minmax(0,1fr)_auto] sm:items-center sm:gap-4 sm:px-4 sm:py-3.5'}`}>
                <Link aria-label={`Открыть заявку: ${appointment.client.name}`} className={`${compact ? 'flex items-center justify-between gap-2' : 'flex items-center gap-2 sm:block sm:text-center'}`} draggable={draggable} href={`/appointments/${appointment.id}/edit`} onDragStart={onDragStart}>
                    <time className="text-sm font-semibold tabular-nums text-[var(--garage-text)]" dateTime={appointment.start_at}>{formatTime(appointment.start_at)}</time>
                    {!compact && <span className="text-[11px] text-[var(--garage-text-muted)] sm:mt-1 sm:block">до {formatTime(appointment.end_at)}</span>}
                    {compact && <span className={`max-w-24 truncate rounded-full px-2 py-0.5 text-[9px] ${statusTone(appointment.status)}`}>{statusLabel}</span>}
                </Link>

                <Link className="min-w-0" draggable={draggable} href={`/appointments/${appointment.id}/edit`} onDragStart={onDragStart}>
                    <span className="flex min-w-0 flex-wrap items-center gap-2">
                        <span className="truncate text-sm font-semibold text-[var(--garage-text)]">{appointment.client.name}</span>
                        {!compact && <span className={`rounded-full px-2 py-0.5 text-[10px] ${statusTone(appointment.status)}`}>{statusLabel}</span>}
                    </span>
                    <span className="mt-1 block truncate text-xs text-[var(--garage-text-secondary)]">{appointment.car.make} {appointment.car.model}{appointment.car.plate_number ? ` · ${appointment.car.plate_number}` : ''}</span>
                    <span className="mt-1 flex min-w-0 flex-wrap gap-x-2 text-[11px] text-[var(--garage-text-muted)]">
                        <span className="truncate">{appointment.service.name}</span>
                        <span className="truncate">Мастер: {appointment.mechanic?.name || 'не назначен'}</span>
                    </span>
                </Link>

                <details className={`${compact ? 'absolute right-1 top-1' : 'absolute right-2 top-2 sm:static sm:justify-self-end'}`}>
                    <summary aria-label="Быстрые действия" className="grid size-8 cursor-pointer list-none place-items-center rounded-md text-[var(--garage-text-muted)] outline-none transition-colors hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)] focus-visible:ring-2 focus-visible:ring-[var(--garage-accent)] [&::-webkit-details-marker]:hidden">
                        <MoreHorizontal size={17} />
                    </summary>
                    <div className="absolute right-0 top-9 z-20 min-w-48 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-1 shadow-xl">
                        <Link className="block rounded px-3 py-2 text-xs text-[var(--garage-text)] hover:bg-[var(--garage-surface-secondary)]" href={`/appointments/${appointment.id}/edit`}>Открыть и изменить</Link>
                        {appointment.order_id ? (
                            <Link className="block rounded px-3 py-2 text-xs text-[var(--garage-text)] hover:bg-[var(--garage-surface-secondary)]" href={`/orders/${appointment.order_id}`}>Открыть заказ-наряд</Link>
                        ) : canCreateOrder && !terminal ? (
                            <Form action={`/appointments/${appointment.id}/order`} method="post">
                                <button className="block w-full rounded px-3 py-2 text-left text-xs text-[var(--garage-accent)] hover:bg-[var(--garage-surface-secondary)]" type="submit">Принять автомобиль · создать заказ</button>
                            </Form>
                        ) : null}
                        {!terminal && <Form action={`/appointments/${appointment.id}`} method="delete" onSubmit={(event) => {
                            if (!window.confirm('Отменить эту заявку?')) event.preventDefault();
                        }}>
                            <button className="block w-full rounded px-3 py-2 text-left text-xs text-[var(--garage-error)] hover:bg-[var(--garage-surface-secondary)]" type="submit">Отменить заявку</button>
                        </Form>}
                    </div>
                </details>
            </div>
        </article>
    );
}

function formatTime(value: string) {
    return new Date(value).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}

function statusTone(status: string) {
    if (status === 'completed') return 'bg-[rgba(114,224,106,0.12)] text-[var(--garage-success)]';
    if (status === 'cancelled') return 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text-muted)]';
    if (status === 'arrived') return 'bg-[rgba(184,255,61,0.12)] text-[var(--garage-accent)]';
    return 'bg-[rgba(98,168,255,0.1)] text-[var(--garage-info)]';
}