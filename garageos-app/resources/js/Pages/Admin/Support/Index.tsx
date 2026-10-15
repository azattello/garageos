import { LifeBuoy } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';

type Props = { ticketFields: { key: string; label: string }[]; statuses: string[]; ticketingEnabled: boolean };

export default function Index({ ticketFields, statuses, ticketingEnabled }: Props) {
    return <AdminLayout title="Поддержка">
        <div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Customer operations</p><h1 className="mt-1 text-2xl font-semibold">Поддержка</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Основа для обработки обращений организаций GarageOS.</p></div>
        <section className="mt-7 grid min-h-56 place-items-center border-y border-[var(--garage-border)] py-8 text-center">
            <div className="max-w-md"><span className="mx-auto grid size-10 place-items-center border border-[var(--garage-border)] text-[var(--garage-info)]"><LifeBuoy size={18} /></span><h2 className="mt-4 text-sm font-semibold">{ticketingEnabled ? 'Обращения поддержки' : 'Ticketing пока не включён'}</h2><p className="mt-2 text-xs leading-5 text-[var(--garage-text-muted)]">{ticketingEnabled ? 'Обращения появятся здесь.' : 'Система пока не создаёт и не хранит tickets. Структура будущего обращения подготовлена, чтобы не вводить незавершённый workflow.'}</p></div>
        </section>
        <section className="mt-7 grid gap-6 md:grid-cols-2"><div className="border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Поля обращения</h2><ul className="mt-3 divide-y divide-[var(--garage-border-subtle)]">{ticketFields.map((field) => <li className="flex justify-between gap-3 py-2 text-xs" key={field.key}><span>{field.label}</span><code className="text-[10px] text-[var(--garage-text-muted)]">{field.key}</code></li>)}</ul></div><div className="border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Предусмотренные статусы</h2><ul className="mt-3 divide-y divide-[var(--garage-border-subtle)]">{statuses.map((status) => <li className="py-2 text-xs" key={status}>{status}</li>)}</ul></div></section>
    </AdminLayout>;
}