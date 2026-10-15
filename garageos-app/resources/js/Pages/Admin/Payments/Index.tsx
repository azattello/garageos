import { Form, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

type Payment = { id: number; organization: string; organization_id?: number; plan?: string; amount: number; currency: string; method: string; status: string; reference?: string; description?: string; paid_at?: string | null };
type Props = {
    payments: { data: Payment[]; links?: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: { search?: string; status?: string; organization_id?: string; from?: string; to?: string };
    organizations: { id: number; name: string }[];
    subscriptions: { id: number; organization_id: number; label: string }[];
    methods: Record<string, string>;
};

const inputClass = 'h-9 min-w-0 border border-[var(--garage-border)] bg-[var(--garage-background)] px-2 text-xs text-[var(--garage-text)]';
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat('ru-RU', { dateStyle: 'medium' }).format(new Date(value)) : '—';
const money = (amount: number, currency: string) => `${amount.toLocaleString('ru-RU')} ${currency === 'KZT' ? '₸' : currency}`;
const statusLabels: Record<string, string> = { paid: 'Оплачен', pending: 'Ожидает', failed: 'Ошибка', refunded: 'Возвращён' };

export default function Index({ payments, filters, organizations, subscriptions, methods }: Props) {
    return <AdminLayout title="Платежи">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">GarageOS billing</p><h1 className="mt-1 text-2xl font-semibold">Платежи платформы</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Здесь только оплаты GarageOS, не платежи по заказам СТО.</p></div><p className="text-xs text-[var(--garage-text-muted)]">Всего: {payments.total}</p></div>

        <details className="mt-5 border border-[var(--garage-border)] bg-[var(--garage-surface)]">
            <summary className="cursor-pointer list-none px-4 py-3 text-xs font-semibold">Добавить SaaS-платёж</summary>
            <Form action="/admin/payments" method="post" className="grid gap-3 border-t border-[var(--garage-border)] p-4 sm:grid-cols-2 xl:grid-cols-4">
                <Field label="Организация"><select className={inputClass} name="organization_id" required><option value="">Выберите организацию</option>{organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}</select></Field>
                <Field label="Подписка"><select className={inputClass} name="subscription_id"><option value="">Без связи с подпиской</option>{subscriptions.map((subscription) => <option key={subscription.id} value={subscription.id}>{subscription.label}</option>)}</select></Field>
                <Field label="Тариф"><select className={inputClass} name="plan"><option value="">Не указан</option><option value="start">Start</option><option value="business">Business</option></select></Field>
                <Field label="Сумма, ₸"><input className={inputClass} min="1" name="amount" required type="number" /></Field>
                <Field label="Способ оплаты"><select className={inputClass} name="method">{Object.entries(methods).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field>
                <Field label="Статус"><select className={inputClass} name="status"><option value="paid">Оплачен</option><option value="pending">Ожидает</option><option value="failed">Ошибка</option></select></Field>
                <Field label="Дата оплаты"><input className={inputClass} name="paid_at" type="date" /></Field>
                <Field label="Reference платежа"><input className={inputClass} maxLength={255} name="reference" /></Field>
                <Field label="Описание"><input className={`${inputClass} sm:col-span-2`} maxLength={2000} name="description" /></Field>
                <div className="flex items-end"><button className="h-9 bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" type="submit">Сохранить платёж</button></div>
                <p className="self-end text-[10px] leading-4 text-[var(--garage-text-muted)] xl:col-span-2">Статус «Оплачен» сразу добавляет поступление в SaaS-финансы. Pending и failed не учитываются в доходах до подтверждения.</p>
            </Form>
        </details>

        <Form action="/admin/payments" method="get" className="mt-5 grid gap-2 border-y border-[var(--garage-border)] py-3 sm:grid-cols-2 lg:grid-cols-6">
            <Field label="Поиск"><input className={inputClass} defaultValue={filters.search} name="search" placeholder="Организация или reference" /></Field>
            <Field label="Организация"><select className={inputClass} defaultValue={filters.organization_id || ''} name="organization_id"><option value="">Все организации</option>{organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}</select></Field>
            <Field label="Статус"><select className={inputClass} defaultValue={filters.status || ''} name="status"><option value="">Все статусы</option>{Object.entries(statusLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></Field>
            <Field label="С даты"><input className={inputClass} defaultValue={filters.from} name="from" type="date" /></Field>
            <Field label="По дату"><input className={inputClass} defaultValue={filters.to} name="to" type="date" /></Field>
            <button className="h-9 self-end border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]" type="submit">Фильтровать</button>
        </Form>

        <section className="mt-5 border-t border-[var(--garage-border)]">
            <div className="hidden grid-cols-[1.2fr_0.8fr_1.2fr_0.8fr_0.8fr_auto] gap-3 border-b border-[var(--garage-border)] py-2 text-[10px] uppercase text-[var(--garage-text-muted)] lg:grid"><span>Дата / организация</span><span>Тариф</span><span>Метод / reference</span><span>Статус</span><span className="text-right">Сумма</span><span>Действия</span></div>
            {payments.data.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{payments.data.map((payment) => <div className="grid gap-3 py-3 lg:grid-cols-[1.2fr_0.8fr_1.2fr_0.8fr_0.8fr_auto] lg:items-center" key={payment.id}>
                <div><Link className="text-xs font-medium hover:text-[var(--garage-accent)]" href={payment.organization_id ? `/admin/organizations/${payment.organization_id}` : '/admin/payments'}>{payment.organization}</Link><p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{formatDate(payment.paid_at)}</p></div>
                <p className="text-xs">{payment.plan || '—'}</p>
                <div><p className="text-xs">{methods[payment.method] || payment.method}</p><p className="mt-1 break-all text-[10px] text-[var(--garage-text-muted)]">{payment.reference || payment.description || '—'}</p></div>
                <span className={`w-fit border px-2 py-1 text-[10px] ${payment.status === 'paid' ? 'border-[rgba(114,224,106,0.3)] text-[var(--garage-success)]' : payment.status === 'refunded' || payment.status === 'failed' ? 'border-[rgba(240,100,100,0.3)] text-[var(--garage-error)]' : 'border-[rgba(245,196,81,0.3)] text-[var(--garage-warning)]'}`}>{statusLabels[payment.status] || payment.status}</span>
                <p className="text-sm font-semibold lg:text-right">{money(payment.amount, payment.currency)}</p>
                <div className="flex flex-wrap gap-2">{['pending', 'failed'].includes(payment.status) && <Form action={`/admin/payments/${payment.id}/status`} method="post"><input name="status" type="hidden" value="paid" /><button className="h-8 border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-success)]" type="submit">Подтвердить</button></Form>}{payment.status === 'paid' && <Form action={`/admin/payments/${payment.id}/status`} method="post" onSubmit={(event) => { if (!window.confirm(`Вернуть ${money(payment.amount, payment.currency)} организации «${payment.organization}»? Доход исключится из финансовой отчётности.`)) event.preventDefault(); }}><input name="status" type="hidden" value="refunded" /><button className="h-8 border border-[rgba(240,100,100,0.3)] px-2 text-[10px] text-[var(--garage-error)]" type="submit">Возврат</button></Form>}</div>
            </div>)}</div> : <div className="grid min-h-40 place-items-center text-center text-xs text-[var(--garage-text-muted)]">Платежей по выбранным условиям нет.</div>}
            {payments.links && <div className="flex flex-wrap gap-1 py-3">{payments.links.map((link) => link.url ? <Link className={`border px-2 py-1 text-[10px] ${link.active ? 'border-[var(--garage-accent)] text-[var(--garage-accent)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`} href={link.url} key={link.label} dangerouslySetInnerHTML={{ __html: link.label }} /> : null)}</div>}
        </section>
    </AdminLayout>;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}