import { Form } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

type Props = {
    settings: { support_email: string; support_phone: string; support_url: string };
    system: { application: string; environment: string; timezone: string; planSource: string; billing: string; automaticCharges: boolean; csrfProtection: boolean; secrets: string };
};

const inputClass = 'h-10 w-full min-w-0 border border-[var(--garage-border)] bg-[var(--garage-background)] px-3 text-xs text-[var(--garage-text)]';

export default function Index({ settings, system }: Props) {
    return <AdminLayout title="Настройки">
        <div><p className="text-[11px] uppercase text-[var(--garage-text-muted)]">Platform configuration</p><h1 className="mt-1 text-2xl font-semibold">Настройки</h1><p className="mt-1 text-xs text-[var(--garage-text-secondary)]">Контакт GarageOS и безопасная сводка конфигурации.</p></div>
        <div className="mt-6 grid gap-8 xl:grid-cols-[1fr_1fr]">
            <section className="border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Контакты поддержки</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Эти данные доступны владельцам организаций на странице тарифа.</p><Form action="/admin/settings/general" method="put" className="mt-4 grid max-w-xl gap-4"><Field label="Email поддержки"><input autoComplete="email" className={inputClass} defaultValue={settings.support_email} maxLength={255} name="support_email" type="email" /></Field><Field label="Телефон"><input autoComplete="tel" className={inputClass} defaultValue={settings.support_phone} maxLength={50} name="support_phone" type="tel" /></Field><Field label="Страница поддержки"><input className={inputClass} defaultValue={settings.support_url} maxLength={255} name="support_url" placeholder="https://..." type="url" /></Field><div><button className="h-9 bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" type="submit">Сохранить контакты</button></div></Form></section>
            <section className="border-t border-[var(--garage-border)] pt-4"><h2 className="text-sm font-semibold">Система</h2><dl className="mt-4 grid gap-3 sm:grid-cols-2">{Object.entries({ 'Продукт': system.application, 'Окружение': system.environment, 'Часовой пояс': system.timezone, 'Источник тарифов': system.planSource, 'Оплата': system.billing, 'Автосписание': system.automaticCharges ? 'Включено' : 'Не подключено', 'CSRF': system.csrfProtection ? 'Защита включена' : 'Не включена', 'Секреты': system.secrets }).map(([label, value]) => <Meta key={label} label={label} value={String(value)} />)}</dl><div className="mt-5 border-l-2 border-[var(--garage-info)] bg-[var(--garage-surface)] px-3 py-2 text-[10px] leading-4 text-[var(--garage-text-secondary)]">Платёжные ключи, SMTP credentials и другие секреты не читаются и не отображаются через эту панель. Автоматический acquiring не подключён.</div></section>
        </div>
    </AdminLayout>;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="grid gap-1.5 text-[10px] text-[var(--garage-text-muted)]">{label}{children}</label>;
}

function Meta({ label, value }: { label: string; value: string }) {
    return <div className="border-b border-[var(--garage-border-subtle)] pb-2"><dt className="text-[10px] uppercase text-[var(--garage-text-muted)]">{label}</dt><dd className="mt-1 break-words text-xs">{value}</dd></div>;
}