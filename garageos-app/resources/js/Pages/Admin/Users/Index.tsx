import { Form, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

type AdminUser = { id: number; name: string; email: string; organization: string; organization_id: number; role: string; status: string; last_login_at?: string | null; created_at?: string | null; can_manage: boolean };
type Props = {
    users: { data: AdminUser[]; links?: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: { search?: string; organization_id?: string; role?: string; status?: string };
    organizations: { id: number; name: string }[];
    roles: Record<string, string>;
};

const inputClass = 'h-9 min-w-0 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-xs text-[var(--garage-text)]';
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('ru-RU', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';

export default function Index({ users, filters, organizations, roles }: Props) {
    return <AdminLayout title="Пользователи">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Accounts</p><h1 className="mt-1 text-2xl font-semibold">Пользователи</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Учётные записи организаций и доступ к GarageOS.</p></div><span className="text-xs text-[var(--garage-text-muted)]">Всего: {users.total}</span></div>
        <Form action="/admin/users" method="get" className="mt-5 grid gap-2 border-y border-[var(--garage-border)] py-3 sm:grid-cols-2 lg:grid-cols-5">
            <Field label="Поиск"><input className={inputClass} defaultValue={filters.search} name="search" placeholder="Имя или email" /></Field>
            <Field label="Организация"><select className={inputClass} defaultValue={filters.organization_id || ''} name="organization_id"><option value="">Все организации</option>{organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}</select></Field>
            <Field label="Роль"><select className={inputClass} defaultValue={filters.role || ''} name="role"><option value="">Все роли</option>{Object.entries(roles).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field>
            <Field label="Статус"><select className={inputClass} defaultValue={filters.status || ''} name="status"><option value="">Все</option><option value="active">Активен</option><option value="inactive">Отключён</option></select></Field>
            <button className="h-9 self-end border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]" type="submit">Фильтровать</button>
        </Form>

        <section className="mt-4 border-t border-[var(--garage-border)]">
            <div className="hidden grid-cols-[1.15fr_1fr_0.7fr_0.7fr_0.9fr_auto] gap-3 border-b border-[var(--garage-border)] py-2 text-[10px] uppercase text-[var(--garage-text-muted)] lg:grid"><span>Пользователь</span><span>Организация</span><span>Роль</span><span>Статус</span><span>Последний вход</span><span>Действия</span></div>
            {users.data.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{users.data.map((user) => <article className="grid gap-3 py-4 lg:grid-cols-[1.15fr_1fr_0.7fr_0.7fr_0.9fr_auto] lg:items-center" key={user.id}>
                <div><p className="text-xs font-medium">{user.name}</p><p className="mt-1 break-all text-[10px] text-[var(--garage-text-muted)]">{user.email}</p></div>
                <Link className="text-xs hover:text-[var(--garage-accent)]" href={`/admin/organizations/${user.organization_id}`}>{user.organization}</Link>
                <span className="text-xs">{roles[user.role] || user.role}</span>
                <span className={`w-fit border px-2 py-1 text-[10px] ${user.status === 'active' ? 'border-[rgba(114,224,106,0.3)] text-[var(--garage-success)]' : 'border-[rgba(240,100,100,0.3)] text-[var(--garage-error)]'}`}>{user.status === 'active' ? 'Активен' : 'Отключён'}</span>
                <div className="text-[10px] text-[var(--garage-text-muted)]">{formatDate(user.last_login_at)}<p className="mt-1">Создан: {formatDate(user.created_at)}</p></div>
                {user.can_manage ? <details className="min-w-0"><summary className="cursor-pointer text-[11px] text-[var(--garage-text-secondary)]">Управление</summary><div className="mt-2 grid gap-2 border-l border-[var(--garage-border)] pl-3"><Form action={`/admin/users/${user.id}/action`} method="post" className="flex flex-wrap items-end gap-2"><input name="action" type="hidden" value="change_role" /><Field label="Изменить роль"><select className={inputClass} defaultValue={user.role} name="role">{Object.entries(roles).filter(([role]) => role !== 'owner').map(([role, label]) => <option key={role} value={role}>{label}</option>)}</select></Field><button className="h-9 border border-[var(--garage-border)] px-2 text-[10px]" type="submit">Сохранить роль</button></Form><Form action={`/admin/users/${user.id}/action`} method="post" onSubmit={(event) => { if (user.status === 'active' && !window.confirm(`Отключить доступ пользователя ${user.name}?`)) event.preventDefault(); }}><input name="action" type="hidden" value="set_status" /><input name="status" type="hidden" value={user.status === 'active' ? 'inactive' : 'active'} /><button className="h-8 border border-[var(--garage-border)] px-2 text-[10px]" type="submit">{user.status === 'active' ? 'Отключить' : 'Активировать'}</button></Form></div></details> : <span className="text-[10px] text-[var(--garage-text-muted)]">Владелец организации</span>}
            </article>)}</div> : <div className="grid min-h-40 place-items-center text-center text-xs text-[var(--garage-text-muted)]">Пользователей по выбранным условиям нет.</div>}
            {users.links && <div className="flex flex-wrap gap-1 py-3">{users.links.map((link) => link.url ? <Link className={`border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</div>}
        </section>
    </AdminLayout>;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}