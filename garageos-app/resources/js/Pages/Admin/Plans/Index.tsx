import { Check, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';

type Plan = { key: string; name: string; price: number; currency: string; max_users: number; features: string[] };
type Props = { plans: Plan[] };

const featureNames: Record<string, string> = {
    clients: 'Клиенты', cars: 'Автомобили', appointments: 'Записи и календарь', orders: 'Заказ-наряды', kanban: 'Kanban доска',
    services: 'Справочник услуг', employees: 'Управление сотрудниками', vehicle_history: 'История автомобиля', photos: 'Фотографии заказов',
    payments: 'Платежи и баланс заказа', debt_control: 'Контроль задолженности', basic_analytics: 'Базовая аналитика',
    notifications: 'Уведомления', public_order: 'Публичная страница заказа', inventory: 'Склад и запчасти', expenses: 'Расходы СТО',
    advanced_analytics: 'Расширенная аналитика', financial_reports: 'Финансовые отчёты', employee_workload: 'Загрузка сотрудников',
};
const allFeatures = Object.keys(featureNames);

export default function Index({ plans }: Props) {
    return <AdminLayout title="Тарифы">
        <div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Plan catalog</p><h1 className="mt-1 text-2xl font-semibold">Тарифы GarageOS</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Действующие планы и их возможности из серверного каталога.</p></div>
        <div className="mt-5 border-l-2 border-[var(--garage-info)] bg-[var(--garage-surface)] px-4 py-3 text-xs leading-5 text-[var(--garage-text-secondary)]">Планы сейчас определены в `PlanCatalog` и влияют на server-side feature gates. Эта страница только для просмотра: менять цену или функции из UI нельзя, чтобы не рассинхронизировать доступ организаций.</div>
        <section className="mt-6 grid gap-6 xl:grid-cols-2">{plans.map((plan) => {
            const excluded = allFeatures.filter((feature) => !plan.features.includes(feature));
            return <article className="min-w-0 border-t-2 border-[var(--garage-accent)] pt-4" key={plan.key}>
                <div className="flex flex-wrap items-start justify-between gap-3"><div><p className="text-[10px] uppercase text-[var(--garage-text-muted)]">{plan.key}</p><h2 className="mt-1 text-xl font-semibold">{plan.name}</h2></div><div className="text-right"><p className="text-lg font-semibold">{plan.price.toLocaleString('ru-RU')} ₸</p><p className="text-[10px] text-[var(--garage-text-muted)]">за месяц</p></div></div>
                <div className="mt-4 grid grid-cols-2 gap-3 border-y border-[var(--garage-border)] py-3 text-xs"><div><p className="text-[10px] text-[var(--garage-text-muted)]">Максимум сотрудников</p><p className="mt-1 font-semibold">{plan.max_users}</p></div><div><p className="text-[10px] text-[var(--garage-text-muted)]">Возможности</p><p className="mt-1 font-semibold">{plan.features.length} из {allFeatures.length}</p></div></div>
                <h3 className="mt-4 text-[10px] font-semibold uppercase text-[var(--garage-text-muted)]">Включено</h3><ul className="mt-2 grid gap-2 sm:grid-cols-2">{plan.features.map((feature) => <li className="flex items-start gap-2 text-xs text-[var(--garage-text-secondary)]" key={feature}><Check aria-hidden="true" className="mt-0.5 shrink-0 text-[var(--garage-success)]" size={14} />{featureNames[feature] || feature}</li>)}</ul>
                {excluded.length > 0 && <><h3 className="mt-5 text-[10px] font-semibold uppercase text-[var(--garage-text-muted)]">Не входит</h3><ul className="mt-2 grid gap-2 sm:grid-cols-2">{excluded.map((feature) => <li className="flex items-start gap-2 text-xs text-[var(--garage-text-muted)]" key={feature}><X aria-hidden="true" className="mt-0.5 shrink-0" size={14} />{featureNames[feature]}</li>)}</ul></>}
            </article>;
        })}</section>
    </AdminLayout>;
}