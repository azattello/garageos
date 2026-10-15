import { CarFront, ClipboardList, Eye, ShieldCheck, Users, Wrench } from 'lucide-react';

type Props = {
    organization: { name: string; phone?: string; email?: string };
    metrics: { clients: number; orders: number; services: number; users: number };
    orders: { number: string; client?: string; vehicle?: string; status?: string; total: number }[];
};

const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;

export default function Index({ organization, metrics, orders }: Props) {
    const cards = [
        { label: 'Клиенты', value: metrics.clients, icon: Users },
        { label: 'Заказ-наряды', value: metrics.orders, icon: ClipboardList },
        { label: 'Услуги', value: metrics.services, icon: Wrench },
        { label: 'Сотрудники', value: metrics.users, icon: Users },
    ];

    return (
        <div className="min-h-screen bg-[var(--garage-background)] text-[var(--garage-text)]">
            <header className="border-b border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="mx-auto flex max-w-[1280px] items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <div className="flex items-center gap-3"><span className="grid size-9 place-items-center rounded-lg bg-[var(--garage-accent)] font-bold text-[var(--garage-background)]">G</span><div><p className="text-sm font-semibold">GarageOS</p><p className="text-[11px] text-[var(--garage-text-muted)]">{organization.name}</p></div></div>
                    <span className="inline-flex items-center gap-2 rounded-full border border-[rgba(98,168,255,0.3)] bg-[rgba(98,168,255,0.08)] px-3 py-1.5 text-[11px] font-medium text-[var(--garage-info)]"><Eye size={14} />DEMO MODE · Только просмотр</span>
                </div>
            </header>

            <main className="mx-auto max-w-[1280px] px-4 py-8 sm:px-6 lg:py-12">
                <section className="max-w-2xl"><p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-accent)]">Операционная система автосервиса</p><h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Посмотрите, как GarageOS держит сервис под контролем</h1><p className="mt-4 text-sm leading-6 text-[var(--garage-text-secondary)]">Это read-only демо-организация с реальными связанными данными: клиенты, автомобили, услуги и заказ-наряды в одном рабочем пространстве.</p></section>

                <section className="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{cards.map(({ label, value, icon: Icon }) => <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4" key={label}><div className="flex items-center justify-between"><p className="text-xs text-[var(--garage-text-secondary)]">{label}</p><Icon className="text-[var(--garage-text-muted)]" size={17} /></div><p className="mt-3 text-2xl font-semibold">{value}</p></article>)}</section>

                <section className="mt-6 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]"><div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5"><div><h2 className="text-sm font-semibold">Последние заказ-наряды</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Данные доступны только для просмотра</p></div><ClipboardList className="text-[var(--garage-text-muted)]" size={17} /></div><div className="divide-y divide-[var(--garage-border-subtle)]">{orders.map((order) => <div className="grid gap-3 px-4 py-4 sm:grid-cols-[110px_1fr_1fr_auto] sm:items-center" key={order.number}><p className="text-sm font-semibold">{order.number}</p><div><p className="text-sm">{order.client}</p><p className="mt-1 flex items-center gap-1 text-xs text-[var(--garage-text-muted)]"><CarFront size={13} />{order.vehicle}</p></div><span className="w-fit rounded-full border border-[rgba(98,168,255,0.25)] bg-[rgba(98,168,255,0.08)] px-2 py-1 text-[10px] text-[var(--garage-info)]">{order.status}</span><p className="text-sm font-medium sm:text-right">{money(order.total)}</p></div>)}</div></section>

                <footer className="mt-8 flex flex-col gap-3 border-t border-[var(--garage-border-subtle)] pt-5 text-xs text-[var(--garage-text-muted)] sm:flex-row sm:items-center sm:justify-between"><span className="flex items-center gap-2"><ShieldCheck size={14} className="text-[var(--garage-success)]" />Демо не изменяет данные</span><span>{organization.email} · {organization.phone}</span></footer>
            </main>
        </div>
    );
}
