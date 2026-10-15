import { Form, Link, router } from '@inertiajs/react';
import {
    ArrowUpRight,
    ClipboardList,
    Plus,
    Search,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

type Car = { id: number; make: string; model: string; plate_number?: string };
type Client = { id: number; name: string; phone?: string | null; cars: Car[] };
type Service = { id: number; name: string; price: number };
type Order = {
    id: number;
    number: string;
    total?: number;
    balance?: number;
    client: { name: string };
    car: { make: string; model: string; plate_number?: string };
    mechanic?: { name: string } | null;
    status: { name: string; slug: string };
    items: { name_snapshot: string; quantity?: number }[];
};
type Props = {
    orders: { data: Order[]; total: number };
    clients: Client[];
    services: Service[];
    canCreate: boolean;
    canViewFinancials: boolean;
    selectedClientId?: number | null;
    selectedCarId?: number | null;
};
const filters = [
    { id: 'all', label: 'Все' },
    { id: 'active', label: 'В работе' },
    { id: 'ready', label: 'Готовы' },
    { id: 'closed', label: 'Закрыты' },
];
const activeSlugs = [
    'new',
    'accepted',
    'diagnostics',
    'in_progress',
    'waiting_parts',
];
const money = (value: number) => `₸${value.toLocaleString('ru-RU')}`;

export default function List({
    orders,
    clients,
    services,
    canCreate,
    canViewFinancials,
    selectedClientId,
    selectedCarId,
}: Props) {
    const [query, setQuery] = useState('');
    const [filter, setFilter] = useState('all');
    const [orderClientId, setOrderClientId] = useState<number | null>(selectedClientId ?? null);
    const [orderCarId, setOrderCarId] = useState<number | null>(selectedCarId ?? null);
    const [quickClientOpen, setQuickClientOpen] = useState(false);
    const [quickCarOpen, setQuickCarOpen] = useState(false);
    const [quickClientName, setQuickClientName] = useState('');
    const [quickClientPhone, setQuickClientPhone] = useState('');
    const [quickCarMake, setQuickCarMake] = useState('');
    const [quickCarModel, setQuickCarModel] = useState('');
    const [quickCreateError, setQuickCreateError] = useState('');
    const [quickCreateBusy, setQuickCreateBusy] = useState(false);

    useEffect(() => {
        setOrderClientId(selectedClientId ?? null);
        setOrderCarId(selectedCarId ?? null);
    }, [selectedClientId, selectedCarId]);

    const selectedClient = clients.find((client) => client.id === orderClientId);
    const availableCars = selectedClient?.cars ?? [];

    const createQuickClient = () => {
        setQuickCreateError('');
        setQuickCreateBusy(true);
        router.post('/clients', {
            name: quickClientName,
            phone: quickClientPhone,
            return_to: 'orders',
            car_id: orderCarId ?? '',
        }, {
            preserveScroll: true,
            onError: (errors) => setQuickCreateError(errors.name ?? errors.phone ?? 'Не удалось создать клиента.'),
            onFinish: () => setQuickCreateBusy(false),
        });
    };

    const createQuickCar = () => {
        if (!orderClientId) return;
        setQuickCreateError('');
        setQuickCreateBusy(true);
        router.post('/cars', {
            client_id: orderClientId,
            make: quickCarMake,
            model: quickCarModel,
            return_to: 'orders',
        }, {
            preserveScroll: true,
            onError: (errors) => setQuickCreateError(errors.make ?? errors.model ?? 'Не удалось добавить автомобиль.'),
            onFinish: () => setQuickCreateBusy(false),
        });
    };
    const filteredOrders = useMemo(() => {
        const search = query.trim().toLocaleLowerCase('ru');
        return orders.data.filter((order) => {
            const matchesFilter =
                filter === 'all' ||
                (filter === 'active' &&
                    activeSlugs.includes(order.status.slug)) ||
                (filter === 'ready' && order.status.slug === 'ready') ||
                (filter === 'closed' &&
                    ['delivered', 'closed', 'cancelled'].includes(
                        order.status.slug,
                    ));
            const matchesSearch =
                !search ||
                [
                    order.number,
                    order.client.name,
                    order.car.make,
                    order.car.model,
                    order.car.plate_number ?? '',
                ].some((value) =>
                    value.toLocaleLowerCase('ru').includes(search),
                );
            return matchesFilter && matchesSearch;
        });
    }, [orders.data, query, filter]);

    return (
        <AppLayout>
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Рабочее пространство
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Заказ-наряды
                    </h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                        Все автомобили в работе и их следующий этап.
                    </p>
                </div>
                <div className="flex gap-2">
                    {canViewFinancials && (
                        <Link
                            className="inline-flex h-9 items-center rounded-md border border-[var(--garage-border)] px-3 text-xs font-medium text-[var(--garage-text-secondary)] hover:bg-[var(--garage-surface-secondary)]"
                            href="/orders/kanban"
                        >
                            Ход работы
                        </Link>
                    )}
                    {canCreate && (
                        <a
                            className="inline-flex h-9 items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                            href="#new-order"
                        >
                            <Plus size={14} />
                            Новый заказ
                        </a>
                    )}
                </div>
            </div>

            <section className="mt-6 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="flex flex-col gap-3 border-b border-[var(--garage-border-subtle)] p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-sm font-semibold">
                            Заказы сервиса
                        </h2>
                        <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                            {orders.total} всего · выберите заказ, чтобы открыть
                            рабочую карту.
                        </p>
                    </div>
                    <label className="flex h-9 w-full items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)] sm:max-w-64">
                        <Search size={14} />
                        <input
                            className="min-w-0 flex-1 bg-transparent text-xs text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]"
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Заказ, клиент, автомобиль"
                            value={query}
                        />
                    </label>
                </div>

                <div className="flex gap-1 overflow-x-auto border-b border-[var(--garage-border-subtle)] px-4 py-2">
                    {filters.map((item) => (
                        <button
                            className={`shrink-0 rounded-md px-3 py-1.5 text-xs transition-colors ${filter === item.id ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)] hover:text-[var(--garage-text)]'}`}
                            key={item.id}
                            onClick={() => setFilter(item.id)}
                            type="button"
                        >
                            {item.label}
                        </button>
                    ))}
                </div>

                <div className="hidden grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)_150px_100px] gap-4 border-b border-[var(--garage-border-subtle)] px-4 py-2.5 text-[10px] font-medium uppercase text-[var(--garage-text-muted)] md:grid">
                    <span>Заказ · клиент</span>
                    <span>Автомобиль · работы</span>
                    <span>Этап</span>
                    {canViewFinancials && (
                        <span className="text-right">К оплате</span>
                    )}
                </div>

                {filteredOrders.length ? (
                    <div className="divide-y divide-[var(--garage-border-subtle)]">
                        {filteredOrders.map((order) => (
                            <Link
                                className={`grid gap-3 px-4 py-3.5 transition-colors hover:bg-[var(--garage-surface-secondary)] md:items-center md:gap-4 ${canViewFinancials ? 'md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)_150px_100px_16px]' : 'md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)_150px_16px]'}`}
                                href={`/orders/${order.id}`}
                                key={order.id}
                            >
                                <div className="flex min-w-0 items-center gap-3">
                                    <span className="grid size-9 shrink-0 place-items-center rounded-md bg-[var(--garage-surface-elevated)] text-[var(--garage-accent)]">
                                        <ClipboardList size={15} />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold">
                                            {order.number}
                                        </p>
                                        <p className="mt-1 truncate text-xs text-[var(--garage-text-muted)]">
                                            {order.client.name}
                                        </p>
                                    </div>
                                </div>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {order.car.make} {order.car.model}
                                    </p>
                                    <p className="mt-1 truncate text-[11px] text-[var(--garage-text-muted)]">
                                        {order.car.plate_number ||
                                            'Госномер не указан'}
                                        {order.items.length
                                            ? ` · ${order.items
                                                  .map(
                                                      (item) =>
                                                          item.name_snapshot,
                                                  )
                                                  .slice(0, 2)
                                                  .join(', ')}`
                                            : ''}
                                    </p>
                                </div>
                                <div className="flex items-center justify-between gap-3 md:block">
                                    <span className="text-[10px] text-[var(--garage-text-muted)] md:hidden">
                                        Этап
                                    </span>
                                    <span
                                        className={`inline-flex w-fit items-center rounded-md border px-2 py-1 text-[10px] ${order.status.slug === 'ready' ? 'border-[rgba(114,224,106,0.25)] bg-[rgba(114,224,106,0.08)] text-[var(--garage-success)]' : 'border-[var(--garage-border)] text-[var(--garage-text-secondary)]'}`}
                                    >
                                        {order.status.name}
                                    </span>
                                    <p className="mt-1 hidden truncate text-[10px] text-[var(--garage-text-muted)] md:block">
                                        {order.mechanic?.name ||
                                            'Мастер не назначен'}
                                    </p>
                                </div>
                                {canViewFinancials && (
                                    <div className="flex items-center justify-between text-xs md:block md:text-right">
                                        <span className="text-[10px] text-[var(--garage-text-muted)] md:hidden">
                                            К оплате
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {money(order.balance ?? 0)}
                                        </span>
                                    </div>
                                )}
                                <ArrowUpRight
                                    className="hidden text-[var(--garage-text-muted)] md:block"
                                    size={14}
                                />
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="grid min-h-52 place-items-center p-8 text-center">
                        <div>
                            <ClipboardList
                                className="mx-auto text-[var(--garage-text-muted)]"
                                size={22}
                            />
                            <p className="mt-3 text-sm font-medium">
                                {orders.data.length
                                    ? 'Заказы не найдены'
                                    : 'Заказов пока нет'}
                            </p>
                            <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                {orders.data.length
                                    ? 'Попробуйте изменить запрос или фильтр.'
                                    : 'Создайте заказ или примите автомобиль из заявки.'}
                            </p>
                        </div>
                    </div>
                )}
            </section>

            {canCreate && (
                <section
                    className="mt-4 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"
                    id="new-order"
                >
                    <div className="flex items-center gap-2">
                        <Plus
                            className="text-[var(--garage-accent)]"
                            size={16}
                        />
                        <h2 className="text-sm font-semibold">
                            Создать заказ без записи
                        </h2>
                    </div>
                    <Form
                        action="/orders"
                        method="post"
                        className="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    >
                        {({ processing, errors }) => (
                            <>
                                <label className="block text-xs font-medium">
                                    Клиент
                                    <select className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)]" name="client_id" onChange={(event) => { setOrderClientId(event.target.value ? Number(event.target.value) : null); setOrderCarId(null); }} value={orderClientId ?? ''}>
                                        <option value="">Выберите клиента</option>
                                        {clients.map((client) => <option key={client.id} value={client.id}>{client.name} · {client.phone || 'без телефона'}</option>)}
                                    </select>
                                    {errors.client_id && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{errors.client_id}</span>}
                                </label>
                                <label className="block text-xs font-medium">
                                    Автомобиль
                                    <select className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] disabled:opacity-50" disabled={!orderClientId || !availableCars.length} name="car_id" onChange={(event) => setOrderCarId(event.target.value ? Number(event.target.value) : null)} value={orderCarId ?? ''}>
                                        <option value="">{orderClientId ? 'Выберите автомобиль' : 'Сначала выберите клиента'}</option>
                                        {availableCars.map((car) => <option key={car.id} value={car.id}>{car.make} {car.model}{car.plate_number ? ` · ${car.plate_number}` : ''}</option>)}
                                    </select>
                                    {errors.car_id && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{errors.car_id}</span>}
                                </label>
                                <input name="mechanic_id" type="hidden" value="" />
                                <div className="space-y-2 sm:col-span-3">
                                    <button className="text-xs font-medium text-[var(--garage-accent)]" onClick={() => setQuickClientOpen((open) => !open)} type="button">{quickClientOpen ? 'Скрыть создание клиента' : '+ Добавить нового клиента'}</button>
                                    {quickClientOpen && <div className="grid gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3 sm:grid-cols-2">
                                        <input aria-label="Имя нового клиента" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickClientName(event.target.value)} placeholder="Имя и фамилия" value={quickClientName} />
                                        <input aria-label="Телефон нового клиента" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickClientPhone(event.target.value)} placeholder="Телефон" value={quickClientPhone} />
                                        <button className="h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs font-medium disabled:opacity-50 sm:col-span-2" disabled={quickCreateBusy || !quickClientName.trim() || !quickClientPhone.trim()} onClick={createQuickClient} type="button">{quickCreateBusy ? 'Сохраняем...' : 'Создать и выбрать клиента'}</button>
                                    </div>}
                                    {orderClientId && <button className="text-xs font-medium text-[var(--garage-accent)]" onClick={() => setQuickCarOpen((open) => !open)} type="button">{quickCarOpen ? 'Скрыть добавление авто' : '+ Добавить автомобиль выбранному клиенту'}</button>}
                                    {quickCarOpen && <div className="grid gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3 sm:grid-cols-2">
                                        <input aria-label="Марка нового автомобиля" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickCarMake(event.target.value)} placeholder="Марка" value={quickCarMake} />
                                        <input aria-label="Модель нового автомобиля" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickCarModel(event.target.value)} placeholder="Модель" value={quickCarModel} />
                                        <button className="h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs font-medium disabled:opacity-50 sm:col-span-2" disabled={quickCreateBusy || !quickCarMake.trim() || !quickCarModel.trim()} onClick={createQuickCar} type="button">{quickCreateBusy ? 'Сохраняем...' : 'Добавить и выбрать автомобиль'}</button>
                                    </div>}
                                    {quickCreateError && <p className="text-[11px] text-[var(--garage-error)]">{quickCreateError}</p>}
                                </div>
                                <button
                                    className="h-10 rounded-md bg-[var(--garage-accent)] px-4 text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50"
                                    disabled={processing || !orderClientId || !orderCarId}
                                    type="submit"
                                >
                                    {processing
                                        ? 'Создаём...'
                                        : 'Создать заказ'}
                                </button>
                            </>
                        )}
                    </Form>
                </section>
            )}
        </AppLayout>
    );
}

function SelectField({
    label,
    name,
    options,
    defaultValue,
    error,
}: {
    label: string;
    name: string;
    options: { value: number; label: string }[];
    defaultValue?: number | null;
    error?: string;
}) {
    return (
        <label className="block text-xs font-medium">
            {label}
            <select
                className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none focus:border-[var(--garage-accent)]"
                defaultValue={defaultValue ?? ''}
                name={name}
            >
                <option disabled value="">
                    Выберите вариант
                </option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error && (
                <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                    {error}
                </span>
            )}
        </label>
    );
}
