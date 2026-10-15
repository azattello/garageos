import { Head } from '@inertiajs/react';
import { Building2, Mail, Phone, UserRound } from 'lucide-react';
import ClientPortalLayout from '../../Layouts/ClientPortalLayout';

export default function Account({
    clientName,
    organizationName,
    phone,
    email,
}: {
    clientName: string;
    organizationName: string;
    phone?: string | null;
    email: string;
}) {
    return (
        <ClientPortalLayout
            clientName={clientName}
            organizationName={organizationName}
        >
            <Head title="Профиль" />
            <header>
                <p className="text-xs uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                    Мой кабинет
                </p>
                <h1 className="mt-1 text-2xl font-semibold">Профиль</h1>
            </header>
            <section className="mt-5 divide-y divide-[var(--garage-border-subtle)] rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <ProfileRow icon={UserRound} label="Имя" value={clientName} />
                <ProfileRow
                    icon={Phone}
                    label="Телефон"
                    value={phone || 'Не указан'}
                />
                <ProfileRow icon={Mail} label="Email" value={email} />
                <ProfileRow
                    icon={Building2}
                    label="Сервис"
                    value={organizationName}
                />
            </section>
            <p className="mt-3 text-xs text-[var(--garage-text-muted)]">
                Чтобы изменить контактные данные, обратитесь в сервис.
            </p>
        </ClientPortalLayout>
    );
}

function ProfileRow({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof UserRound;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-center gap-3 p-4">
            <Icon className="text-[var(--garage-text-muted)]" size={16} />
            <div>
                <p className="text-[10px] text-[var(--garage-text-muted)]">
                    {label}
                </p>
                <p className="mt-1 text-sm font-medium">{value}</p>
            </div>
        </div>
    );
}
