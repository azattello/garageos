import { Link } from '@inertiajs/react';
import { CalendarDays, Plus } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import CalendarNavigation, { calendarHref, dateKey } from './CalendarNavigation';

type Props = {
    date: string;
    dayCounts: Record<string, number>;
    periodTotal: number;
    filters: { q: string; status: string };
};

const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

export default function Month({ date, dayCounts, periodTotal, filters }: Props) {
    const anchor = new Date(`${date}T12:00:00`);
    const first = new Date(anchor.getFullYear(), anchor.getMonth(), 1, 12);
    const offset = (first.getDay() + 6) % 7;
    const dayCount = new Date(anchor.getFullYear(), anchor.getMonth() + 1, 0).getDate();
    const cellCount = Math.ceil((offset + dayCount) / 7) * 7;
    const cells = Array.from({ length: cellCount }, (_, index) => {
        const day = new Date(first);
        day.setDate(index - offset + 1);
        return day;
    });
    const busiestDay = Object.entries(dayCounts).sort((left, right) => right[1] - left[1])[0];

    return (
        <AppLayout>
            <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Рабочее пространство</p>
                    <h1 className="mt-2 text-2xl font-semibold">Заявки</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Обзор загрузки по дням. Откройте день, чтобы посмотреть записи и действия.</p>
                </div>
                <Link className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[var(--garage-accent)] px-3.5 text-xs font-semibold text-[var(--garage-background)]" href={calendarHref('day', date, filters, true)}><Plus size={15} />Новая заявка</Link>
            </header>

            <CalendarNavigation date={date} filters={filters} view="month" />

            <section aria-label="Итоги месяца" className="mt-5 grid gap-3 sm:grid-cols-2">
                <article className="rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                    <p className="text-xs text-[var(--garage-text-muted)]">Заявок за месяц</p>
                    <p className="mt-2 text-2xl font-semibold tabular-nums">{periodTotal}</p>
                </article>
                <article className="rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                    <p className="text-xs text-[var(--garage-text-muted)]">Самый загруженный день</p>
                    <p className="mt-2 text-sm font-semibold">
                        {busiestDay
                            ? `${new Date(`${busiestDay[0]}T12:00:00`).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })} · ${busiestDay[1]} ${plural(busiestDay[1])}`
                            : 'Пока нет записей'}
                    </p>
                </article>
            </section>

            <section aria-label="Календарь на месяц" className="mt-4 overflow-hidden rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="grid grid-cols-7 border-b border-[var(--garage-border)] bg-[var(--garage-surface-secondary)]">
                    {weekdays.map((day) => <div className="py-2 text-center text-[10px] font-medium uppercase text-[var(--garage-text-muted)] sm:py-3 sm:text-xs" key={day}>{day}</div>)}
                </div>
                <div className="grid grid-cols-7">
                    {cells.map((day) => {
                        const key = dateKey(day);
                        const count = dayCounts[key] ?? 0;
                        const inMonth = day.getMonth() === anchor.getMonth();
                        const isToday = key === dateKey(new Date());
                        return (
                            <Link aria-label={`${day.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })}: ${count} ${plural(count)}`} className={`flex min-h-20 flex-col border-b border-r border-[var(--garage-border-subtle)] p-1.5 transition-colors hover:bg-[var(--garage-surface-secondary)] sm:min-h-28 sm:p-2.5 ${inMonth ? '' : 'bg-[var(--garage-background)] text-[var(--garage-text-muted)]'}`} href={calendarHref('day', key, filters)} key={key}>
                                <span className={`grid size-7 place-items-center rounded-full text-xs ${isToday ? 'bg-[var(--garage-accent)] font-bold text-[var(--garage-background)]' : inMonth ? 'font-medium text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)]'}`}>{day.getDate()}</span>
                                {count > 0 && <span className="mt-1.5 inline-flex w-fit max-w-full items-center gap-1 rounded bg-[rgba(184,255,61,0.1)] px-1.5 py-1 text-[9px] font-semibold text-[var(--garage-accent)] sm:mt-2 sm:px-2 sm:text-[11px]">
                                    <CalendarDays className="hidden shrink-0 sm:block" size={12} />
                                    <span className="truncate">{count} <span className="hidden sm:inline">{plural(count)}</span><span className="sm:hidden">зап.</span></span>
                                </span>}
                                {inMonth && count === 0 && <span className="mt-2 hidden text-[10px] text-[var(--garage-text-muted)] sm:block">Свободно</span>}
                            </Link>
                        );
                    })}
                </div>
            </section>
        </AppLayout>
    );
}

function plural(count: number) {
    const remainder = count % 10;
    const tens = count % 100;
    if (remainder === 1 && tens !== 11) return 'заявка';
    if (remainder >= 2 && remainder <= 4 && (tens < 12 || tens > 14)) return 'заявки';
    return 'заявок';
}