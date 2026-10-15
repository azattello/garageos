import { Form, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Banknote,
    BarChart3,
    Building2,
    CreditCard,
    LayoutDashboard,
    LifeBuoy,
    Menu,
    ReceiptText,
    Settings2,
    ShieldCheck,
    Users,
    WalletCards,
    X,
} from 'lucide-react';
import { useState, type PropsWithChildren } from 'react';

const navigation = [
    {
        label: 'Платформа',
        items: [
            { label: 'Обзор', href: '/admin', icon: LayoutDashboard },
            { label: 'Организации', href: '/admin/organizations', icon: Building2 },
            { label: 'Пользователи', href: '/admin/users', icon: Users },
        ],
    },
    {
        label: 'Биллинг и метрики',
        items: [
            { label: 'Тарифы', href: '/admin/plans', icon: ReceiptText },
            { label: 'Подписки', href: '/admin/subscriptions', icon: CreditCard },
            { label: 'Платежи', href: '/admin/payments', icon: WalletCards },
            { label: 'Финансы SaaS', href: '/admin/finance', icon: Banknote },
            { label: 'Аналитика', href: '/admin/analytics', icon: BarChart3 },
        ],
    },
    {
        label: 'Система',
        items: [
            { label: 'Поддержка', href: '/admin/support', icon: LifeBuoy },
            { label: 'Журнал аудита', href: '/admin/audit-logs', icon: Activity },
            { label: 'Настройки', href: '/admin/settings', icon: Settings2 },
        ],
    },
];

export default function AdminLayout({ children, title }: PropsWithChildren<{ title: string }>) {
    const { props, url } = usePage<any>();
    const user = props.auth?.user;
    const pathname = url.split('?')[0];
    const [mobileOpen, setMobileOpen] = useState(false);

    return (
        <div className="min-h-screen bg-[var(--garage-background)] text-[var(--garage-text)]">
            {mobileOpen && <button aria-label="Закрыть навигацию" className="fixed inset-0 z-30 bg-black/70 lg:hidden" onClick={() => setMobileOpen(false)} type="button" />}
            <aside className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-[var(--garage-border)] bg-[var(--garage-surface)] transition-transform duration-200 lg:translate-x-0 ${mobileOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex h-16 items-center gap-3 border-b border-[var(--garage-border)] px-5">
                    <Link className="flex min-w-0 items-center gap-3" href="/admin">
                        <span className="grid size-9 shrink-0 place-items-center bg-[var(--garage-accent)] text-[var(--garage-background)]"><ShieldCheck size={19} /></span>
                        <span><span className="block text-sm font-semibold">GarageOS</span><span className="mt-0.5 block text-[10px] uppercase text-[var(--garage-text-muted)]">Platform console</span></span>
                    </Link>
                    <button aria-label="Закрыть навигацию" className="ml-auto text-[var(--garage-text-muted)] lg:hidden" onClick={() => setMobileOpen(false)} type="button"><X size={17} /></button>
                </div>
                <nav className="garage-sidebar-scroll flex-1 space-y-6 overflow-y-auto px-3 py-5">
                    {navigation.map((section) => <section key={section.label}>
                        <p className="mb-2 px-3 text-[10px] font-semibold uppercase text-[var(--garage-text-muted)]">{section.label}</p>
                        <div className="space-y-0.5">{section.items.map(({ label, href, icon: Icon }) => {
                            const active = href === '/admin' ? pathname === href : pathname === href || pathname.startsWith(`${href}/`);
                            return <Link className={`flex items-center gap-3 border-l-2 px-3 py-2 text-[13px] font-medium transition-colors ${active ? 'border-[var(--garage-accent)] bg-[rgba(184,255,61,0.07)] text-[var(--garage-accent)]' : 'border-transparent text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]'}`} href={href} key={href} onClick={() => setMobileOpen(false)}><Icon size={16} strokeWidth={1.8} />{label}</Link>;
                        })}</div>
                    </section>)}
                </nav>
                <div className="border-t border-[var(--garage-border)] p-3">
                    <div className="flex items-center gap-3 px-2 py-2"><span className="grid size-8 shrink-0 place-items-center rounded-full bg-[var(--garage-surface-elevated)] text-xs font-semibold">{(user?.name || 'A').slice(0, 1).toUpperCase()}</span><span className="min-w-0 flex-1"><span className="block truncate text-xs font-medium">{user?.name || 'Администратор'}</span><span className="mt-0.5 block text-[10px] text-[var(--garage-text-muted)]">Platform Admin</span></span></div>
                    <Form action="/logout" method="post"><button className="mt-1 h-9 w-full border border-[var(--garage-border)] text-xs text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface-secondary)]" type="submit">Выйти</button></Form>
                </div>
            </aside>
            <div className="min-h-screen lg:pl-64">
                <header className="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-[var(--garage-border)] bg-[var(--garage-background)]/95 px-4 backdrop-blur sm:px-6">
                    <button aria-label="Открыть навигацию" className="grid size-9 place-items-center border border-[var(--garage-border)] text-[var(--garage-text-secondary)] lg:hidden" onClick={() => setMobileOpen(true)} type="button"><Menu size={17} /></button>
                    <div className="min-w-0 flex-1"><p className="truncate text-sm font-semibold">{title}</p></div>
                    <span className="hidden items-center gap-2 border border-[var(--garage-border)] px-2.5 py-1 text-[10px] text-[var(--garage-text-muted)] sm:inline-flex"><span className="size-1.5 rounded-full bg-[var(--garage-success)]" />Платформа работает</span>
                </header>
                <main className="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">{children}</main>
            </div>
        </div>
    );
}