import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import AppointmentCard from './AppointmentCard';
import CalendarNavigation, { calendarHref, dateKey } from './CalendarNavigation';

type Appointment = {
    id: number;
    client_id: number;
    car_id: number;
    service_id: number;
    mechanic_id?: number | null;
    order_id?: number | null;
    start_at: string;
    end_at: string;
    status: string;
    client: { name: string };
    car: { make: string; model: string; plate_number?: string };
    service: { name: string };
    mechanic?: { name: string };
};

type Props = {
    appointments: { data: Appointment[] };
    date: string;
    filters: { q: string; status: string };
    appointmentStatuses: { slug: string; name: string }[];
    canCreateOrder: boolean;
};

export default function WeekCalendar({ appointments, date, filters, appointmentStatuses, canCreateOrder }: Props) {
    const anchor = new Date(`${date}T12:00:00`);
    const monday = new Date(anchor);
    monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7));
    const days = Array.from({ length: 7 }, (_, index) => {
        const day = new Date(monday);
        day.setDate(monday.getDate() + index);
        return day;
    });
    const statusLabel = (slug: string) => appointmentStatuses.find((status) => status.slug === slug)?.name ?? slug;

    const moveAppointment = (appointment: Appointment, targetDay: Date) => {
        const start = new Date(appointment.start_at);
        const end = new Date(appointment.end_at);
        const movedStart = new Date(targetDay);
        movedStart.setHours(start.getHours(), start.getMinutes(), 0, 0);
        const movedEnd = new Date(movedStart.getTime() + end.getTime() - start.getTime());
        router.put(`/appointments/${appointment.id}`, {
            client_id: appointment.client_id,
            car_id: appointment.car_id,
            service_id: appointment.service_id,
            mechanic_id: appointment.mechanic_id || '',
            status: appointment.status,
            start_at: toLocalInput(movedStart),
            end_at: toLocalInput(movedEnd),
        }, { preserveScroll: true });
    };

    return (
        <AppLayout>
            <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Рабочее пространство</p>
                    <h1 className="mt-2 text-2xl font-semibold">Заявки</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Недельная загрузка. Перенесите карточку в другой день, чтобы изменить дату визита.</p>
                </div>
                <Link className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[var(--garage-accent)] px-3.5 text-xs font-semibold text-[var(--garage-background)]" href={calendarHref('day', date, filters, true)}><Plus size={15} />Новая заявка</Link>
            </header>

            <CalendarNavigation date={date} filters={filters} view="week" />

            <section aria-label="Заявки по дням недели" className="mt-5 overflow-x-auto rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="grid min-w-[980px] grid-cols-7 divide-x divide-[var(--garage-border)]">
                    {days.map((day) => {
                        const key = dateKey(day);
                        const items = appointments.data.filter((item) => dateKey(new Date(item.start_at)) === key);
                        const isToday = key === dateKey(new Date());
                        return (
                            <div className="min-h-[440px] transition-colors hover:bg-[var(--garage-surface-secondary)]" key={key} onDragOver={(event) => event.preventDefault()} onDrop={(event) => {
                                const appointment = appointments.data.find((item) => item.id === Number(event.dataTransfer.getData('appointment-id')));
                                if (appointment) moveAppointment(appointment, day);
                            }}>
                                <Link className={`block border-b border-[var(--garage-border-subtle)] px-3 py-3 transition-colors hover:bg-[var(--garage-surface-secondary)] ${isToday ? 'bg-[rgba(184,255,61,0.06)]' : ''}`} href={calendarHref('day', key, filters)}>
                                    <p className="text-[10px] uppercase text-[var(--garage-text-muted)]">{day.toLocaleDateString('ru-RU', { weekday: 'short' })}</p>
                                    <p className={`mt-1 text-lg font-semibold ${isToday ? 'text-[var(--garage-accent)]' : ''}`}>{day.getDate()}</p>
                                    <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{items.length} {plural(items.length)}</p>
                                </Link>
                                <div className="space-y-2 p-2">
                                    {items.length ? items.map((appointment) => (
                                        <AppointmentCard appointment={appointment} canCreateOrder={canCreateOrder} compact draggable key={appointment.id} onDragStart={(event) => event.dataTransfer.setData('appointment-id', String(appointment.id))} statusLabel={statusLabel(appointment.status)} />
                                    )) : (
                                        <Link className="grid min-h-20 place-items-center rounded-md border border-dashed border-[var(--garage-border)] text-[10px] text-[var(--garage-text-muted)] transition-colors hover:border-[var(--garage-text-muted)] hover:text-[var(--garage-text)]" href={calendarHref('day', key, filters, true)}>Свободно · добавить</Link>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </section>
        </AppLayout>
    );
}

function toLocalInput(value: Date) {
    const pad = (part: number) => String(part).padStart(2, '0');
    return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}T${pad(value.getHours())}:${pad(value.getMinutes())}`;
}

function plural(count: number) {
    const remainder = count % 10;
    const tens = count % 100;
    if (remainder === 1 && tens !== 11) return 'заявка';
    if (remainder >= 2 && remainder <= 4 && (tens < 12 || tens > 14)) return 'заявки';
    return 'заявок';
}