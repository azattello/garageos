import { Form, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

type Organization = {
    id: number; name: string; slug: string; status: string; email?: string; phone?: string;
    owner?: { name: string; email: string; status: string; last_login_at?: string | null } | null;
    subscription?: { plan: string; plan_name: string; status: string; starts_at?: string | null; ends_at?: string | null; trial_ends_at?: string | null } | null;
    counts: { users: number; clients: number; cars: number; appointments: number; orders: number };
    created_at?: string | null;
};
type Props = {
    organizations: { data: Organization[]; links?: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: { search?: string; status?: string; plan?: string; subscription_status?: string; from?: string; to?: string };
    plans: { key: string; name: string; price: number; max_users: number }[];
};

const inputClass = 'h-9 min-w-0 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-xs text-[var(--garage-text)]';
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('ru-RU', { dateStyle: 'medium' }).format(new Date(value)) : '—';
const statusLabel: Record<string, string> = { active: 'Активна', blocked: 'Заблокирована', archived: 'Архив' };
const subscriptionStatus: Record<string, string> = { trialing: 'Trial', active: 'Активна', expired: 'Истекла', suspended: 'Приостановлена', cancelled: 'Отменена' };

export default function Index({ organizations, filters, plans }: Props) {
    return <AdminLayout title="Организации">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Tenants</p><h1 className="mt-1 text-2xl font-semibold">Организации</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Клиенты платформы, владельцы и состояние подписки.</p></div><span className="text-xs text-[var(--garage-text-muted)]">Всего: {organizations.total}</span></div>

        <details className="mt-5 border border-[var(--garage-border)] bg-[var(--garage-surface)]"><summary className="cursor-pointer list-none px-4 py-3 text-xs font-semibold">Создать организацию</summary><Form action="/admin/organizations" method="post" className="grid gap-3 border-t border-[var(--garage-border)] p-4 sm:grid-cols-2 xl:grid-cols-4">
            <Field label="Название организации"><input autoComplete="organization" className={inputClass} maxLength={160} name="name" required /></Field>
            <Field label="Имя владельца"><input autoComplete="name" className={inputClass} maxLength={120} name="owner_name" required /></Field>
            <Field label="Email владельца"><input autoComplete="email" className={inputClass} maxLength={255} name="owner_email" required type="email" /></Field>
            <Field label="Телефон"><input autoComplete="tel" className={inputClass} maxLength={50} name="phone" type="tel" /></Field>
            <Field label="Email организации"><input className={inputClass} maxLength={255} name="email" type="email" /></Field>
            <Field label="Тариф"><select className={inputClass} name="plan">{plans.map((plan) => <option key={plan.key} value={plan.key}>{plan.name} · {plan.price.toLocaleString('ru-RU')} ₸/мес. · до {plan.max_users} сотрудников</option>)}</select></Field>
            <Field label="Пробный период, дней"><input className={inputClass} defaultValue="14" max="365" min="1" name="trial_days" required type="number" /></Field>
            <Field label="Статус организации"><select className={inputClass} name="status"><option value="active">Активна</option><option value="blocked">Заблокирована</option></select></Field>
            <div className="flex items-end"><button className="h-9 bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" type="submit">Создать и отправить ссылку владельцу</button></div>
            <p className="self-end text-[10px] leading-4 text-[var(--garage-text-muted)] xl:col-span-3">Владелец задаст пароль по email. Организация и её стандартные настройки создаются через текущую регистрационную логику.</p>
        </Form></details>

        <Form action="/admin/organizations" method="get" className="mt-5 grid gap-2 border-y border-[var(--garage-border)] py-3 sm:grid-cols-2 lg:grid-cols-6">
            <Field label="Поиск"><input className={inputClass} defaultValue={filters.search} name="search" placeholder="Название, владелец, email" /></Field>
            <Field label="Статус организации"><select className={inputClass} defaultValue={filters.status || ''} name="status"><option value="">Все</option>{Object.entries(statusLabel).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field>
            <Field label="Тариф"><select className={inputClass} defaultValue={filters.plan || ''} name="plan"><option value="">Все тарифы</option>{plans.map((plan) => <option key={plan.key} value={plan.key}>{plan.name}</option>)}</select></Field>
            <Field label="Подписка"><select className={inputClass} defaultValue={filters.subscription_status || ''} name="subscription_status"><option value="">Любой статус</option>{Object.entries(subscriptionStatus).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field>
            <Field label="Создана с"><input className={inputClass} defaultValue={filters.from} name="from" type="date" /></Field>
            <div className="flex items-end gap-2"><Field label="По"><input className={inputClass} defaultValue={filters.to} name="to" type="date" /></Field><button className="h-9 shrink-0 border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]" type="submit">Найти</button></div>
        </Form>

        <section className="mt-4 border-t border-[var(--garage-border)]">
            <div className="hidden grid-cols-[1.35fr_1fr_0.9fr_1.05fr_0.8fr_auto] gap-3 border-b border-[var(--garage-border)] py-2 text-[10px] uppercase text-[var(--garage-text-muted)] xl:grid"><span>Организация / владелец</span><span>Подписка</span><span>Использование</span><span>Контакты / создана</span><span>Статус</span><span>Действия</span></div>
            {organizations.data.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{organizations.data.map((organization) => {
                const nextStatus = organization.status === 'blocked' ? 'active' : 'blocked';
                return <article className="grid gap-3 py-4 xl:grid-cols-[1.35fr_1fr_0.9fr_1.05fr_0.8fr_auto] xl:items-start" key={organization.id}>
                    <div><Link className="text-xs font-semibold hover:text-[var(--garage-accent)]" href={`/admin/organizations/${organization.id}`}>{organization.name}</Link><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{organization.slug}</p><p className="mt-2 text-xs">{organization.owner?.name || 'Владелец не найден'}</p><p className="mt-1 break-all text-[10px] text-[var(--garage-text-muted)]">{organization.owner?.email || '—'} · вход {formatDate(organization.owner?.last_login_at)}</p></div>
                    <div><p className="text-xs font-medium">{organization.subscription?.plan_name || 'Тариф не назначен'}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{subscriptionStatus[organization.subscription?.status || ''] || organization.subscription?.status || 'Нет подписки'}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{organization.subscription?.status === 'trialing' ? `Trial до ${formatDate(organization.subscription.trial_ends_at)}` : `Оплачена до ${formatDate(organization.subscription?.ends_at)}`}</p></div>
                    <div className="grid grid-cols-3 gap-x-3 gap-y-1 text-[10px] text-[var(--garage-text-secondary)] sm:grid-cols-5 xl:grid-cols-3"><Count label="Сотр." value={organization.counts.users} /><Count label="Клиенты" value={organization.counts.clients} /><Count label="Авто" value={organization.counts.cars} /><Count label="Записи" value={organization.counts.appointments} /><Count label="Заказы" value={organization.counts.orders} /></div>
                    <div className="text-[10px] text-[var(--garage-text-secondary)]"><p className="break-all">{organization.email || 'Email не указан'}</p><p className="mt-1">{organization.phone || 'Телефон не указан'}</p><p className="mt-2 text-[var(--garage-text-muted)]">Создана {formatDate(organization.created_at)}</p></div>
                    <span className={`w-fit border px-2 py-1 text-[10px] ${organization.status === 'active' ? 'border-[rgba(114,224,106,0.3)] text-[var(--garage-success)]' : organization.status === 'archived' ? 'border-[var(--garage-border)] text-[var(--garage-text-muted)]' : 'border-[rgba(240,100,100,0.3)] text-[var(--garage-error)]'}`}>{statusLabel[organization.status] || organization.status}</span>
                    <div className="flex flex-wrap gap-1.5"><Form action={`/admin/organizations/${organization.id}/trial`} method="post"><input name="days" type="hidden" value="14" /><button className="h-8 border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-text-secondary)]" type="submit">+14д trial</button></Form>{organization.status !== 'archived' && <Form action={`/admin/organizations/${organization.id}/status`} method="post" onSubmit={(event) => { if (nextStatus === 'blocked' && !window.confirm(`Заблокировать организацию «${organization.name}»?`)) event.preventDefault(); }}><input name="status" type="hidden" value={nextStatus} /><button className="h-8 border border-[var(--garage-border)] px-2 text-[10px]" type="submit">{organization.status === 'blocked' ? 'Активировать' : 'Блокировать'}</button></Form>}{organization.status !== 'archived' ? <Form action={`/admin/organizations/${organization.id}/status`} method="post" onSubmit={(event) => { if (!window.confirm(`Архивировать «${organization.name}»? Данные сохранятся, но пользователи потеряют доступ.`)) event.preventDefault(); }}><input name="status" type="hidden" value="archived" /><button className="h-8 border border-[rgba(240,100,100,0.3)] px-2 text-[10px] text-[var(--garage-error)]" type="submit">Архивировать</button></Form> : <Form action={`/admin/organizations/${organization.id}/status`} method="post"><input name="status" type="hidden" value="active" /><button className="h-8 border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-success)]" type="submit">Восстановить</button></Form>}</div>
                </article>;
            })}</div> : <div className="grid min-h-44 place-items-center text-center text-xs text-[var(--garage-text-muted)]">Организаций по выбранным условиям нет.</div>}
            {organizations.links && <div className="flex flex-wrap gap-1 py-3">{organizations.links.map((link) => link.url ? <Link className={`border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</div>}
        </section>
    </AdminLayout>;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}

function Count({ label, value }: { label: string; value: number }) {
    return <span><span className="text-[var(--garage-text-muted)]">{label} </span>{value}</span>;
}