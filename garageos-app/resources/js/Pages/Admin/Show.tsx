import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Building2, CalendarClock, CarFront, CreditCard, Users, Wrench } from 'lucide-react';
import AdminLayout from '../../Layouts/AdminLayout';

type Props = {
    organization: { id: number; name: string; slug: string; status: string; phone?: string; email?: string; timezone?: string; currency?: string; created_at: string; users_count: number; owner?: { id: number; name: string; email: string; phone?: string; status: string; last_login_at?: string | null } | null; subscription?: { id: number; plan: string; plan_name: string; price?: number; max_users: number; status: string; starts_at?: string | null; ends_at?: string | null; trial_ends_at?: string | null; auto_renew: boolean } | null };
    users: { id: number; name: string; email: string; role: string; status: string; last_login_at?: string | null }[];
    stats: { users: number; clients: number; cars: number; appointments: number; orders: number };
    saasFinance: { paid: number; refunded: number; outstanding: number; payments: { data: { id: number; amount: number; currency: string; plan?: string; method: string; status: string; reference?: string; paid_at?: string | null }[]; links?: { url: string | null; label: string; active: boolean }[] } };
    recentActivity: { id: number; actor: string; actor_email?: string; action: string; target?: string; metadata?: Record<string, unknown>; ip?: string; created_at?: string | null }[];
    subscriptionRequests: { id: number; current_plan: string; requested_plan: string; amount: number; currency: string; status: string; requested_at?: string | null; reviewed_at?: string | null }[];
};

const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;
const formatDate = (value?: string) => value ? new Date(value).toLocaleDateString('ru-RU') : '—';

export default function Show({ organization, users, stats, saasFinance, recentActivity, subscriptionRequests }: Props) {
    const statusLabel = organization.status === 'active' ? 'Активна' : organization.status === 'archived' ? 'В архиве' : 'Заблокирована';
    const nextStatus = organization.status === 'active' ? 'blocked' : 'active';
    return (
        <AdminLayout title={organization.name}>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <Link className="inline-flex items-center gap-2 text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]" href="/admin/organizations"><ArrowLeft size={14} />Организации</Link>
                    <div className="mt-4 flex items-center gap-3">
                        <span className="grid size-10 place-items-center bg-[var(--garage-surface-elevated)] text-[var(--garage-accent)]"><Building2 size={20} /></span>
                        <div>
                            <p className="text-[10px] uppercase text-[var(--garage-text-muted)]">{organization.slug}</p>
                            <h1 className="mt-1 text-xl font-semibold">{organization.name}</h1>
                        </div>
                    </div>
                </div>
                <div className="flex flex-wrap items-center gap-2"><span className={`border px-2.5 py-1 text-[10px] ${organization.status === 'active' ? 'border-[rgba(114,224,106,0.3)] text-[var(--garage-success)]' : 'border-[rgba(240,100,100,0.3)] text-[var(--garage-error)]'}`}>{statusLabel}</span>{organization.status !== 'archived' && <Form action={`/admin/organizations/${organization.id}/status`} method="post" onSubmit={(event) => { if (nextStatus === 'blocked' && !window.confirm(`Заблокировать организацию «${organization.name}»?`)) event.preventDefault(); }}><input name="status" type="hidden" value={nextStatus} /><button className="h-8 border border-[var(--garage-border)] px-2.5 text-[10px]" type="submit">{organization.status === 'blocked' ? 'Активировать' : 'Заблокировать'}</button></Form>}{organization.status === 'archived' ? <Form action={`/admin/organizations/${organization.id}/status`} method="post"><input name="status" type="hidden" value="active" /><button className="h-8 border border-[var(--garage-border)] px-2.5 text-[10px] text-[var(--garage-success)]" type="submit">Восстановить</button></Form> : <Form action={`/admin/organizations/${organization.id}/status`} method="post" onSubmit={(event) => { if (!window.confirm(`Архивировать «${organization.name}» без удаления данных?`)) event.preventDefault(); }}><input name="status" type="hidden" value="archived" /><button className="h-8 border border-[rgba(240,100,100,0.3)] px-2.5 text-[10px] text-[var(--garage-error)]" type="submit">Архивировать</button></Form>}</div>
            </div>

            <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <MiniStat label="Сотрудники" value={String(stats.users)} icon={Users} />
                <MiniStat label="Клиенты" value={String(stats.clients)} icon={Users} />
                <MiniStat label="Автомобили" value={String(stats.cars)} icon={CarFront} />
                <MiniStat label="Записи" value={String(stats.appointments)} icon={CalendarClock} />
                <MiniStat label="Заказ-наряды" value={String(stats.orders)} icon={Wrench} />
            </div>

            <div className="mt-7 grid gap-7 xl:grid-cols-2">
                <section className="border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Профиль организации</h2><dl className="mt-4 grid gap-3 text-xs sm:grid-cols-2"><Meta label="Email организации" value={organization.email || '—'} /><Meta label="Телефон" value={organization.phone || '—'} /><Meta label="Часовой пояс" value={organization.timezone || '—'} /><Meta label="Валюта" value={organization.currency || '—'} /><Meta label="Создана" value={formatDate(organization.created_at)} /></dl><div className="mt-5 border-t border-[var(--garage-border-subtle)] pt-4"><h3 className="text-xs font-semibold">Владелец</h3>{organization.owner ? <dl className="mt-3 grid gap-3 text-xs sm:grid-cols-2"><Meta label="Имя" value={organization.owner.name} /><Meta label="Email" value={organization.owner.email} /><Meta label="Телефон" value={organization.owner.phone || '—'} /><Meta label="Статус / последний вход" value={`${organization.owner.status} · ${formatDate(organization.owner.last_login_at)}`} /></dl> : <p className="mt-3 text-xs text-[var(--garage-warning)]">Пользователь с ролью owner не найден.</p>}</div></section>

                <section className="border-t border-[var(--garage-border)] pt-4"><div className="flex items-center justify-between gap-3"><h2 className="text-sm font-semibold">Подписка</h2><Link className="text-[10px] text-[var(--garage-accent)] hover:underline" href="/admin/subscriptions">Все подписки</Link></div>{organization.subscription ? <><dl className="mt-4 grid gap-3 text-xs sm:grid-cols-2"><Meta label="Тариф" value={`${organization.subscription.plan_name} · ${organization.subscription.price ? money(organization.subscription.price) + '/мес.' : ''}`} /><Meta label="Статус" value={organization.subscription.status} /><Meta label="Сотрудники / лимит" value={`${stats.users} / ${organization.subscription.max_users}`} /><Meta label="Начало" value={formatDate(organization.subscription.starts_at)} /><Meta label="Окончание" value={formatDate(organization.subscription.ends_at)} /><Meta label="Trial до" value={formatDate(organization.subscription.trial_ends_at)} /><Meta label="Автопродление" value={organization.subscription.auto_renew ? 'Включено' : 'Не предусмотрено'} /></dl><Form action={`/admin/organizations/${organization.id}/trial`} method="post" className="mt-4 flex items-center gap-2"><input name="days" type="number" defaultValue="14" min="1" max="365" className="h-8 w-20 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-xs" /><button className="h-8 border border-[var(--garage-border)] px-2.5 text-[10px]" type="submit">Продлить trial на дни</button></Form></> : <p className="mt-3 text-xs text-[var(--garage-warning)]">Подписка не назначена.</p>}</section>
            </div>

            <section className="mt-7 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Оплаты GarageOS</h2><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">Только платежи за платформу; финансы заказов СТО здесь не учитываются.</p><div className="mt-4 grid gap-3 sm:grid-cols-3"><MiniStat label="Оплачено" value={money(saasFinance.paid)} icon={CreditCard} /><MiniStat label="Возвраты" value={money(saasFinance.refunded)} icon={CreditCard} /><MiniStat label="Ожидает оплаты" value={money(saasFinance.outstanding)} icon={CreditCard} /></div>{saasFinance.payments.data.length ? <div className="mt-4 divide-y divide-[var(--garage-border-subtle)]">{saasFinance.payments.data.map((payment) => <div className="grid gap-2 py-3 text-xs sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-center" key={payment.id}><span>{payment.plan || '—'} · {payment.reference || payment.method}</span><span className="text-[var(--garage-text-muted)]">{formatDate(payment.paid_at)}</span><span className="text-[var(--garage-text-muted)]">{payment.status}</span><strong>{money(payment.amount)}</strong></div>)}</div> : <Empty>Платежей GarageOS пока нет.</Empty>}</section>

            <section className="mt-7 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Пользователи организации</h2>{users.length ? <div className="mt-3 divide-y divide-[var(--garage-border-subtle)]">{users.map((user) => <div className="grid gap-2 py-3 sm:grid-cols-[1fr_0.8fr_0.8fr] sm:items-center" key={user.id}><div><p className="text-xs font-medium">{user.name}</p><p className="mt-1 break-all text-[10px] text-[var(--garage-text-muted)]">{user.email}</p></div><p className="text-[10px] text-[var(--garage-text-secondary)]">{user.role} · {user.status}</p><p className="text-[10px] text-[var(--garage-text-muted)]">Вход: {formatDate(user.last_login_at)}</p></div>)}</div> : <Empty>Пользователей нет.</Empty>}</section>

            <section className="mt-7 border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">История заявок на тариф</h2>{subscriptionRequests.length ? <div className="mt-3 divide-y divide-[var(--garage-border-subtle)]">{subscriptionRequests.map((change) => <div className="grid gap-2 py-3 text-xs sm:grid-cols-[1fr_1fr_auto] sm:items-center" key={change.id}><span>{change.current_plan} → {change.requested_plan}</span><span className="text-[var(--garage-text-muted)]">{formatDate(change.requested_at)} · {change.status}</span><strong>{money(change.amount)}</strong></div>)}</div> : <Empty>Заявок на изменение тарифа пока нет.</Empty>}</section>

            <section className="mt-7 border-t border-[var(--garage-border)] pt-4"><div className="flex items-center justify-between gap-3"><h2 className="text-sm font-semibold">Последняя активность</h2><Link className="text-[10px] text-[var(--garage-accent)] hover:underline" href={`/admin/audit-logs?organization_id=${organization.id}`}>Открыть журнал</Link></div>{recentActivity.length ? <div className="mt-3 divide-y divide-[var(--garage-border-subtle)]">{recentActivity.map((event) => <div className="grid gap-2 py-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start" key={event.id}><div><p className="text-xs font-medium">{event.action}</p><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{event.actor}{event.target ? ` · ${event.target}` : ''}</p></div><p className="break-all text-[10px] text-[var(--garage-text-muted)]">{event.metadata ? JSON.stringify(event.metadata) : '—'}</p><p className="text-[10px] text-[var(--garage-text-muted)]">{formatDate(event.created_at)}{event.ip ? ` · ${event.ip}` : ''}</p></div>)}</div> : <Empty>Событий пока нет.</Empty>}</section>
        </AdminLayout>
    );
}

function MiniStat({ label, value, icon: Icon }: { label: string; value: string; icon: typeof Users }) {
    return <article className="border border-[var(--garage-border)] bg-[var(--garage-surface)] p-3"><div className="flex items-center justify-between"><p className="text-[10px] text-[var(--garage-text-secondary)]">{label}</p><Icon className="text-[var(--garage-text-muted)]" size={15} /></div><p className="mt-2 break-words text-lg font-semibold">{value}</p></article>;
}

function Meta({ label, value }: { label: string; value: string }) {
    return <div className="grid gap-1 border-b border-[var(--garage-border-subtle)] pb-2 last:border-0 last:pb-0"><dt className="text-[10px] uppercase text-[var(--garage-text-muted)]">{label}</dt><dd className="break-words text-xs">{value}</dd></div>;
}

function Empty({ children }: { children: React.ReactNode }) {
    return <div className="grid min-h-24 place-items-center text-center text-xs text-[var(--garage-text-muted)]">{children}</div>;
}
