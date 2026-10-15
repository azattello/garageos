import { Form, Link } from '@inertiajs/react';
import {
    Archive,
    ArrowLeft,
    CarFront,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    CreditCard,
    Image,
    Pencil,
    RotateCcw,
    type LucideIcon,
    UserRound,
} from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type Car = {
    id: number;
    make: string;
    model: string;
    year?: number;
    plate_number?: string;
    vin?: string;
    color?: string;
    mileage?: number;
    fuel_type?: string;
    comment?: string;
    active: boolean;
    client: { id: number; name: string };
    orders: Order[];
    photos: { id: number; path: string; category: string; caption?: string }[];
    summary: {
        orders: number;
        total: number;
        paid: number;
        balance: number;
        active_order?: Order;
    };
};
type Order = {
    id: number;
    number: string;
    opened_at: string;
    total: number;
    paid_total: number;
    balance: number;
    status?: { name: string; is_terminal?: boolean };
    items: { name_snapshot: string }[];
    photos?: { id: number; path: string; category: string }[];
};
const money = (value: number) => `${value.toLocaleString('ru-RU')} ₸`;

export default function Show({ car }: { car: Car }) {
    const stats: Array<[string, string | number, LucideIcon]> = [
        ['Заказы', car.summary.orders, ClipboardList],
        ['Обслуживание', money(car.summary.total), CreditCard],
        ['Оплачено', money(car.summary.paid), CheckCircle2],
        ['Долг', money(car.summary.balance), CreditCard],
    ];

    return (
        <AppLayout>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <Link
                        className="inline-flex items-center gap-2 text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]"
                        href={`/clients/${car.client.id}`}
                    >
                        <ArrowLeft size={14} />
                        Клиент: {car.client.name}
                    </Link>
                    <div className="mt-4 flex items-center gap-3">
                        <span className="grid size-11 place-items-center rounded-lg bg-[var(--garage-surface-elevated)] text-[var(--garage-accent)]">
                            <CarFront size={22} />
                        </span>
                        <div>
                            <p className="text-xs uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                                Карточка автомобиля
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {car.make} {car.model}
                            </h1>
                        </div>
                    </div>
                    <p className="mt-2 text-sm text-[var(--garage-text-secondary)]">
                        {car.plate_number || 'Номер не указан'} · владелец:{' '}
                        {car.client.name}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {car.active && (
                        <Link
                            className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 text-xs text-[var(--garage-text-secondary)] hover:border-[var(--garage-accent)] hover:text-[var(--garage-text)]"
                            href={`/appointments?client_id=${car.client.id}&car_id=${car.id}`}
                        >
                            <CalendarDays size={14} />
                            Записать на обслуживание
                        </Link>
                    )}
                    {car.active && (
                        <Link
                            className="inline-flex h-9 items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]"
                            href={`/orders?client_id=${car.client.id}&car_id=${car.id}#new-order`}
                        >
                            <ClipboardList size={14} />
                            Новый заказ
                        </Link>
                    )}
                    {car.active ? (
                        <Form action={`/cars/${car.id}`} method="delete">
                            {({ processing, errors }) => (
                                <div>
                                    <button
                                        className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-error)]"
                                        disabled={processing}
                                        onClick={(event) => {
                                            if (
                                                !window.confirm(
                                                    'Архивировать автомобиль? История заказов сохранится.',
                                                )
                                            )
                                                event.preventDefault();
                                        }}
                                        type="submit"
                                    >
                                        <Archive size={14} />В архив
                                    </button>
                                    {errors.car && (
                                        <p
                                            className="mt-1 max-w-56 text-[10px] text-[var(--garage-error)]"
                                            role="alert"
                                        >
                                            {errors.car}
                                        </p>
                                    )}
                                </div>
                            )}
                        </Form>
                    ) : (
                        <>
                            <span className="inline-flex h-9 items-center rounded-md border border-[var(--garage-warning)]/30 px-3 text-xs text-[var(--garage-warning)]">
                                В архиве
                            </span>
                            <Form
                                action={`/cars/${car.id}/restore`}
                                method="post"
                            >
                                <button
                                    className="inline-flex h-9 items-center gap-2 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-success)]"
                                    type="submit"
                                >
                                    <RotateCcw size={14} />
                                    Восстановить
                                </button>
                            </Form>
                        </>
                    )}
                </div>
            </div>

            <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {stats.map(([label, value, Icon]) => (
                    <article
                        className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"
                        key={String(label)}
                    >
                        <div className="flex items-center justify-between">
                            <p className="text-xs text-[var(--garage-text-secondary)]">
                                {label}
                            </p>
                            <Icon
                                className="text-[var(--garage-text-muted)]"
                                size={16}
                            />
                        </div>
                        <p
                            className={`mt-3 text-xl font-semibold ${label === 'Долг' && car.summary.balance > 0 ? 'text-[var(--garage-warning)]' : ''}`}
                        >
                            {value}
                        </p>
                    </article>
                ))}
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                        <h2 className="text-sm font-semibold">
                            Технические данные
                        </h2>
                    </div>
                    <dl className="grid grid-cols-2 gap-4 p-4 text-sm">
                        <Data label="VIN" value={car.vin || 'Не указан'} />
                        <Data
                            label="Год"
                            value={car.year ? String(car.year) : 'Не указан'}
                        />
                        <Data
                            label="Пробег"
                            value={
                                car.mileage
                                    ? `${car.mileage.toLocaleString('ru-RU')} км`
                                    : 'Не указан'
                            }
                        />
                        <Data label="Цвет" value={car.color || 'Не указан'} />
                        <Data
                            label="Топливо"
                            value={car.fuel_type || 'Не указано'}
                        />
                        <Data
                            label="Госномер"
                            value={car.plate_number || 'Не указан'}
                        />
                    </dl>
                    {car.comment && (
                        <p className="border-t border-[var(--garage-border-subtle)] p-4 text-xs text-[var(--garage-text-muted)]">
                            {car.comment}
                        </p>
                    )}
                    <div className="border-t border-[var(--garage-border-subtle)] p-4">
                        <p className="text-xs text-[var(--garage-text-muted)]">
                            Владелец
                        </p>
                        <Link
                            className="mt-2 flex items-center gap-2 text-sm font-medium hover:text-[var(--garage-accent)]"
                            href={`/clients/${car.client.id}`}
                        >
                            <UserRound size={15} />
                            {car.client.name}
                        </Link>
                    </div>
                </section>
                <section className="overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                        <h2 className="text-sm font-semibold">
                            История обслуживания
                        </h2>
                    </div>
                    {car.orders.length ? (
                        <div className="divide-y divide-[var(--garage-border-subtle)]">
                            {car.orders.map((order) => (
                                <div
                                    className="grid gap-3 px-4 py-4 sm:grid-cols-[120px_1fr_auto] sm:items-center"
                                    key={order.id}
                                >
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {order.number}
                                        </p>
                                        <p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">
                                            {new Date(
                                                order.opened_at,
                                            ).toLocaleDateString('ru-RU')}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm">
                                            {order.items
                                                .map(
                                                    (item) =>
                                                        item.name_snapshot,
                                                )
                                                .join(', ') ||
                                                'Работы не добавлены'}
                                        </p>
                                        <span className="mt-1 inline-block rounded-full border border-[var(--garage-border)] px-2 py-0.5 text-[10px] text-[var(--garage-text-secondary)]">
                                            {order.status?.name ||
                                                'Без статуса'}
                                        </span>
                                    </div>
                                    <div className="text-left sm:text-right">
                                        <p className="text-sm font-semibold">
                                            {money(order.total)}
                                        </p>
                                        <p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">
                                            Долг: {money(order.balance)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-48 place-items-center p-8 text-sm text-[var(--garage-text-muted)]">
                            История обслуживания пока пуста.
                        </div>
                    )}
                </section>
            </div>

            {car.active && (
                <details className="mt-4 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <summary className="flex cursor-pointer list-none items-center gap-2 px-4 py-3.5 text-sm font-semibold">
                        <Pencil
                            className="text-[var(--garage-accent)]"
                            size={15}
                        />
                        Редактировать данные автомобиля
                    </summary>
                    <Form
                        action={`/cars/${car.id}`}
                        className="grid gap-3 border-t border-[var(--garage-border-subtle)] p-4 sm:grid-cols-2 xl:grid-cols-3"
                        method="put"
                    >
                        {({ processing, errors }) => (
                            <>
                                <CarField
                                    label="Марка"
                                    name="make"
                                    required
                                    defaultValue={car.make}
                                    error={errors.make}
                                />
                                <CarField
                                    label="Модель"
                                    name="model"
                                    required
                                    defaultValue={car.model}
                                    error={errors.model}
                                />
                                <CarField
                                    label="Год"
                                    name="year"
                                    type="number"
                                    defaultValue={car.year?.toString()}
                                    error={errors.year}
                                />
                                <CarField
                                    label="Госномер"
                                    name="plate_number"
                                    defaultValue={car.plate_number}
                                    error={errors.plate_number}
                                />
                                <CarField
                                    label="VIN"
                                    name="vin"
                                    defaultValue={car.vin}
                                    error={errors.vin}
                                />
                                <CarField
                                    label="Цвет"
                                    name="color"
                                    defaultValue={car.color}
                                    error={errors.color}
                                />
                                <CarField
                                    label="Пробег, км"
                                    name="mileage"
                                    type="number"
                                    defaultValue={car.mileage?.toString()}
                                    error={errors.mileage}
                                />
                                <CarField
                                    label="Топливо"
                                    name="fuel_type"
                                    defaultValue={car.fuel_type}
                                    error={errors.fuel_type}
                                />
                                <label className="block text-xs font-medium sm:col-span-2 xl:col-span-3">
                                    Комментарий
                                    <textarea
                                        className="mt-1.5 min-h-20 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 py-2 text-sm text-[var(--garage-text)]"
                                        defaultValue={car.comment}
                                        name="comment"
                                    />
                                    {errors.comment && (
                                        <span className="mt-1 block text-[10px] text-[var(--garage-error)]">
                                            {errors.comment}
                                        </span>
                                    )}
                                </label>
                                <button
                                    className="h-9 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50 sm:col-span-2 xl:col-span-3"
                                    disabled={processing}
                                    type="submit"
                                >
                                    {processing
                                        ? 'Сохраняем...'
                                        : 'Сохранить данные'}
                                </button>
                            </>
                        )}
                    </Form>
                </details>
            )}

            <section className="mt-4 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                <div className="flex items-center gap-2 border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                    <Image
                        size={16}
                        className="text-[var(--garage-text-muted)]"
                    />
                    <h2 className="text-sm font-semibold">Фотографии</h2>
                    <span className="text-xs text-[var(--garage-text-muted)]">
                        {car.photos.length}
                    </span>
                </div>
                {car.photos.length ? (
                    <div className="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4">
                        {car.photos.map((photo) => (
                            <div
                                className="overflow-hidden rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)]"
                                key={photo.id}
                            >
                                <img
                                    alt={photo.caption || photo.category}
                                    className="aspect-square w-full object-cover"
                                    src={`/storage/${photo.path.replace(/^public\//, '')}`}
                                />
                                <p className="truncate px-2 py-2 text-[11px] text-[var(--garage-text-muted)]">
                                    {photo.caption || photo.category}
                                </p>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="p-6 text-center text-sm text-[var(--garage-text-muted)]">
                        Фотографий пока нет.
                    </div>
                )}
            </section>
        </AppLayout>
    );
}
function CarField({
    label,
    name,
    defaultValue,
    type = 'text',
    required,
    error,
}: {
    label: string;
    name: string;
    defaultValue?: string;
    type?: string;
    required?: boolean;
    error?: string;
}) {
    return (
        <label className="block text-xs font-medium">
            {label}
            <input
                className="mt-1.5 h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-xs text-[var(--garage-text)]"
                defaultValue={defaultValue ?? ''}
                name={name}
                required={required}
                type={type}
            />
            {error && (
                <span className="mt-1 block text-[10px] text-[var(--garage-error)]">
                    {error}
                </span>
            )}
        </label>
    );
}

function Data({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-[11px] text-[var(--garage-text-muted)]">
                {label}
            </dt>
            <dd className="mt-1 break-words text-sm font-medium">{value}</dd>
        </div>
    );
}
