import { Form, Link } from '@inertiajs/react';
import {
    CarFront,
    ClipboardList,
    Clock3,
    Home,
    LogOut,
    UserRound,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import BrandLogo from '../components/BrandLogo';

const navigation = [
    { label: 'Главная', href: '/portal', icon: Home },
    { label: 'Автомобили', href: '/portal#cars', icon: CarFront },
    { label: 'Заказы', href: '/portal/orders', icon: ClipboardList },
    { label: 'История', href: '/portal/history', icon: Clock3 },
    { label: 'Ещё', href: '/portal/account', icon: UserRound },
];

export default function ClientPortalLayout({
    children,
    clientName,
    organizationName,
}: PropsWithChildren<{ clientName: string; organizationName: string }>) {
    const pathname =
        typeof window === 'undefined' ? '/portal' : window.location.pathname;
    const hash = typeof window === 'undefined' ? '' : window.location.hash;

    return (
        <div className="min-h-screen bg-[var(--garage-background)] text-[var(--garage-text)]">
            <header className="border-b border-[var(--garage-border)] bg-[var(--garage-background)] px-4 py-3 sm:px-6">
                <div className="mx-auto flex max-w-5xl items-center justify-between gap-3">
                    <Link
                        aria-label="Кабинет GarageOS"
                        className="min-w-0"
                        href="/portal"
                    >
                        <BrandLogo compact />
                    </Link>
                    <div className="flex min-w-0 items-center gap-3">
                        <div className="hidden min-w-0 text-right sm:block">
                            <p className="truncate text-xs font-medium">
                                {clientName}
                            </p>
                            <p className="truncate text-[10px] text-[var(--garage-text-muted)]">
                                {organizationName}
                            </p>
                        </div>
                        <Form action="/logout" method="post">
                            <button
                                aria-label="Выйти из кабинета"
                                className="grid size-10 place-items-center rounded-md border border-[var(--garage-border)] text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface)]"
                                type="submit"
                            >
                                <LogOut size={16} />
                            </button>
                        </Form>
                    </div>
                </div>
            </header>

            <main className="mx-auto min-h-[calc(100vh-64px)] max-w-5xl px-4 py-6 pb-24 sm:px-6 sm:py-8 sm:pb-10">
                {children}
            </main>

            <nav
                aria-label="Меню клиентского кабинета"
                className="fixed inset-x-0 bottom-0 z-30 grid h-16 border-t border-[var(--garage-border)] bg-[var(--garage-surface)] lg:hidden"
                style={{
                    gridTemplateColumns: `repeat(${navigation.length}, minmax(0, 1fr))`,
                }}
            >
                {navigation.map(({ label, href, icon: Icon }) => {
                    const targetPath = href.split('#')[0];
                    const active =
                        label === 'Главная'
                            ? pathname === '/portal' && hash !== '#cars'
                            : label === 'Автомобили'
                              ? pathname.startsWith('/portal/cars') ||
                                (pathname === '/portal' && hash === '#cars')
                              : pathname === targetPath ||
                                pathname.startsWith(`${targetPath}/`);
                    return (
                        <Link
                            aria-current={active ? 'page' : undefined}
                            className={`flex min-w-0 flex-col items-center justify-center gap-1 px-1 text-[10px] ${active ? 'text-[var(--garage-accent)]' : 'text-[var(--garage-text-muted)]'}`}
                            href={href}
                            key={href}
                        >
                            <Icon size={17} />
                            <span className="truncate">{label}</span>
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}
