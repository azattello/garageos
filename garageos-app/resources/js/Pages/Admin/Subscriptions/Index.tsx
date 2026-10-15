import { Form, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

type Subscription = { id: number; organization: string; organization_id: number; organization_status: string; plan: string; plan_name: string; amount: number; status: string; starts_at?: string | null; ends_at?: string | null; trial_ends_at?: string | null; max_users: number };
type ChangeRequest = { id: number; organization: string; requester: string; current_plan: string; requested_plan: string; amount: number; currency: string; requested_at: string };
type Props = {
    subscriptions: { data: Subscription[]; links?: { url: string | null; label: string; active: boolean }[]; total: number };
    pendingRequests: { data: ChangeRequest[]; links?: { url: string | null; label: string; active: boolean }[] };
    filters: { search?: string; status?: string; plan?: string; organization_id?: string };
    plans: { key: string; name: string; price: number; max_users: number }[];
    organizations: { id: number; name: string }[];
};

const inputClass = 'h-8 min-w-0 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-[11px] text-[var(--garage-text)]';
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('ru-RU', { dateStyle: 'medium' }).format(new Date(value)) : '—';
const statusLabels: Record<string, string> = { active: 'Активна', trialing: 'Пробный период', expired: 'Истекла', suspended: 'Приостановлена', cancelled: 'Отменена' };
const money = (amount: number, currency = 'KZT') => `${amount.toLocaleString('ru-RU')} ${currency === 'KZT' ? '₸' : currency}`;

export default function Index({ subscriptions, pendingRequests, filters, plans, organizations }: Props) {
    return <AdminLayout title="Подписки">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Billing operations</p><h1 className="mt-1 text-2xl font-semibold">Подписки</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Управление периодом доступа, тарифом и ожидающими заявками.</p></div><p className="text-xs text-[var(--garage-text-muted)]">Всего: {subscriptions.total}</p></div>

        <section className="mt-6 border-t border-[var(--garage-border)] pt-4"><div className="flex items-center justify-between gap-3"><h2 className="text-sm font-semibold">Заявки на изменение тарифа</h2><span className="text-[10px] text-[var(--garage-text-muted)]">Подтверждение требует payment reference</span></div>
            {pendingRequests.data.length ? <div className="mt-3 divide-y divide-[var(--garage-border-subtle)]">{pendingRequests.data.map((request) => <div className="grid gap-3 py-4 xl:grid-cols-[1fr_1.1fr_1.5fr] xl:items-center" key={request.id}>
                <div><p className="text-xs font-medium">{request.organization}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{request.requester} · {formatDate(request.requested_at)}</p></div>
                <p className="text-xs">{request.current_plan} → <strong>{request.requested_plan}</strong><span className="ml-2 text-[var(--garage-text-muted)]">{money(request.amount, request.currency)} / мес.</span></p>
                <div className="flex flex-wrap gap-2"><Form action={`/admin/subscription-requests/${request.id}`} method="post" className="flex min-w-0 flex-1 gap-2"><input name="decision" type="hidden" value="approve" /><input className={`${inputClass} flex-1`} name="payment_reference" placeholder="Reference оплаты" required /><button className="h-8 bg-[var(--garage-success)] px-2 text-[10px] font-semibold text-[var(--garage-background)]" type="submit">Подтвердить</button></Form><Form action={`/admin/subscription-requests/${request.id}`} method="post" onSubmit={(event) => { if (!window.confirm(`Отклонить заявку организации «${request.organization}»?`)) event.preventDefault(); }}><input name="decision" type="hidden" value="reject" /><button className="h-8 border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-error)]" type="submit">Отклонить</button></Form></div>
            </div>)}</div> : <div className="mt-3 border-y border-[var(--garage-border-subtle)] py-6 text-center text-xs text-[var(--garage-text-muted)]">Ожидающих заявок нет.</div>}
            {pendingRequests.links && <Pagination links={pendingRequests.links} />}
        </section>

        <Form action="/admin/subscriptions" method="get" className="mt-7 grid gap-2 border-y border-[var(--garage-border)] py-3 sm:grid-cols-2 lg:grid-cols-5">
            <Field label="Поиск организации"><input className={inputClass} defaultValue={filters.search} name="search" /></Field>
            <Field label="Организация"><select className={inputClass} defaultValue={filters.organization_id || ''} name="organization_id"><option value="">Все</option>{organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}</select></Field>
            <Field label="Тариф"><select className={inputClass} defaultValue={filters.plan || ''} name="plan"><option value="">Все тарифы</option>{plans.map((plan) => <option key={plan.key} value={plan.key}>{plan.name}</option>)}</select></Field>
            <Field label="Статус"><select className={inputClass} defaultValue={filters.status || ''} name="status"><option value="">Все статусы</option>{Object.entries(statusLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field>
            <button className="h-8 self-end border border-[var(--garage-border)] px-3 text-[11px] text-[var(--garage-text-secondary)]" type="submit">Фильтровать</button>
        </Form>

        <section className="mt-4 border-t border-[var(--garage-border)]">
            <div className="hidden grid-cols-[1.1fr_0.9fr_0.8fr_0.9fr_1.8fr] gap-3 border-b border-[var(--garage-border)] py-2 text-[10px] uppercase text-[var(--garage-text-muted)] lg:grid"><span>Организация</span><span>Тариф / сумма</span><span>Статус</span><span>Период</span><span>Действия</span></div>
            {subscriptions.data.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{subscriptions.data.map((subscription) => <div className="grid gap-3 py-4 lg:grid-cols-[1.1fr_0.9fr_0.8fr_0.9fr_1.8fr] lg:items-start" key={subscription.id}>
                <div><Link className="text-xs font-medium hover:text-[var(--garage-accent)]" href={`/admin/organizations/${subscription.organization_id}`}>{subscription.organization}</Link><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{subscription.max_users} сотрудников · организация {subscription.organization_status}</p></div>
                <div><p className="text-xs">{subscription.plan_name}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{money(subscription.amount)} / мес.</p></div>
                <span className={`w-fit border px-2 py-1 text-[10px] ${subscription.status === 'active' ? 'border-[rgba(114,224,106,0.3)] text-[var(--garage-success)]' : subscription.status === 'expired' || subscription.status === 'cancelled' ? 'border-[rgba(240,100,100,0.3)] text-[var(--garage-error)]' : 'border-[rgba(245,196,81,0.3)] text-[var(--garage-warning)]'}`}>{statusLabels[subscription.status] || subscription.status}</span>
                <div className="text-[10px] text-[var(--garage-text-muted)]"><p>С {formatDate(subscription.starts_at)}</p><p className="mt-1">До {formatDate(subscription.ends_at || subscription.trial_ends_at)}</p></div>
                <details className="min-w-0"><summary className="cursor-pointer text-[11px] text-[var(--garage-text-secondary)]">Управление</summary><div className="mt-3 grid gap-3 border-l border-[var(--garage-border)] pl-3">
                    {subscription.status !== 'active' && <ActionForm subscription={subscription} action="activate" days paymentReference><span>Активировать</span></ActionForm>}
                    {subscription.status === 'active' && <><ActionForm subscription={subscription} action="extend" days><span>Продлить на</span></ActionForm><ActionForm subscription={subscription} action="suspend" confirm={`Приостановить доступ организации «${subscription.organization}»?`}><span>Приостановить</span></ActionForm></>}
                    {['suspended', 'expired', 'cancelled'].includes(subscription.status) && <ActionForm subscription={subscription} action="reactivate" days paymentReference><span>Возобновить</span></ActionForm>}
                    {!['cancelled', 'expired'].includes(subscription.status) && <ActionForm subscription={subscription} action="cancel" confirm={`Отменить подписку организации «${subscription.organization}»? Доступ будет закрыт.`}><span className="text-[var(--garage-error)]">Отменить</span></ActionForm>}
                    <Form action={`/admin/subscriptions/${subscription.id}/action`} method="post" className="grid gap-2 border-t border-[var(--garage-border-subtle)] pt-3 sm:grid-cols-2"><input name="action" type="hidden" value="change_plan" /><Field label="Новый тариф"><select className={inputClass} name="plan" required defaultValue="">{plans.map((plan) => <option key={plan.key} value={plan.key}>{plan.name} · {money(plan.price)}/мес.</option>)}</select></Field><Field label="Reference оплаченного SaaS-платежа"><input className={inputClass} name="payment_reference" required /></Field><Field label="Примечание"><input className={inputClass} maxLength={1000} name="note" /></Field><button className="h-8 self-end border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-text-secondary)]" type="submit">Сменить тариф</button></Form>
                </div></details>
            </div>)}</div> : <div className="grid min-h-40 place-items-center text-xs text-[var(--garage-text-muted)]">Подписок по выбранным условиям нет.</div>}
            {subscriptions.links && <Pagination links={subscriptions.links} />}
        </section>
    </AdminLayout>;
}

function ActionForm({ subscription, action, days = false, paymentReference = false, confirm, children }: { subscription: Subscription; action: string; days?: boolean; paymentReference?: boolean; confirm?: string; children: React.ReactNode }) {
    return <Form action={`/admin/subscriptions/${subscription.id}/action`} method="post" className="flex flex-wrap items-center gap-2" onSubmit={(event) => { if (confirm && !window.confirm(confirm)) event.preventDefault(); }}><input name="action" type="hidden" value={action} />{days && <><span className="text-[10px] text-[var(--garage-text-muted)]">{children}</span><input aria-label="Количество дней" className={`${inputClass} w-16`} defaultValue="30" min="1" max="365" name="days" required type="number" /></>}{paymentReference && <input aria-label="Reference оплаченного SaaS-платежа" className={`${inputClass} w-36`} name="payment_reference" placeholder="Reference оплаты" required={action === 'activate'} />}<button className="h-8 border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-text-secondary)]" type="submit">{days ? 'дн. · применить' : children}</button></Form>;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}

function Pagination({ links }: { links: { url: string | null; label: string; active: boolean }[] }) {
    return <div className="mt-3 flex flex-wrap gap-1">{links.map((link) => link.url ? <Link className={`border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</div>;
}