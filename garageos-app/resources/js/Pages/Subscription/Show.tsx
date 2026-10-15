import { Form, Link } from '@inertiajs/react';
import { BadgeCheck, Check, CreditCard, LockKeyhole, Users, X, type LucideIcon } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type Plan = { key: string; name: string; price: number; max_users: number; features: string[] };
type Props = {
    subscription?: { plan: string; status: string; max_users: number; starts_at?: string | null; ends_at?: string | null; trial_ends_at?: string | null };
    plans: Plan[];
    employeeSeats: number;
    pendingRequest?: { id: number; requested_plan: string; status: string } | null;
    organizationStatus?: string;
    blockedMessage?: string;
    featureUnavailable?: string;
    subscriptionExpired?: string;
    supportContact?: { support_email?: string; support_phone?: string; support_url?: string };
    canManageSubscription: boolean;
};

const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸ / мес.`;
const formatDate = (value?: string | null) => value ? new Date(value).toLocaleDateString('ru-RU') : '—';
const featureNames: Record<string, string> = {
    clients: 'Клиенты', cars: 'Автомобили', appointments: 'Записи и календарь', orders: 'Заказ-наряды', kanban: 'Kanban',
    services: 'Услуги', employees: 'Сотрудники', vehicle_history: 'История автомобиля', photos: 'Фотографии', payments: 'Оплаты',
    debt_control: 'Контроль задолженности', basic_analytics: 'Базовая аналитика', notifications: 'Уведомления', public_order: 'Страница заказа для клиента',
    inventory: 'Склад и запчасти', expenses: 'Расходы', advanced_analytics: 'Расширенная аналитика', financial_reports: 'Финансовые отчёты', employee_workload: 'Загрузка сотрудников',
};

export default function Show({ subscription, plans, employeeSeats, pendingRequest, organizationStatus, blockedMessage, featureUnavailable, subscriptionExpired, supportContact, canManageSubscription }: Props) {
    const blocked = organizationStatus === 'blocked';
    const currentPlan = plans.find((plan) => plan.key === subscription?.plan);
    const expired = subscription?.status === 'expired' || Boolean(subscriptionExpired);
    const statusLabel = blocked ? 'Организация заблокирована' : expired ? 'Доступ ограничен' : subscription?.status === 'trialing' ? 'Пробный период' : subscription?.status === 'active' ? 'Активна' : 'Не активна';
    const unavailableFeature = featureUnavailable ? featureNames[featureUnavailable] || featureUnavailable : null;

    return <AppLayout><div className="mx-auto max-w-4xl">
        <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Система</p>
        <h1 className="mt-2 text-2xl font-semibold">Тариф и оплата</h1>
        {(blocked || expired || unavailableFeature) && <div className="mt-5 flex gap-3 border-l-2 border-[var(--garage-warning)] bg-[var(--garage-surface)] p-4"><LockKeyhole className="shrink-0 text-[var(--garage-warning)]" size={17} /><div><p className="text-sm font-semibold">{unavailableFeature ? `Функция «${unavailableFeature}» доступна в Business` : statusLabel}</p><p className="mt-1 text-sm text-[var(--garage-text-secondary)]">{blockedMessage || (expired ? 'Подписка истекла. Рабочие разделы останутся закрыты до подтверждения продления.' : 'Выберите тариф и отправьте заявку. Платный доступ включит Platform Admin после проверки платежа.')}</p></div></div>}

        <section className="mt-5 grid gap-3 sm:grid-cols-3">
            <Summary icon={CreditCard} label="Тариф" value={currentPlan?.name || subscription?.plan || '—'} detail={currentPlan ? money(currentPlan.price) : 'Тариф не назначен'} />
            <Summary icon={Users} label="Сотрудники" value={`${employeeSeats} / ${currentPlan?.max_users ?? subscription?.max_users ?? 0}`} detail="клиентские кабинеты не учитываются" />
            <Summary icon={BadgeCheck} label="Статус" value={statusLabel} detail={subscription?.status === 'trialing' ? `Пробный период до ${formatDate(subscription.trial_ends_at)}` : subscription?.ends_at ? `${expired ? 'Истекла' : 'Оплачена'} до ${formatDate(subscription.ends_at)}` : 'Срок действия не указан'} />
        </section>

        {pendingRequest && <div className="mt-5 border border-[var(--garage-border)] bg-[var(--garage-surface)] px-4 py-3 text-sm"><span className="font-medium">Заявка на {plans.find((plan) => plan.key === pendingRequest.requested_plan)?.name || pendingRequest.requested_plan}</span><span className="ml-2 text-[var(--garage-warning)]">ожидает подтверждения оплаты</span></div>}

        <section className="mt-7"><div><h2 className="text-sm font-semibold">Сравнение тарифов</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Состав и лимиты указаны по действующим настройкам GarageOS.</p></div><div className="mt-3 grid gap-3 md:grid-cols-2">{plans.map((plan) => {
            const selected = plan.key === subscription?.plan;
            const requested = pendingRequest?.requested_plan === plan.key;
            const overSeatLimit = employeeSeats > plan.max_users;
            const unavailableFeatures = Object.keys(featureNames).filter((feature) => !plan.features.includes(feature));
            return <article className={`border bg-[var(--garage-surface)] p-5 ${selected ? 'border-[var(--garage-accent)]' : 'border-[var(--garage-border)]'}`} key={plan.key}>
                <div className="flex items-start justify-between gap-3"><div><h3 className="text-base font-semibold">{plan.name}</h3><p className="mt-1 text-sm text-[var(--garage-text-secondary)]">{money(plan.price)}</p></div><span className="text-xs text-[var(--garage-text-muted)]">до {plan.max_users} сотрудников</span></div>
                <p className="mt-4 text-[11px] font-semibold uppercase text-[var(--garage-text-muted)]">Включено</p>
                <ul className="mt-2 grid gap-1.5 sm:grid-cols-2">{plan.features.map((feature) => <li className="flex items-start gap-2 text-xs text-[var(--garage-text-secondary)]" key={feature}><Check aria-hidden="true" className="shrink-0 text-[var(--garage-accent)]" size={14} />{featureNames[feature] || feature}</li>)}</ul>
                {unavailableFeatures.length > 0 && <><p className="mt-4 text-[11px] font-semibold uppercase text-[var(--garage-text-muted)]">Не входит</p><ul className="mt-2 grid gap-1.5 sm:grid-cols-2">{unavailableFeatures.map((feature) => <li className="flex items-start gap-2 text-xs text-[var(--garage-text-muted)]" key={feature}><X aria-hidden="true" className="shrink-0" size={14} />{featureNames[feature]}</li>)}</ul></>}
                <div className="mt-4">{selected ? <span className="inline-flex h-9 items-center rounded-md border border-[var(--garage-border)] px-3 text-xs">Текущий тариф</span> : requested ? <span className="inline-flex h-9 items-center text-xs text-[var(--garage-warning)]">Заявка рассматривается</span> : canManageSubscription ? <Form action="/subscription/plan-change" method="post"><input name="plan" type="hidden" value={plan.key} /><button className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50" disabled={blocked || overSeatLimit || Boolean(pendingRequest)} type="submit">{overSeatLimit ? 'Сначала уменьшите число сотрудников' : plan.price > (currentPlan?.price || 0) ? 'Запросить обновление' : 'Запросить смену тарифа'}</button></Form> : <span className="text-xs text-[var(--garage-text-muted)]">Обратитесь к владельцу организации для смены тарифа.</span>}</div>
            </article>;
        })}</div></section>
        <section className="mt-5 border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><h2 className="text-sm font-semibold">Как изменить тариф и оплатить</h2><ol className="mt-2 list-decimal space-y-1 pl-4 text-xs leading-5 text-[var(--garage-text-secondary)]"><li>Владелец организации отправляет заявку на нужный тариф. Менеджер может просматривать условия, но не менять подписку.</li><li>Дождитесь согласования реквизитов и оплаты с администратором GarageOS.</li><li>После перевода передайте администратору reference платежа. Доступ и новый тариф включаются после ручной проверки.</li></ol><p className="mt-3 text-xs leading-5 text-[var(--garage-text-muted)]">Автоматическое списание, автопродление и самостоятельная отмена подписки сейчас не предусмотрены. Если заявка уже ожидает рассмотрения, отправить вторую нельзя.</p></section>
        {(supportContact?.support_email || supportContact?.support_phone || supportContact?.support_url) && <p className="mt-3 text-xs text-[var(--garage-text-muted)]">Поддержка GarageOS: {[supportContact.support_email, supportContact.support_phone, supportContact.support_url].filter(Boolean).join(' · ')}</p>}
        {!blocked && !expired && <Link className="mt-6 inline-flex h-9 items-center rounded-md border border-[var(--garage-border)] px-4 text-xs text-[var(--garage-text-secondary)]" href="/">Вернуться на обзор</Link>}
    </div></AppLayout>;
}

function Summary({ icon: Icon, label, value, detail }: { icon: LucideIcon; label: string; value: string; detail: string }) {
    return <article className="border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><div className="flex items-center justify-between"><p className="text-xs text-[var(--garage-text-muted)]">{label}</p><Icon className="text-[var(--garage-text-muted)]" size={15} /></div><p className="mt-3 text-lg font-semibold">{value}</p><p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">{detail}</p></article>;
}
