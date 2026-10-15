import { Form, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

type Event = { id: number; actor: string; actor_email?: string; action: string; target_type?: string | null; target_id?: number | null; organization: string; organization_id?: number | null; metadata?: Record<string, unknown> | null; ip?: string | null; user_agent?: string | null; created_at?: string | null };
type Props = {
    events: { data: Event[]; links?: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: { search?: string; action?: string; organization_id?: string; user_id?: string; from?: string; to?: string };
    organizations: { id: number; name: string }[];
};

const inputClass = 'h-9 min-w-0 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-xs text-[var(--garage-text)]';
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('ru-RU', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';
const actionLabels: Record<string, string> = {
    'admin.login': 'Вход platform admin', 'admin.logout': 'Выход platform admin',
    'organization.created_by_platform_admin': 'Организация создана', 'organization.status_changed': 'Статус организации изменён', 'organization.archived': 'Организация архивирована',
    'subscription.trial_extended': 'Trial продлён', 'subscription.plan_activated': 'Тариф активирован', 'subscription.plan_change_rejected': 'Заявка отклонена',
    'saas.payment.created': 'SaaS-платёж создан', 'saas.payment.refunded': 'SaaS-платёж возвращён', 'saas.payment.status_changed': 'Статус SaaS-платежа изменён',
    'saas.revenue.created': 'Доход GarageOS записан', 'saas.expense.created': 'Расход GarageOS записан',
    'user.role_changed': 'Роль пользователя изменена', 'user.status_changed': 'Статус пользователя изменён',
};

export default function Index({ events, filters, organizations }: Props) {
    return <AdminLayout title="Журнал аудита">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Security & operations</p><h1 className="mt-1 text-2xl font-semibold">Журнал аудита</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Действия платформы и организаций, актор, источник и метаданные.</p></div><span className="text-xs text-[var(--garage-text-muted)]">Событий: {events.total}</span></div>
        <Form action="/admin/audit-logs" method="get" className="mt-5 grid gap-2 border-y border-[var(--garage-border)] py-3 sm:grid-cols-2 xl:grid-cols-6">
            <Field label="Поиск"><input className={inputClass} defaultValue={filters.search} name="search" placeholder="Актор, организация, объект" /></Field>
            <Field label="Действие"><input className={inputClass} defaultValue={filters.action} name="action" placeholder="Например saas.payment" /></Field>
            <Field label="Организация"><select className={inputClass} defaultValue={filters.organization_id || ''} name="organization_id"><option value="">Все, включая платформу</option>{organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}</select></Field>
            <Field label="ID актора"><input className={inputClass} defaultValue={filters.user_id} min="1" name="user_id" type="number" /></Field>
            <Field label="С даты"><input className={inputClass} defaultValue={filters.from} name="from" type="date" /></Field>
            <div className="flex items-end gap-2"><Field label="По дату"><input className={inputClass} defaultValue={filters.to} name="to" type="date" /></Field><button className="h-9 shrink-0 border border-[var(--garage-border)] px-3 text-xs" type="submit">Найти</button></div>
        </Form>

        <section className="mt-4 border-t border-[var(--garage-border)]"><div className="hidden grid-cols-[1fr_1fr_0.8fr_0.8fr_auto] gap-3 border-b border-[var(--garage-border)] py-2 text-[10px] uppercase text-[var(--garage-text-muted)] lg:grid"><span>Действие / актор</span><span>Цель</span><span>Организация</span><span>Время / IP</span><span>Данные</span></div>
            {events.data.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{events.data.map((event) => <article className="grid gap-3 py-4 lg:grid-cols-[1fr_1fr_0.8fr_0.8fr_auto] lg:items-start" key={event.id}>
                <div><p className="text-xs font-medium">{actionLabels[event.action] || event.action}</p><p className="mt-1 break-all text-[10px] text-[var(--garage-text-muted)]">{event.actor}{event.actor_email ? ` · ${event.actor_email}` : ''}</p></div>
                <p className="break-all text-xs text-[var(--garage-text-secondary)]">{event.target_type || '—'}{event.target_id ? ` #${event.target_id}` : ''}</p>
                <div>{event.organization_id ? <Link className="text-xs hover:text-[var(--garage-accent)]" href={`/admin/organizations/${event.organization_id}`}>{event.organization}</Link> : <span className="text-xs text-[var(--garage-text-muted)]">{event.organization}</span>}</div>
                <div className="text-[10px] text-[var(--garage-text-muted)]"><p>{formatDate(event.created_at)}</p><p className="mt-1">{event.ip || 'IP не записан'}</p></div>
                <details className="max-w-sm"><summary className="cursor-pointer text-[10px] text-[var(--garage-text-secondary)]">Metadata / User-Agent</summary><div className="mt-2 max-w-full space-y-2"><pre className="max-h-48 overflow-auto whitespace-pre-wrap break-all border border-[var(--garage-border)] bg-[var(--garage-surface)] p-2 text-[10px] text-[var(--garage-text-secondary)]">{event.metadata ? JSON.stringify(event.metadata, null, 2) : 'Метаданные отсутствуют'}</pre><p className="break-all text-[10px] text-[var(--garage-text-muted)]">{event.user_agent || 'User-Agent не записан'}</p></div></details>
            </article>)}</div> : <div className="grid min-h-44 place-items-center text-center text-xs text-[var(--garage-text-muted)]">Событий по выбранным условиям нет.</div>}
            {events.links && <div className="flex flex-wrap gap-1 py-3">{events.links.map((link) => link.url ? <Link className={`border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</div>}
        </section>
    </AdminLayout>;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}