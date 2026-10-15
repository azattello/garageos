import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

type View = 'day' | 'week' | 'month';

export default function CalendarNavigation({
    view,
    date,
    filters,
}: {
    view: View;
    date: string;
    filters?: { q: string; status: string };
}) {
    const anchor = new Date(`${date}T12:00:00`);
    const previous = shift(anchor, view, -1);
    const next = shift(anchor, view, 1);
    const today = dateKey(new Date());
    const label = view === 'month'
        ? anchor.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' })
        : view === 'week'
          ? `${shortDate(startOfWeek(anchor))} — ${shortDate(endOfWeek(anchor))}`
          : anchor.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });

    return (
        <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-2">
                <Link aria-label="Предыдущий период" className="grid size-9 place-items-center rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] text-[var(--garage-text)] transition-colors hover:border-[var(--garage-text-muted)]" href={calendarHref(view, dateKey(previous), filters)}>
                    <ChevronLeft size={16} />
                </Link>
                <h2 className="min-w-0 text-sm font-semibold capitalize text-[var(--garage-text)] sm:min-w-48 sm:text-center">{label}</h2>
                <Link aria-label="Следующий период" className="grid size-9 place-items-center rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] text-[var(--garage-text)] transition-colors hover:border-[var(--garage-text-muted)]" href={calendarHref(view, dateKey(next), filters)}>
                    <ChevronRight size={16} />
                </Link>
                <Link className="ml-1 hidden h-9 items-center rounded-md border border-[var(--garage-border)] px-3 text-xs font-medium text-[var(--garage-text-secondary)] transition-colors hover:bg-[var(--garage-surface-secondary)] sm:inline-flex" href={calendarHref(view, today, filters)}>Сегодня</Link>
            </div>
            <nav aria-label="Период календаря" className="inline-flex w-fit rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-1">
                {(['day', 'week', 'month'] as const).map((option) => (
                    <Link aria-current={view === option ? 'page' : undefined} className={`rounded px-3 py-1.5 text-xs font-medium transition-colors ${view === option ? 'bg-[var(--garage-surface)] text-[var(--garage-text)] shadow-sm' : 'text-[var(--garage-text-muted)] hover:text-[var(--garage-text)]'}`} href={calendarHref(option, date, filters)} key={option}>
                        {option === 'day' ? 'День' : option === 'week' ? 'Неделя' : 'Месяц'}
                    </Link>
                ))}
            </nav>
        </div>
    );
}

export function calendarHref(view: View, date: string, filters?: { q: string; status: string }, create = false) {
    const params = new URLSearchParams({ view, date });
    if (filters?.q) params.set('q', filters.q);
    if (filters?.status) params.set('status', filters.status);
    if (create) params.set('create', '1');
    return `/appointments?${params.toString()}`;
}

export function dateKey(date: Date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function shift(date: Date, view: View, amount: number) {
    const result = new Date(date);
    if (view === 'month') {
        result.setDate(1);
        result.setMonth(result.getMonth() + amount);
    } else {
        result.setDate(result.getDate() + amount * (view === 'week' ? 7 : 1));
    }
    return result;
}

function startOfWeek(date: Date) {
    const result = new Date(date);
    result.setDate(result.getDate() - ((result.getDay() + 6) % 7));
    return result;
}

function endOfWeek(date: Date) {
    const result = startOfWeek(date);
    result.setDate(result.getDate() + 6);
    return result;
}

function shortDate(date: Date) {
    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}