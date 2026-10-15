import { Form, Link, router } from '@inertiajs/react';
import {
    CalendarDays,
    CarFront,
    CheckCircle2,
    Clock3,
    Plus,
    Search,
    UserRound,
    Wrench,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import AppointmentCard from './AppointmentCard';
import CalendarNavigation, { dateKey } from './CalendarNavigation';

type Car = { id: number; make: string; model: string; plate_number?: string };
type Client = { id: number; name: string; phone?: string; cars: Car[] };
type Appointment = {
    id: number;
    order_id?: number | null;
    mechanic_id?: number | null;
    start_at: string;
    end_at: string;
    status: string;
    client: { name: string };
    car: { make: string; model: string; plate_number?: string };
    service: { name: string };
    mechanic?: { name: string };
};
type Props = {
    appointments: { data: Appointment[] };
    periodTotal: number;
    clients: Client[];
    services: { id: number; name: string; duration_minutes: number }[];
    mechanics: { id: number; name: string }[];
    appointmentStatuses: { id: number; name: string; slug: string }[];
    view?: 'day' | 'week' | 'month';
    date?: string;
    openCreate?: boolean;
    canCreateOrder?: boolean;
    selectedClientId?: number | null;
    selectedCarId?: number | null;
    filters: { q: string; status: string };
};

const statusLabels: Record<string, string> = {
    pending: 'Ожидает',
    confirmed: 'Подтверждена',
    arrived: 'Прибыл',
    completed: 'Завершена',
    cancelled: 'Отменена',
};

export default function Index({
    appointments,
    periodTotal,
    clients,
    services,
    mechanics,
    appointmentStatuses,
    view = 'day',
    date,
    openCreate = false,
    canCreateOrder = false,
    selectedClientId = null,
    selectedCarId: initialCarId = null,
    filters,
}: Props) {
    const currentDate = date ? new Date(`${date}T12:00:00`) : new Date();
    const currentKey = view === 'week' ? 'week' : 'day';
    const pendingSlugs = appointmentStatuses
        .filter((item) => ['pending', 'confirmed'].includes(item.slug))
        .map((item) => item.slug);
    const pendingCount = appointments.data.filter((item) =>
        pendingSlugs.includes(item.status),
    ).length;
    const statusLabel = (slug: string) =>
        appointmentStatuses.find((item) => item.slug === slug)?.name ??
        statusLabels[slug] ??
        slug;

    const defaultStart = useMemo(() => {
        const next = new Date();
        next.setMinutes(0, 0, 0);
        next.setHours(next.getHours() + 1);
        return toDateTimeLocal(next);
    }, []);

    const defaultEnd = useMemo(() => {
        const next = new Date();
        next.setMinutes(0, 0, 0);
        next.setHours(next.getHours() + 2);
        return toDateTimeLocal(next);
    }, []);

    const [wizardOpen, setWizardOpen] = useState(openCreate || selectedClientId !== null);
    const [wizardStep, setWizardStep] = useState(1);
    const [selectedClientIdState, setSelectedClientId] = useState<
        number | null
    >(selectedClientId);
    const [clientQuery, setClientQuery] = useState('');
    const [selectedCarId, setSelectedCarId] = useState<number | null>(initialCarId);
    const [selectedServiceId, setSelectedServiceId] = useState<number | null>(
        null,
    );
    const [selectedMechanicId, setSelectedMechanicId] = useState<number | null>(
        null,
    );
    const [availability, setAvailability] = useState<'available' | 'conflict' | 'error' | null>(null);
    const [checkingAvailability, setCheckingAvailability] = useState(false);
    const [startAt, setStartAt] = useState(defaultStart);
    const [endAt, setEndAt] = useState(defaultEnd);
    const [quickClientOpen, setQuickClientOpen] = useState(false);
    const [quickCarOpen, setQuickCarOpen] = useState(false);
    const [quickClientName, setQuickClientName] = useState('');
    const [quickClientPhone, setQuickClientPhone] = useState('');
    const [quickCarMake, setQuickCarMake] = useState('');
    const [quickCarModel, setQuickCarModel] = useState('');
    const [quickCreateError, setQuickCreateError] = useState('');
    const [quickCreateBusy, setQuickCreateBusy] = useState(false);

    useEffect(() => {
        if (selectedClientId) {
            setSelectedClientId(selectedClientId);
            setWizardStep(2);
            setWizardOpen(true);
        }
    }, [selectedClientId]);

    useEffect(() => {
        if (initialCarId) {
            setSelectedCarId(initialCarId);
            setWizardStep(3);
            setWizardOpen(true);
        }
    }, [initialCarId]);

    useEffect(() => {
        if (!selectedMechanicId || !startAt || !endAt || endAt <= startAt) {
            setAvailability(null);
            setCheckingAvailability(false);
            return;
        }

        const controller = new AbortController();
        const timeout = window.setTimeout(async () => {
            setCheckingAvailability(true);
            try {
                const params = new URLSearchParams({
                    mechanic_id: String(selectedMechanicId),
                    start_at: startAt,
                    end_at: endAt,
                });
                const response = await fetch(`/appointments/availability?${params}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Availability check failed');
                const result = (await response.json()) as { available: boolean };
                setAvailability(result.available ? 'available' : 'conflict');
            } catch (error) {
                if (!(error instanceof DOMException && error.name === 'AbortError')) {
                    setAvailability('error');
                }
            } finally {
                setCheckingAvailability(false);
            }
        }, 250);

        return () => {
            window.clearTimeout(timeout);
            controller.abort();
        };
    }, [selectedMechanicId, startAt, endAt]);

    const selectedClient =
        clients.find((client) => client.id === selectedClientIdState) ?? null;
    const visibleClients = clients.filter((client) => {
        const query = clientQuery.trim().toLocaleLowerCase('ru');
        return (
            !query ||
            `${client.name} ${client.phone ?? ''}`
                .toLocaleLowerCase('ru')
                .includes(query)
        );
    });
    const selectedCar =
        (selectedClient?.cars ?? []).find((car) => car.id === selectedCarId) ??
        null;
    const selectedService =
        services.find((service) => service.id === selectedServiceId) ?? null;
    const selectedMechanic =
        mechanics.find((mechanic) => mechanic.id === selectedMechanicId) ??
        null;

    const resetWizard = () => {
        setWizardOpen(false);
        setWizardStep(1);
        setSelectedClientId(null);
        setSelectedCarId(null);
        setSelectedServiceId(null);
        setSelectedMechanicId(null);
        setStartAt(defaultStart);
        setEndAt(defaultEnd);
    };

    const canAdvanceFromStep = () => {
        if (wizardStep === 1) return selectedClientId !== null;
        if (wizardStep === 2)
            return (
                selectedCarId !== null ||
                (selectedClient?.cars.length ?? 0) === 0
            );
        if (wizardStep === 3) return selectedServiceId !== null;
        return true;
    };

    const handleNext = () => {
        if (!canAdvanceFromStep()) return;
        setWizardStep((step) => Math.min(step + 1, 4));
    };

    const createQuickClient = () => {
        setQuickCreateError('');
        setQuickCreateBusy(true);
        router.post('/clients', {
            name: quickClientName,
            phone: quickClientPhone,
            return_to: 'appointments',
        }, {
            preserveScroll: true,
            onError: (errors) => setQuickCreateError(errors.name ?? errors.phone ?? 'Не удалось создать клиента.'),
            onFinish: () => setQuickCreateBusy(false),
        });
    };

    const createQuickCar = () => {
        if (!selectedClient) return;
        setQuickCreateError('');
        setQuickCreateBusy(true);
        router.post('/cars', {
            client_id: selectedClient.id,
            make: quickCarMake,
            model: quickCarModel,
            return_to: 'appointments',
        }, {
            preserveScroll: true,
            onError: (errors) => setQuickCreateError(errors.make ?? errors.model ?? 'Не удалось добавить автомобиль.'),
            onFinish: () => setQuickCreateBusy(false),
        });
    };

    return (
        <AppLayout>
            <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Рабочее пространство
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold">Заявки</h1>
                    <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                        Ближайшие визиты, активные заявки и загрузка мастеров.
                    </p>
                </div>

            </div>

            <CalendarNavigation date={dateKey(currentDate)} filters={filters} view="day" />
            <div className="mt-3 flex flex-wrap gap-2">
                <button
                    className="inline-flex items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 py-2 text-xs font-semibold text-[var(--garage-background)]"
                    onClick={() => setWizardOpen(true)}
                    type="button"
                >
                    <Plus size={15} />
                    Новая заявка
                </button>
            </div>

            <Form action="/appointments" method="get" className="mt-4 flex flex-col gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-3 sm:flex-row sm:items-center">
                <input type="hidden" name="view" value={currentKey} />
                <input type="hidden" name="date" value={dateKey(currentDate)} />
                <label className="flex h-9 min-w-0 flex-1 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-[var(--garage-text-muted)]">
                    <Search size={14} />
                    <input aria-label="Поиск заявок" className="min-w-0 flex-1 bg-transparent text-xs text-[var(--garage-text)] outline-none placeholder:text-[var(--garage-text-muted)]" defaultValue={filters.q} name="q" placeholder="Клиент, телефон, авто, госномер, услуга или мастер" />
                </label>
                <select aria-label="Фильтр по статусу" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-xs text-[var(--garage-text)]" defaultValue={filters.status} name="status">
                    <option value="">Все статусы</option>
                    {appointmentStatuses.map((item) => <option key={item.id} value={item.slug}>{item.name}</option>)}
                </select>
                <button className="h-9 rounded-md bg-[var(--garage-surface-elevated)] px-3 text-xs font-medium" type="submit">Найти</button>
            </Form>

            <div className="mt-5 grid gap-3 sm:grid-cols-3">
                <SummaryCard
                    title="Заявки"
                    value={String(periodTotal)}
                    detail="в текущем периоде"
                />
                <SummaryCard
                    title="Ожидают"
                    value={String(pendingCount)}
                    detail="подтверждения"
                />
                <SummaryCard
                    title="Мастера"
                    value={String(mechanics.length)}
                    detail="доступно"
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-[1fr_340px]">
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    {appointments.data.length ? (
                        <div className="space-y-2 p-2 sm:p-3">
                            {appointments.data.map((item) => (
                                <AppointmentCard appointment={item} canCreateOrder={canCreateOrder} key={item.id} statusLabel={statusLabel(item.status)} />
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-56 place-items-center p-8 text-center">
                            <div>
                                <CalendarDays
                                    className="mx-auto text-[var(--garage-text-muted)]"
                                    size={24}
                                />
                                <p className="mt-3 text-sm font-medium">
                                    Заявок пока нет
                                </p>
                                <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                    Создайте первую заявку и сервисный день
                                    начнётся автоматически.
                                </p>
                            </div>
                        </div>
                    )}
                </section>

                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                    <div className="flex items-center gap-2">
                        <Clock3
                            className="text-[var(--garage-accent)]"
                            size={16}
                        />
                        <h2 className="text-sm font-semibold">
                            Быстрая заявка
                        </h2>
                    </div>

                    <div className="mt-4 space-y-3">
                        <QuickStat
                            label="Клиент"
                            value={
                                selectedClient
                                    ? selectedClient.name
                                    : 'Не выбран'
                            }
                            icon={UserRound}
                        />
                        <QuickStat
                            label="Автомобиль"
                            value={
                                selectedCar
                                    ? `${selectedCar.make} ${selectedCar.model}`
                                    : 'Не выбран'
                            }
                            icon={CarFront}
                        />
                        <QuickStat
                            label="Услуга"
                            value={
                                selectedService
                                    ? selectedService.name
                                    : 'Не выбрана'
                            }
                            icon={Wrench}
                        />
                        <QuickStat
                            label="Время"
                            value={
                                startAt ? formatDateTime(startAt) : 'Не выбрано'
                            }
                            icon={CalendarDays}
                        />
                    </div>

                    <button
                        className="mt-5 h-9 w-full rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)]"
                        onClick={() => setWizardOpen(true)}
                        type="button"
                    >
                        Создать заявку
                    </button>
                </section>
            </div>

            {wizardOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4">
                    <div className="w-full max-w-2xl rounded-[14px] border border-[var(--garage-border)] bg-[var(--garage-surface)] shadow-2xl shadow-black/25">
                        <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3">
                            <div>
                                <p className="text-[11px] uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                                    Создание заявки
                                </p>
                                <h3 className="mt-1 text-base font-semibold">
                                    Новая заявка
                                </h3>
                            </div>
                            <button
                                className="rounded-md p-2 text-[var(--garage-text-muted)] hover:bg-[var(--garage-surface-secondary)]"
                                onClick={resetWizard}
                                type="button"
                                aria-label="Закрыть"
                            >
                                <X size={16} />
                            </button>
                        </div>

                        <div className="px-4 py-4">
                            <div className="mb-5 flex items-center gap-2">
                                {[1, 2, 3, 4].map((step) => (
                                    <div
                                        className="flex items-center gap-2 text-[10px] text-[var(--garage-text-muted)]"
                                        key={step}
                                    >
                                        <span
                                            className={`grid size-6 place-items-center rounded-full text-[10px] font-semibold ${wizardStep === step ? 'bg-[var(--garage-accent)] text-[var(--garage-background)]' : 'border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)]'}`}
                                        >
                                            {step}
                                        </span>
                                        {step < 4 && (
                                            <span className="h-px w-5 bg-[var(--garage-border)]" />
                                        )}
                                    </div>
                                ))}
                            </div>

                            <Form
                                action="/appointments"
                                method="post"
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        {wizardStep === 1 && (
                                            <div className="space-y-3">
                                                <div className="flex items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 py-2 text-[var(--garage-text-muted)]">
                                                    <Search size={15} />
                                                    <input
                                                        aria-label="Поиск клиента по имени или телефону"
                                                        className="w-full bg-transparent text-sm placeholder:text-[var(--garage-text-muted)] focus:outline-none"
                                                        onChange={(event) =>
                                                            setClientQuery(
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        placeholder="Поиск клиента по имени или телефону"
                                                        type="search"
                                                        value={clientQuery}
                                                    />
                                                </div>
                                                <div className="max-h-64 space-y-2 overflow-auto pr-1">
                                                    {visibleClients.map(
                                                        (client) => (
                                                            <button
                                                                className={`flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left transition-colors ${selectedClientIdState === client.id ? 'border-[var(--garage-accent)] bg-[rgba(184,255,61,0.08)]' : 'border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] hover:border-[var(--garage-text-muted)]'}`}
                                                                key={client.id}
                                                                onClick={() => {
                                                                    setSelectedClientId(
                                                                        client.id,
                                                                    );
                                                                    const firstCar =
                                                                        client
                                                                            .cars[0];
                                                                    setSelectedCarId(
                                                                        firstCar
                                                                            ? firstCar.id
                                                                            : null,
                                                                    );
                                                                }}
                                                                type="button"
                                                            >
                                                                <span>
                                                                    <span className="block text-sm font-medium text-[var(--garage-text)]">
                                                                        {
                                                                            client.name
                                                                        }
                                                                    </span>
                                                                    <span className="mt-0.5 block text-[11px] text-[var(--garage-text-muted)]">
                                                                        {
                                                                            client
                                                                                .cars
                                                                                .length
                                                                        }{' '}
                                                                        авто ·{' '}
                                                                        {client.phone ||
                                                                            'Телефон не указан'}
                                                                    </span>
                                                                </span>
                                                                <span className="text-[10px] text-[var(--garage-text-muted)]">
                                                                    Выбрать
                                                                </span>
                                                            </button>
                                                        ),
                                                    )}
                                                    {!visibleClients.length && (
                                                        <div className="rounded-md border border-dashed border-[var(--garage-border)] p-4 text-center">
                                                            <p className="text-xs text-[var(--garage-text-muted)]">
                                                                Клиент не найден
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                                <button className="inline-flex items-center gap-1.5 text-xs font-medium text-[var(--garage-accent)]" onClick={() => setQuickClientOpen((open) => !open)} type="button">
                                                    <Plus size={13} />{quickClientOpen ? 'Скрыть форму' : 'Добавить нового клиента'}
                                                </button>
                                                {quickClientOpen && <div className="grid gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3 sm:grid-cols-2">
                                                    <input aria-label="Имя нового клиента" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickClientName(event.target.value)} placeholder="Имя и фамилия" value={quickClientName} />
                                                    <input aria-label="Телефон нового клиента" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickClientPhone(event.target.value)} placeholder="Телефон" value={quickClientPhone} />
                                                    {quickCreateError && <p className="text-[11px] text-[var(--garage-error)] sm:col-span-2">{quickCreateError}</p>}
                                                    <button className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50 sm:col-span-2" disabled={quickCreateBusy || !quickClientName.trim() || !quickClientPhone.trim()} onClick={createQuickClient} type="button">{quickCreateBusy ? 'Сохраняем...' : 'Создать и выбрать клиента'}</button>
                                                </div>}
                                            </div>
                                        )}

                                        {wizardStep === 2 && selectedClient && (
                                            <div className="space-y-3">
                                                <div className="rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3">
                                                    <p className="text-xs uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                                                        Клиент
                                                    </p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {selectedClient.name}
                                                    </p>
                                                </div>
                                                <div className="space-y-2">
                                                    {(selectedClient.cars.length
                                                        ? selectedClient.cars
                                                        : [
                                                              {
                                                                  id: 0,
                                                                  make: 'Новый',
                                                                  model: 'автомобиль',
                                                                  plate_number:
                                                                      '',
                                                              },
                                                          ]
                                                    ).map((car) => (
                                                        <button
                                                            className={`flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left transition-colors ${selectedCarId === car.id ? 'border-[var(--garage-accent)] bg-[rgba(184,255,61,0.08)]' : 'border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] hover:border-[var(--garage-text-muted)]'}`}
                                                            key={
                                                                car.id ||
                                                                'new-car'
                                                            }
                                                            onClick={() =>
                                                                setSelectedCarId(
                                                                    car.id ||
                                                                        null,
                                                                )
                                                            }
                                                            type="button"
                                                        >
                                                            <span>
                                                                <span className="block text-sm font-medium text-[var(--garage-text)]">
                                                                    {car.make}{' '}
                                                                    {car.model}
                                                                </span>
                                                                <span className="mt-0.5 block text-[11px] text-[var(--garage-text-muted)]">
                                                                    {car.plate_number ||
                                                                        'Госномер не указан'}
                                                                </span>
                                                            </span>
                                                            <span className="text-[10px] text-[var(--garage-text-muted)]">
                                                                Выбрать
                                                            </span>
                                                        </button>
                                                    ))}
                                                </div>
                                                {!selectedClient.cars
                                                    .length && (
                                                    <div className="space-y-2">
                                                        <button className="inline-flex items-center gap-1.5 text-xs font-medium text-[var(--garage-accent)]" onClick={() => setQuickCarOpen((open) => !open)} type="button"><Plus size={13} />{quickCarOpen ? 'Скрыть форму' : 'Добавить автомобиль клиенту'}</button>
                                                        {quickCarOpen && <div className="grid gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3 sm:grid-cols-2">
                                                            <input aria-label="Марка автомобиля" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickCarMake(event.target.value)} placeholder="Марка" value={quickCarMake} />
                                                            <input aria-label="Модель автомобиля" className="h-9 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs" onChange={(event) => setQuickCarModel(event.target.value)} placeholder="Модель" value={quickCarModel} />
                                                            {quickCreateError && <p className="text-[11px] text-[var(--garage-error)] sm:col-span-2">{quickCreateError}</p>}
                                                            <button className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50 sm:col-span-2" disabled={quickCreateBusy || !quickCarMake.trim() || !quickCarModel.trim()} onClick={createQuickCar} type="button">{quickCreateBusy ? 'Сохраняем...' : 'Добавить и выбрать автомобиль'}</button>
                                                        </div>}
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        {wizardStep === 3 && (
                                            <div className="space-y-3">
                                                <div className="space-y-2">
                                                    {services.map((service) => (
                                                        <button
                                                            className={`flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left transition-colors ${selectedServiceId === service.id ? 'border-[var(--garage-accent)] bg-[rgba(184,255,61,0.08)]' : 'border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] hover:border-[var(--garage-text-muted)]'}`}
                                                            key={service.id}
                                                            onClick={() =>
                                                                {
                                                                    setSelectedServiceId(service.id);
                                                                    const nextEnd = new Date(startAt);
                                                                    if (!Number.isNaN(nextEnd.getTime())) {
                                                                        nextEnd.setMinutes(nextEnd.getMinutes() + service.duration_minutes);
                                                                        setEndAt(toDateTimeLocal(nextEnd));
                                                                    }
                                                                }
                                                            }
                                                            type="button"
                                                        >
                                                            <span>
                                                                <span className="block text-sm font-medium text-[var(--garage-text)]">
                                                                    {
                                                                        service.name
                                                                    }
                                                                </span>
                                                                <span className="mt-0.5 block text-[11px] text-[var(--garage-text-muted)]">
                                                                    {
                                                                        service.duration_minutes
                                                                    }{' '}
                                                                    мин.
                                                                </span>
                                                            </span>
                                                            <span className="text-[10px] text-[var(--garage-text-muted)]">
                                                                Выбрать
                                                            </span>
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {wizardStep === 4 &&
                                            selectedClient &&
                                            selectedCar &&
                                            selectedService && (
                                                <div className="space-y-4">
                                                    <div className="rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] p-3">
                                                        <p className="text-[11px] uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                                                            Проверьте заявку
                                                        </p>
                                                        <div className="mt-3 space-y-2 text-sm text-[var(--garage-text)]">
                                                            <p>
                                                                <span className="text-[var(--garage-text-muted)]">
                                                                    Клиент:
                                                                </span>{' '}
                                                                {
                                                                    selectedClient.name
                                                                }
                                                            </p>
                                                            <p>
                                                                <span className="text-[var(--garage-text-muted)]">
                                                                    Автомобиль:
                                                                </span>{' '}
                                                                {
                                                                    selectedCar.make
                                                                }{' '}
                                                                {
                                                                    selectedCar.model
                                                                }
                                                            </p>
                                                            <p>
                                                                <span className="text-[var(--garage-text-muted)]">
                                                                    Услуга:
                                                                </span>{' '}
                                                                {
                                                                    selectedService.name
                                                                }
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-3 sm:grid-cols-2">
                                                        <Field
                                                            label="Начало"
                                                            name="start_at"
                                                            type="datetime-local"
                                                            step={1800}
                                                            value={startAt}
                                                            onChange={(event) =>
                                                                setStartAt(
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            error={
                                                                errors.start_at
                                                            }
                                                        />
                                                        <Field
                                                            label="Окончание"
                                                            name="end_at"
                                                            type="datetime-local"
                                                            step={60}
                                                            value={endAt}
                                                            onChange={(event) =>
                                                                setEndAt(
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            error={
                                                                errors.end_at
                                                            }
                                                        />
                                                    </div>
                                                    {checkingAvailability && <p aria-live="polite" className="text-xs text-[var(--garage-text-muted)]">Проверяем загрузку мастера...</p>}
                                                    {availability === 'conflict' && <p aria-live="assertive" className="text-xs text-[var(--garage-error)]">Мастер занят в это время. Выберите другой интервал.</p>}
                                                    {availability === 'available' && <p aria-live="polite" className="text-xs text-[var(--garage-success)]">Это время свободно.</p>}
                                                    {availability === 'error' && <p aria-live="polite" className="text-xs text-[var(--garage-warning)]">Не удалось проверить график. Сервер всё равно проверит конфликт при сохранении.</p>}

                                                    <SelectFieldCompact
                                                        label="Мастер"
                                                        value={String(
                                                            selectedMechanicId ??
                                                                '',
                                                        )}
                                                        onChange={(value) =>
                                                            setSelectedMechanicId(
                                                                value === ''
                                                                    ? null
                                                                    : Number(
                                                                          value,
                                                                      ),
                                                            )
                                                        }
                                                        options={mechanics.map(
                                                            (mechanic) => ({
                                                                value: String(
                                                                    mechanic.id,
                                                                ),
                                                                label: mechanic.name,
                                                            }),
                                                        )}
                                                    />

                                                    <input
                                                        type="hidden"
                                                        name="client_id"
                                                        value={
                                                            selectedClient.id
                                                        }
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="car_id"
                                                        value={selectedCar.id}
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="service_id"
                                                        value={
                                                            selectedService.id
                                                        }
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="mechanic_id"
                                                        value={
                                                            selectedMechanicId ??
                                                            ''
                                                        }
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="start_at"
                                                        value={startAt}
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="end_at"
                                                        value={endAt}
                                                    />

                                                    <div className="flex justify-end gap-2">
                                                        <button
                                                            className="h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]"
                                                            onClick={() =>
                                                                setWizardStep(3)
                                                            }
                                                            type="button"
                                                        >
                                                            Назад
                                                        </button>
                                                        <button
                                                            className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                                                            disabled={
                                                                processing
                                                            }
                                                            type="submit"
                                                        >
                                                            {processing
                                                                ? 'Создаём...'
                                                                : 'Создать заявку'}
                                                        </button>
                                                    </div>
                                                </div>
                                            )}

                                        <div className="flex justify-end gap-2 pt-2">
                                            <button
                                                className="h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-text-secondary)]"
                                                onClick={() =>
                                                    setWizardStep((current) =>
                                                        Math.max(
                                                            1,
                                                            current - 1,
                                                        ),
                                                    )
                                                }
                                                type="button"
                                                disabled={wizardStep === 1}
                                            >
                                                Назад
                                            </button>
                                            {wizardStep < 4 ? (
                                                <button
                                                    className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                                                    onClick={handleNext}
                                                    type="button"
                                                    disabled={
                                                        !canAdvanceFromStep()
                                                    }
                                                >
                                                    Далее
                                                </button>
                                            ) : null}
                                        </div>
                                    </>
                                )}
                            </Form>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

function Field({
    label,
    name,
    type = 'text',
    value,
    onChange,
    step,
    error,
}: {
    label: string;
    name: string;
    type?: string;
    value?: string;
    onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
    step?: number;
    error?: string;
}) {
    return (
        <label className="block text-xs font-medium">
            {label}
            <input
                className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm outline-none focus:border-[var(--garage-accent)]"
                name={name}
                type={type}
                step={step}
                value={value}
                onChange={onChange}
            />
            {error && (
                <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                    {error}
                </span>
            )}
        </label>
    );
}

function SelectFieldCompact({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
}) {
    return (
        <label className="block text-xs font-medium">
            {label}
            <select
                className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none focus:border-[var(--garage-accent)]"
                value={value}
                onChange={(event) => onChange(event.target.value)}
            >
                <option className="bg-[var(--garage-surface)]" value="">
                    Не назначен
                </option>
                {options.map((option) => (
                    <option
                        className="bg-[var(--garage-surface)]"
                        key={option.value}
                        value={option.value}
                    >
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}

function QuickStat({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: string;
    icon: typeof UserRound;
}) {
    return (
        <div className="flex items-center gap-3 rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 py-2">
            <div className="grid size-8 place-items-center rounded-md bg-[var(--garage-surface)] text-[var(--garage-accent)]">
                <Icon size={15} />
            </div>
            <div>
                <p className="text-[10px] uppercase tracking-[0.12em] text-[var(--garage-text-muted)]">
                    {label}
                </p>
                <p className="mt-0.5 text-sm font-medium text-[var(--garage-text)]">
                    {value}
                </p>
            </div>
        </div>
    );
}

function SummaryCard({
    title,
    value,
    detail,
}: {
    title: string;
    value: string;
    detail: string;
}) {
    return (
        <div className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
            <p className="text-[11px] uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                {title}
            </p>
            <div className="mt-3 flex items-end justify-between gap-3">
                <span className="text-2xl font-semibold text-[var(--garage-text)]">
                    {value}
                </span>
                <span className="text-[11px] text-[var(--garage-text-muted)]">
                    {detail}
                </span>
            </div>
        </div>
    );
}

function formatTime(value: string) {
    return new Date(value).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function toDateTimeLocal(value: Date) {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');
    const hours = String(value.getHours()).padStart(2, '0');
    const minutes = String(value.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function formatDateTime(value: string) {
    const date = new Date(value);
    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}
