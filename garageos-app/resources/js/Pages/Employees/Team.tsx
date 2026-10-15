import { Form } from '@inertiajs/react';
import { BriefcaseBusiness, Check, ClipboardCheck, Clock3, Mail, Pencil, RefreshCw, Search, UserPlus, UserRoundCheck, UserRoundX, UsersRound, Wrench, X, type LucideIcon } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Bar } from '../../components/charts/bar';
import { BarChart } from '../../components/charts/bar-chart';
import { BarYAxis } from '../../components/charts/bar-y-axis';
import { chartCssVars } from '../../components/charts/chart-context';
import { ChartTooltip } from '../../components/charts/tooltip/chart-tooltip';
import { Grid } from '../../components/charts/grid';
import AppLayout from '../../Layouts/AppLayout';

type Employee = {
    id: number;
    name: string;
    email: string;
    phone?: string;
    specialization?: string | null;
    role: string;
    status: string;
    last_login_at?: string | null;
    active_orders_count: number;
    completed_orders_count: number;
};
type Invite = { id: number; email: string; role: string; expires_at: string };
type EmployeeMetric = { id: number; name: string; active_orders: number; completed_orders: number };
type Summary = { total: number; active: number; mechanics: number; pendingInvites: number; activeOrders: number; completedOrders30Days: number };
type Props = { employees: Employee[]; invites: Invite[]; employeeAnalytics: EmployeeMetric[]; summary: Summary; canManageAccounts: boolean };

const roleLabels: Record<string, string> = { owner: 'Владелец', manager: 'Менеджер', mechanic: 'Механик' };
const filters = [
    { id: 'all', label: 'Все' },
    { id: 'manager', label: 'Менеджеры' },
    { id: 'mechanic', label: 'Механики' },
];

export default function Team({ employees, invites, employeeAnalytics, summary, canManageAccounts }: Props) {
    const { props } = usePage<{ flash?: { success?: string; invite_url?: string } }>();
    const [query, setQuery] = useState('');
    const [roleFilter, setRoleFilter] = useState('all');
    const [copiedInvite, setCopiedInvite] = useState(false);
    const filteredEmployees = useMemo(() => {
        const normalizedQuery = query.trim().toLocaleLowerCase('ru');

        return employees.filter((employee) => {
            const matchesRole = roleFilter === 'all' || employee.role === roleFilter;
            const matchesQuery = !normalizedQuery || [employee.name, employee.email, employee.phone ?? '']
                .some((value) => value.toLocaleLowerCase('ru').includes(normalizedQuery));

            return matchesRole && matchesQuery;
        });
    }, [employees, query, roleFilter]);

    return (
        <AppLayout>
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Команда сервиса</p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">Сотрудники</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Состав команды и текущая загрузка без лишних экранов.</p>
                </div>
                <a className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[var(--garage-accent)] px-3.5 text-xs font-semibold text-[var(--garage-background)] transition-opacity hover:opacity-90" href="#invite-employee">
                    <UserPlus size={15} />Пригласить сотрудника
                </a>
            </div>

            {props.flash?.invite_url && (
                <section className="mt-3 flex flex-col gap-3 rounded-[10px] border border-[var(--garage-accent)]/30 bg-[rgba(184,255,61,0.04)] p-4 sm:flex-row sm:items-end">
                    <label className="min-w-0 flex-1 text-xs font-medium">Ссылка для сотрудника
                        <input className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs text-[var(--garage-text-secondary)] outline-none" readOnly value={props.flash.invite_url} />
                    </label>
                    <button className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs font-medium" onClick={async () => {
                        try {
                            await navigator.clipboard.writeText(props.flash?.invite_url ?? '');
                            setCopiedInvite(true);
                        } catch {
                            setCopiedInvite(false);
                        }
                    }} type="button">
                        {copiedInvite ? <ClipboardCheck size={14} /> : <ClipboardCheck size={14} />}{copiedInvite ? 'Скопировано' : 'Копировать ссылку'}
                    </button>
                </section>
            )}

            <section className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <SummaryCard label="В команде" value={summary.total} detail="учётных записей" icon={UsersRound} />
                <SummaryCard label="Активны" value={summary.active} detail="могут войти в систему" icon={Check} />
                <SummaryCard label="Механики" value={summary.mechanics} detail="в сервисной команде" icon={Wrench} />
                <SummaryCard label="Заказы в работе" value={summary.activeOrders} detail="текущая нагрузка" icon={BriefcaseBusiness} />
                <SummaryCard label="Завершено" value={summary.completedOrders30Days} detail={`${summary.pendingInvites} приглашений ожидают`} icon={Check} />
            </section>

            <section className="mt-4 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="flex flex-col justify-between gap-2 border-b border-[var(--garage-border-subtle)] px-4 py-3.5 sm:flex-row sm:items-center">
                    <div><h2 className="text-sm font-semibold">Загрузка мастеров</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Активные заказ-наряды сейчас и завершённые за 30 дней.</p></div>
                    <div className="flex flex-wrap gap-3 text-[10px] text-[var(--garage-text-muted)]"><span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--garage-accent)]" />В работе</span><span className="inline-flex items-center gap-1.5"><span className="size-2 rounded-sm bg-[var(--chart-3)]" />Завершено</span></div>
                </div>
                {employeeAnalytics.length ? <div className="p-3 sm:p-4">
                    <BarChart className="w-full" data={employeeAnalytics} xDataKey="name" orientation="horizontal" aspectRatio="2.8 / 1">
                        <Grid vertical />
                        <Bar dataKey="active_orders" fill={chartCssVars.linePrimary} />
                        <Bar dataKey="completed_orders" fill="var(--chart-3)" />
                        <BarYAxis maxLabels={8} />
                        <ChartTooltip />
                    </BarChart>
                </div> : <p className="px-4 py-6 text-center text-xs text-[var(--garage-text-muted)]">Назначайте заказ-наряды мастерам, чтобы сравнивать текущую загрузку и выполненную работу.</p>}
            </section>

            <div className="mt-6 grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                <section className="min-w-0 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="flex flex-col gap-3 border-b border-[var(--garage-border-subtle)] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-sm font-semibold">Состав команды</h2>
                            <p className="mt-1 text-xs text-[var(--garage-text-muted)]">Загрузка по активным заказам и результат за 30 дней.</p>
                        </div>
                        <label className="flex h-9 w-full items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)] sm:max-w-64">
                            <Search size={14} />
                            <input className="min-w-0 flex-1 bg-transparent text-xs text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]" onChange={(event) => setQuery(event.target.value)} placeholder="Имя, email или телефон" value={query} />
                        </label>
                    </div>

                    <div className="flex gap-1 overflow-x-auto border-b border-[var(--garage-border-subtle)] px-4 py-2">
                        {filters.map((filter) => (
                            <button className={`shrink-0 rounded-md px-3 py-1.5 text-xs transition-colors ${roleFilter === filter.id ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]'}`} key={filter.id} onClick={() => setRoleFilter(filter.id)} type="button">
                                {filter.label}
                                <span className="ml-2 text-[10px] text-[var(--garage-text-muted)]">{filter.id === 'all' ? employees.length : employees.filter((employee) => employee.role === filter.id).length}</span>
                            </button>
                        ))}
                    </div>

                    <div className="hidden grid-cols-[minmax(220px,1fr)_120px_110px_120px] gap-4 border-b border-[var(--garage-border-subtle)] px-4 py-2.5 text-[10px] font-medium uppercase text-[var(--garage-text-muted)] md:grid">
                        <span>Сотрудник</span><span>Роль</span><span>В работе</span><span>Завершено · 30 дн.</span>
                    </div>

                    {filteredEmployees.length ? (
                        <div className="divide-y divide-[var(--garage-border-subtle)]">
                            {filteredEmployees.map((employee) => (
                                <article className="grid gap-3 px-4 py-3.5 transition-colors hover:bg-[var(--garage-surface-secondary)] md:grid-cols-[minmax(220px,1fr)_120px_110px_120px] md:items-center md:gap-4" key={employee.id}>
                                    <div className="flex min-w-0 items-center gap-3">
                                        <span className="grid size-10 shrink-0 place-items-center rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-elevated)] text-xs font-semibold text-[var(--garage-accent)]">{initials(employee.name)}</span>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">{employee.name}</p>
                                            <p className="mt-1 flex min-w-0 items-center gap-1.5 truncate text-xs text-[var(--garage-text-muted)]"><span className={`size-1.5 shrink-0 rounded-full ${employee.status === 'active' ? 'bg-[var(--garage-success)]' : 'bg-[var(--garage-text-muted)]'}`} />{employee.email}{employee.phone ? ` · ${employee.phone}` : ''}</p>
                                            <p className="mt-1 truncate text-[10px] text-[var(--garage-text-muted)]">Последний вход: {formatLastLogin(employee.last_login_at)}</p>
                                            {employee.specialization && <p className="mt-1 truncate text-[10px] text-[var(--garage-text-secondary)]">{employee.specialization}</p>}
                                        </div>
                                    </div>
                                    <div className="flex items-center justify-between gap-2 md:block">
                                        <span className="text-[10px] text-[var(--garage-text-muted)] md:hidden">Роль</span>
                                        <div className="flex flex-wrap items-center gap-1.5"><span className="inline-flex items-center rounded-md border border-[var(--garage-border)] px-2 py-1 text-[10px] text-[var(--garage-text-secondary)]">{roleLabels[employee.role] ?? 'Сотрудник'}</span><span className={`inline-flex items-center rounded-md px-2 py-1 text-[9px] ${employee.status === 'active' ? 'bg-[rgba(114,224,106,0.1)] text-[var(--garage-success)]' : 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text-muted)]'}`}>{employee.status === 'active' ? 'Вход открыт' : 'Вход закрыт'}</span></div>
                                    </div>
                                    <MetricCell label="В работе" value={employee.active_orders_count} icon={BriefcaseBusiness} />
                                    <MetricCell label="Завершено · 30 дн." value={employee.completed_orders_count} icon={Check} />
                                    {canManageAccounts && employee.role !== 'owner' && <div className="flex flex-wrap items-center gap-2 md:col-span-4">
                                        <details className="group">
                                            <summary className="inline-flex h-8 cursor-pointer list-none items-center gap-1.5 rounded-md border border-[var(--garage-border)] px-2.5 text-[11px] text-[var(--garage-text-secondary)]"><Pencil size={12} />Изменить профиль</summary>
                                            <Form action={`/employees/${employee.id}`} method="put" className="mt-2 grid w-full gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-3 sm:grid-cols-3">
                                                <input aria-label="Имя сотрудника" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue={employee.name} name="name" required />
                                                <input aria-label="Телефон сотрудника" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue={employee.phone ?? ''} name="phone" />
                                                <input aria-label="Специализация сотрудника" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-2 text-xs" defaultValue={employee.specialization ?? ''} name="specialization" placeholder="Специализация" />
                                                <button className="inline-flex h-8 items-center justify-center gap-1.5 rounded-md bg-[var(--garage-accent)] px-3 text-[11px] font-semibold text-[var(--garage-background)] sm:col-span-3" type="submit"><Check size={12} />Сохранить</button>
                                            </Form>
                                        </details>
                                        <Form action={`/employees/${employee.id}/status`} method="post" onSubmit={(event) => { if (employee.status === 'active' && !window.confirm(`Деактивировать ${employee.name}?`)) event.preventDefault(); }}>
                                            <input name="active" type="hidden" value={employee.status === 'active' ? '0' : '1'} />
                                            <button className={`inline-flex h-8 items-center gap-1.5 rounded-md border px-2.5 text-[11px] ${employee.status === 'active' ? 'border-[var(--garage-error)]/30 text-[var(--garage-error)]' : 'border-[var(--garage-success)]/30 text-[var(--garage-success)]'}`} type="submit">{employee.status === 'active' ? <><UserRoundX size={12} />Деактивировать</> : <><UserRoundCheck size={12} />Активировать</>}</button>
                                        </Form>
                                    </div>}
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-48 place-items-center px-6 py-10 text-center">
                            <div>
                                <UsersRound className="mx-auto text-[var(--garage-text-muted)]" size={22} />
                                <p className="mt-3 text-sm font-medium">{employees.length ? 'Ничего не найдено' : 'Команда пока небольшая'}</p>
                                <p className="mt-1 text-xs text-[var(--garage-text-muted)]">{employees.length ? 'Измените запрос или фильтр.' : 'Пригласите коллег, чтобы распределять работу.'}</p>
                            </div>
                        </div>
                    )}
                </section>

                <aside className="space-y-4">
                    <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4" id="invite-employee">
                        <div className="flex items-center gap-2">
                            <UserPlus className="text-[var(--garage-accent)]" size={16} />
                            <h2 className="text-sm font-semibold">Новое приглашение</h2>
                        </div>
                        <p className="mt-2 text-xs leading-5 text-[var(--garage-text-muted)]">Создайте одноразовую ссылку и передайте сотруднику. По ней он задаст пароль, после чего сможет войти по email. Пароль можно восстановить на экране входа.</p>
                        <Form action="/employees/invite" method="post" className="mt-4 space-y-3">
                            {({ processing, errors }) => (
                                <>
                                    <label className="block text-xs font-medium">Рабочий email
                                        <span className="mt-1.5 flex h-10 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)] focus-within:border-[var(--garage-accent)]">
                                            <Mail size={14} />
                                            <input className="min-w-0 flex-1 bg-transparent text-sm text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]" name="email" placeholder="name@service.kz" type="email" />
                                        </span>
                                        {errors.email && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{errors.email}</span>}
                                    </label>
                                    <label className="block text-xs font-medium">Роль
                                        <select className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none focus:border-[var(--garage-accent)]" defaultValue="mechanic" name="role">
                                            <option value="mechanic">Механик</option>
                                            <option value="manager">Менеджер</option>
                                        </select>
                                        {errors.role && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{errors.role}</span>}
                                    </label>
                                    <button className="inline-flex h-9 w-full items-center justify-center gap-2 rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)] transition-opacity hover:opacity-90 disabled:opacity-50" disabled={processing} type="submit">
                                        <UserPlus size={14} />{processing ? 'Отправляем...' : 'Создать приглашение'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3">
                            <div>
                                <h2 className="text-sm font-semibold">Ожидают ответа</h2>
                                <p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">Ссылка действует 7 дней</p>
                            </div>
                            <span className="rounded-md bg-[var(--garage-surface-elevated)] px-2 py-1 text-[10px] text-[var(--garage-text-secondary)]">{invites.length}</span>
                        </div>
                        {invites.length ? (
                            <div className="divide-y divide-[var(--garage-border-subtle)]">
                                {invites.map((invite) => (
                                    <div className="flex items-center justify-between gap-3 px-4 py-3" key={invite.id}>
                                        <div className="min-w-0">
                                            <p className="truncate text-xs font-medium">{invite.email}</p>
                                            <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">{roleLabels[invite.role] ?? 'Сотрудник'}</p>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-1.5">
                                            <span className="inline-flex items-center gap-1 text-[10px] text-[var(--garage-warning)]"><Clock3 size={12} />Ожидает</span>
                                            <Form action={`/employees/invites/${invite.id}/resend`} method="post">
                                                <button aria-label={`Обновить ссылку для ${invite.email}`} className="rounded p-1.5 text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]" title="Создать новую ссылку" type="submit"><RefreshCw size={13} /></button>
                                            </Form>
                                            <Form action={`/employees/invites/${invite.id}/revoke`} method="post">
                                                <button aria-label={`Отозвать приглашение ${invite.email}`} className="rounded p-1.5 text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-error)]" title="Отозвать приглашение" type="submit"><X size={14} /></button>
                                            </Form>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="px-4 py-5 text-xs text-[var(--garage-text-muted)]">Активных приглашений нет.</p>
                        )}
                    </section>
                </aside>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value, detail, icon: Icon }: { label: string; value: number; detail: string; icon: LucideIcon }) {
    return (
        <article className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
            <div className="flex items-center justify-between gap-2">
                <p className="text-xs text-[var(--garage-text-secondary)]">{label}</p>
                <Icon className="text-[var(--garage-text-muted)]" size={15} />
            </div>
            <p className="mt-3 text-2xl font-semibold tabular-nums">{value}</p>
            <p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">{detail}</p>
        </article>
    );
}

function MetricCell({ label, value, icon: Icon }: { label: string; value: number; icon: LucideIcon }) {
    return (
        <div className="flex items-center justify-between gap-2 md:block">
            <span className="text-[10px] text-[var(--garage-text-muted)] md:hidden">{label}</span>
            <span className="inline-flex items-center gap-1.5 text-xs font-medium tabular-nums text-[var(--garage-text-secondary)]"><Icon className="text-[var(--garage-text-muted)]" size={13} />{value}</span>
        </div>
    );
}

function initials(name: string) {
    return name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
}

function formatLastLogin(value?: string | null) {
    if (!value) return 'ещё не входил';
    return new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}
