import type { LucideIcon } from 'lucide-react';
import {
    Banknote,
    Bell,
    CalendarDays,
    CarFront,
    ChartNoAxesCombined,
    CheckCircle2,
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    LogOut,
    Menu,
    Package,
    Plus,
    Search,
    Settings,
    ShieldCheck,
    Users,
    UsersRound,
    Wrench,
    X,
} from 'lucide-react';
import { Form, Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState, type PropsWithChildren } from 'react';
import BrandLogo from '../components/BrandLogo';

type NavigationItem = { label: string; icon: LucideIcon; href?: string };

const sections: { label: string; items: NavigationItem[] }[] = [
    {
        label: 'Рабочее пространство',
        items: [
            { label: 'Сегодня', icon: LayoutDashboard, href: '/' },
            { label: 'Заявки', icon: CalendarDays, href: '/appointments' },
            { label: 'Заказы', icon: ClipboardList, href: '/orders' },
            { label: 'Клиенты', icon: Users, href: '/clients' },
            { label: 'Автомобили', icon: CarFront, href: '/cars' },
            { label: 'Услуги', icon: Wrench, href: '/services' },
            { label: 'Склад', icon: Package, href: '/inventory' },
        ],
    },
    {
        label: 'Управление',
        items: [
            { label: 'Сотрудники', icon: UsersRound, href: '/employees' },
            { label: 'Финансы', icon: Banknote, href: '/finance' },
            { label: 'Уведомления', icon: Bell, href: '/notifications' },
            {
                label: 'Аналитика',
                icon: ChartNoAxesCombined,
                href: '/analytics',
            },
        ],
    },
    {
        label: 'Система',
        items: [
            { label: 'Тариф и оплата', icon: CreditCard, href: '/subscription' },
            { label: 'Настройки', icon: Settings, href: '/settings' },
        ],
    },
];

export default function AppLayout({ children }: PropsWithChildren) {
    const { props } = usePage<any>();
    const user = props.auth?.user;
    const organization = props.auth?.organization;
    const isPlatformAdmin =
        user?.role === 'platform_admin' && !user?.organization_id;
    const isMechanic = user?.role === 'mechanic';
    const isOwner = user?.role === 'owner';
    const canCreateAppointments = ['owner', 'manager'].includes(
        user?.role ?? '',
    );
    const [mobileOpen, setMobileOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const pathname =
        typeof window !== 'undefined' ? window.location.pathname : '/';

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            const isSearchShortcut =
                (event.ctrlKey || event.metaKey) &&
                event.key.toLowerCase() === 'k';
            if (isSearchShortcut) {
                event.preventDefault();
                setSearchOpen(true);
            }

            if (event.key === 'Escape') {
                setSearchOpen(false);
                setProfileOpen(false);
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    const mobileItems: [string, LucideIcon, string][] = isMechanic
        ? [
              ['Мои заказы', ClipboardList, '/orders'],
              ['Уведомления', Bell, '/notifications'],
          ]
        : [
              ['Сегодня', LayoutDashboard, '/'],
              ['Заявки', CalendarDays, '/appointments'],
              ['Заказы', ClipboardList, '/orders'],
              ['Клиенты', Users, '/clients'],
              [
                  'Ещё',
                  isOwner ? Settings : UsersRound,
                  isOwner ? '/settings' : '/employees',
              ],
          ];

    const visibleSections = isMechanic
        ? [
              {
                  label: 'Моя работа',
                  items: [
                      {
                          label: 'Мои заказы',
                          icon: ClipboardList,
                          href: '/orders',
                      },
                      {
                          label: 'Уведомления',
                          icon: Bell,
                          href: '/notifications',
                      },
                  ],
              },
          ]
        : sections.map((section) => ({
              ...section,
              items: section.items.filter(
                  ({ label }) =>
                      (label !== 'Настройки' || isOwner) &&
                      (label !== 'Тариф и оплата' || ['owner', 'manager'].includes(user?.role ?? '')),
              ),
          }));

    const initials = useMemo(
        () =>
            (
                (user?.name || 'G')
                    .trim()
                    .split(/\s+/)
                    .slice(0, 2)
                    .map((part: string) => part[0])
                    .join('') || 'G'
            )
                .slice(0, 2)
                .toUpperCase(),
        [user?.name],
    );

    const roleLabels: Record<string, string> = {
        owner: 'Владелец',
        manager: 'Менеджер',
        mechanic: 'Механик',
        platform_admin: 'Platform Admin',
    };
    const roleLabel = roleLabels[user?.role ?? 'owner'] ?? 'Сотрудник';

    const todayLabel = `Сегодня · ${new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date())}`;

    return (
        <div className="min-h-screen bg-[var(--garage-background)] text-[var(--garage-text)]">
            {mobileOpen && (
                <button
                    aria-label="Закрыть навигацию"
                    className="fixed inset-0 z-30 bg-black/65 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                    type="button"
                />
            )}
            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 py-4 transition-transform duration-200 lg:translate-x-0 ${mobileOpen ? 'translate-x-0' : '-translate-x-full'}`}
            >
                <div className="flex items-center justify-between px-2 pb-5">
                    <div className="flex items-center gap-2.5">
                        <BrandLogo compact className="h-11 w-11" />
                    </div>
                    <button
                        aria-label="Закрыть навигацию"
                        className="text-[var(--garage-text-muted)] lg:hidden"
                        onClick={() => setMobileOpen(false)}
                        type="button"
                    >
                        <X size={17} />
                    </button>
                </div>

                <div className="mb-5 flex w-full items-center justify-between rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 py-2 text-left">
                    <span>
                        <span className="block text-xs font-medium">
                            {organization?.name || 'GarageOS'}
                        </span>
                        <span className="mt-0.5 block text-[11px] text-[var(--garage-text-muted)]">
                            Организация
                        </span>
                    </span>
                </div>

                <nav className="garage-sidebar-scroll flex-1 space-y-5 overflow-y-auto">
                    {isPlatformAdmin && (
                        <div>
                            <p className="mb-1 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                                Платформа
                            </p>
                            <Link
                                className={`group flex items-center gap-3 rounded-md px-3 py-2 text-[13px] font-medium transition-colors ${pathname.startsWith('/admin') ? 'bg-[rgba(184,255,61,0.08)] text-[var(--garage-accent)]' : 'text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]'}`}
                                href="/admin"
                            >
                                <ShieldCheck size={17} strokeWidth={1.8} />
                                SaaS Admin
                            </Link>
                        </div>
                    )}
                    {visibleSections.map((section) => (
                        <div key={section.label}>
                            <p className="mb-1 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                                {section.label}
                            </p>
                            <div className="space-y-0.5">
                                {section.items.map(
                                    ({ label, icon: Icon, href }) => {
                                        const isActive = href
                                            ? href === '/'
                                                ? pathname === '/'
                                                : pathname.startsWith(href)
                                            : false;
                                        return (
                                            <Link
                                                key={label}
                                                className={`group flex items-center gap-3 rounded-md px-3 py-2 text-[13px] font-medium transition-colors ${isActive ? 'bg-[rgba(184,255,61,0.08)] text-[var(--garage-accent)]' : 'text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]'}`}
                                                href={href ?? '#'}
                                            >
                                                <Icon
                                                    size={17}
                                                    strokeWidth={1.8}
                                                />
                                                {label}
                                            </Link>
                                        );
                                    },
                                )}
                            </div>
                        </div>
                    ))}
                </nav>

                <div className="mt-4 border-t border-[var(--garage-border-subtle)] pt-4">
                    <div className="relative">
                        <button
                            aria-controls="profile-menu"
                            aria-expanded={profileOpen}
                            className="flex w-full items-center gap-2.5 rounded-md px-2 py-2 text-left hover:bg-[var(--garage-surface-secondary)]"
                            onClick={() => setProfileOpen((open) => !open)}
                            type="button"
                        >
                            <span className="grid size-8 place-items-center rounded-full bg-[var(--garage-surface-elevated)] text-[11px] font-semibold text-[var(--garage-accent)]">
                                {initials}
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-xs font-medium">
                                    {user?.name || 'Пользователь'}
                                </span>
                                <span className="block text-[11px] text-[var(--garage-text-muted)]">
                                    {roleLabel}
                                </span>
                            </span>
                            <span
                                aria-hidden="true"
                                className="text-xs text-[var(--garage-text-muted)]"
                            >
                                ⌄
                            </span>
                        </button>
                        {profileOpen && (
                            <div
                                className="absolute inset-x-0 bottom-full z-50 mb-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-2 shadow-xl"
                                id="profile-menu"
                            >
                                <p className="truncate px-2 py-1 text-[11px] text-[var(--garage-text-muted)]">
                                    {user?.email || roleLabel}
                                </p>
                                <Form action="/logout" method="post">
                                    <button
                                        className="flex h-9 w-full items-center gap-2 rounded px-2 text-left text-xs text-[var(--garage-error)] hover:bg-[var(--garage-surface-secondary)]"
                                        onClick={() => setProfileOpen(false)}
                                        type="submit"
                                    >
                                        <LogOut size={14} />
                                        Выйти
                                    </button>
                                </Form>
                            </div>
                        )}
                    </div>
                </div>
            </aside>

            <main className="lg:pl-60">
                <header className="flex h-16 items-center justify-between border-b border-[var(--garage-border)] bg-[var(--garage-background)] px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <button
                            aria-label="Открыть навигацию"
                            className="text-[var(--garage-text-secondary)] lg:hidden"
                            onClick={() => setMobileOpen(true)}
                            type="button"
                        >
                            <Menu size={19} />
                        </button>
                        <div>
                            <p className="text-sm font-semibold">
                                {isMechanic ? 'Моя работа' : 'Сегодня'}
                            </p>
                            <p className="text-[11px] text-[var(--garage-text-muted)]">
                                {todayLabel}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 sm:gap-3">
                        {canCreateAppointments && (
                            <Link
                                className="inline-flex items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 py-2 text-[11px] font-semibold text-[var(--garage-background)] shadow-sm shadow-[rgba(184,255,61,0.15)] transition-opacity hover:opacity-95"
                                href="/appointments"
                            >
                                <Plus size={14} />
                                Новая заявка
                            </Link>
                        )}
                        <button
                            className="hidden h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs text-[var(--garage-text-muted)] sm:flex"
                            onClick={() => setSearchOpen(true)}
                            type="button"
                        >
                            <Search size={15} />
                            Поиск...
                            <kbd className="ml-4 rounded border border-[var(--garage-border)] px-1.5 py-0.5 text-[10px]">
                                Ctrl K
                            </kbd>
                        </button>
                        <Link
                            aria-label="Уведомления"
                            className="relative rounded-md p-2 text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface-secondary)]"
                            href="/notifications"
                        >
                            <Bell size={17} />
                            <span className="absolute right-1.5 top-1.5 size-1.5 rounded-full bg-[var(--garage-accent)]" />
                        </Link>
                        <span className="grid size-8 place-items-center rounded-full bg-[var(--garage-surface-elevated)] text-[11px] font-semibold text-[var(--garage-accent)]">
                            {initials}
                        </span>
                    </div>
                </header>

                <div className="mx-auto max-w-[1600px] p-4 pb-24 sm:p-6 sm:pb-8">
                    {props.flash?.success && (
                        <p
                            className="mb-4 flex items-center gap-2 rounded-md border border-[rgba(114,224,106,0.22)] bg-[rgba(114,224,106,0.06)] px-3 py-2 text-xs text-[var(--garage-success)]"
                            role="status"
                        >
                            <CheckCircle2 size={14} />
                            {props.flash.success}
                        </p>
                    )}
                    {children}
                </div>
            </main>

            <nav
                className="fixed inset-x-0 bottom-0 z-20 grid h-16 border-t border-[var(--garage-border)] bg-[var(--garage-surface)] lg:hidden"
                style={{
                    gridTemplateColumns: `repeat(${mobileItems.length}, minmax(0, 1fr))`,
                }}
            >
                {mobileItems.map(([label, Icon, href], index) => (
                    <Link
                        className={`flex flex-col items-center justify-center gap-1 text-[10px] ${pathname === href ? 'text-[var(--garage-accent)]' : 'text-[var(--garage-text-muted)]'}`}
                        href={href}
                        key={label}
                    >
                        <Icon size={17} />
                        <span>{label}</span>
                    </Link>
                ))}
            </nav>

            {searchOpen && (
                <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/60 px-4 pt-20">
                    <div className="w-full max-w-2xl rounded-[12px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4 shadow-2xl shadow-black/20">
                        <div className="flex items-center gap-2 border-b border-[var(--garage-border-subtle)] pb-3">
                            <Search
                                className="text-[var(--garage-text-muted)]"
                                size={17}
                            />
                            <input
                                autoFocus
                                className="flex-1 bg-transparent text-sm text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]"
                                onChange={(event) =>
                                    setSearchQuery(event.target.value)
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        const value = searchQuery.trim();
                                        window.location.href = value
                                            ? `/search?q=${encodeURIComponent(value)}`
                                            : '/search';
                                    }
                                }}
                                placeholder="Поиск клиента, машины, VIN, госномера или заказа..."
                                value={searchQuery}
                            />
                            <button
                                className="rounded-md border border-[var(--garage-border)] px-2 py-1 text-[10px] text-[var(--garage-text-muted)]"
                                onClick={() => setSearchOpen(false)}
                                type="button"
                            >
                                ESC
                            </button>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            {[
                                'BMW',
                                'VIN',
                                'ЗН-00001',
                                'Алексей',
                                'Toyota',
                            ].map((term) => (
                                <button
                                    className="rounded-full border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2.5 py-1 text-[11px] text-[var(--garage-text-secondary)]"
                                    key={term}
                                    onClick={() => {
                                        setSearchQuery(term);
                                        window.location.href = `/search?q=${encodeURIComponent(term)}`;
                                    }}
                                    type="button"
                                >
                                    {term}
                                </button>
                            ))}
                        </div>

                        <div className="mt-4 flex justify-end gap-2">
                            <button
                                className="h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]"
                                onClick={() => setSearchOpen(false)}
                                type="button"
                            >
                                Отмена
                            </button>
                            <Form
                                action="/search"
                                className="inline-block"
                                method="get"
                            >
                                <input
                                    name="q"
                                    type="hidden"
                                    value={searchQuery}
                                />
                                <button
                                    className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                                    onClick={() => setSearchOpen(false)}
                                    type="submit"
                                >
                                    Найти
                                </button>
                            </Form>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
