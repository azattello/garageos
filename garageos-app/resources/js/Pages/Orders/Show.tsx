import { Form, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CarFront,
    Check,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Copy,
    CreditCard,
    Circle,
    FileText,
    Gauge,
    Link2,
    Pencil,
    Trash2,
    UserRound,
    Wrench,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import PaymentForm from '../../components/PaymentForm';
import PhotoUploadForm from '../../components/PhotoUploadForm';

type Person = { id: number; name: string; phone?: string; email?: string };
type OrderItem = {
    id: number;
    type: string;
    name: string;
    quantity: number;
    unit_price: number | null;
    total: number | null;
};
type Payment = {
    id: number;
    amount: number;
    method: string;
    status: string;
    paid_at: string;
};
type Photo = {
    id: number;
    url: string;
    category: string;
    caption?: string;
    visible_to_client: boolean;
};
type Order = {
    id: number;
    number: string;
    client: Person;
    car: {
        id: number;
        make: string;
        model: string;
        year?: number;
        plate_number?: string;
        mileage?: number;
    };
    mechanic?: { id: number; name: string } | null;
    status: { name: string; slug: string };
    opened_at: string;
    planned_completion_at?: string | null;
    internal_comment?: string | null;
    client_comment?: string | null;
    subtotal: number | null;
    discount: number | null;
    total: number | null;
    paid_total: number | null;
    balance: number | null;
    items: OrderItem[];
    payments: Payment[];
    photos: Photo[];
};
type Props = {
    order: Order;
    services: { id: number; name: string; price?: number }[];
    mechanics: { id: number; name: string }[];
    parts: {
        id: number;
        name: string;
        available_stock: string | number;
        unit: string;
        sale_price: number;
    }[];
    nextStatuses: { slug: string; name: string }[];
    canManageFinances: boolean;
    canUpdate: boolean;
    publicOrderUrl?: string | null;
    hasActivePublicLink: boolean;
};

const paymentMethods: Record<string, string> = {
    cash: 'Наличные',
    card: 'Карта',
    kaspi: 'Kaspi',
    transfer: 'Перевод',
    other: 'Другое',
};
const photoCategories: Record<string, string> = {
    before: 'До ремонта',
    damage: 'Повреждение',
    process: 'Процесс',
    after: 'После ремонта',
    other: 'Другое',
};
const money = (value: number) => `₸${value.toLocaleString('ru-RU')}`;

export default function Show({
    order,
    services,
    mechanics,
    parts,
    nextStatuses,
    canManageFinances,
    canUpdate,
    publicOrderUrl,
    hasActivePublicLink,
}: Props) {
    const [copied, setCopied] = useState(false);
    const [itemType, setItemType] = useState<'service' | 'part'>('service');
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);
    const clientVisiblePhotos = order.photos.filter((photo) => photo.visible_to_client).length;
    const checklist = [
        { label: 'Добавить работу или запчасть', detail: order.items.length ? `${order.items.length} поз.` : 'Пока пусто', complete: order.items.length > 0, href: '#order-items' },
        { label: 'Назначить механика', detail: order.mechanic?.name || 'Не назначен', complete: Boolean(order.mechanic), href: '#order-assignment' },
        { label: 'Проверить оплату', detail: (order.balance ?? 0) > 0 ? `Остаток ${money(order.balance ?? 0)}` : 'Долга нет', complete: (order.balance ?? 0) <= 0, href: '#order-payment' },
        { label: 'Добавить фото для клиента', detail: clientVisiblePhotos ? `${clientVisiblePhotos} видно по ссылке` : 'Нет доступных клиенту фото', complete: clientVisiblePhotos > 0, href: '#order-photos' },
        { label: 'Ссылка отслеживания', detail: hasActivePublicLink ? 'Активна' : 'Ещё не создана', complete: hasActivePublicLink, href: '#order-tracking' },
    ];

    useEffect(() => {
        if (lightboxIndex === null) return;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setLightboxIndex(null);
            if (event.key === 'ArrowLeft') setLightboxIndex((index) => index === null ? null : (index + order.photos.length - 1) % order.photos.length);
            if (event.key === 'ArrowRight') setLightboxIndex((index) => index === null ? null : (index + 1) % order.photos.length);
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [lightboxIndex, order.photos.length]);

    const copyPublicLink = async () => {
        if (!publicOrderUrl) return;
        await navigator.clipboard.writeText(publicOrderUrl);
        setCopied(true);
    };

    return (
        <AppLayout>
            <Link
                className="inline-flex items-center gap-2 text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]"
                href="/orders"
            >
                <ArrowLeft size={14} />
                Все заказ-наряды
            </Link>

            <div className="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">
                        Рабочая карта
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                        {order.number}
                    </h1>
                    <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                        Создан {formatDate(order.opened_at)}
                    </p>
                </div>
                <span className="inline-flex w-fit items-center gap-2 rounded-md border border-[rgba(98,168,255,0.25)] bg-[rgba(98,168,255,0.08)] px-3 py-2 text-xs font-medium text-[var(--garage-info)]">
                    <span className="size-1.5 rounded-full bg-current" />
                    {order.status.name}
                </span>
            </div>

            <div className="mt-5 grid gap-3 md:grid-cols-3">
                <InfoBlock
                    icon={UserRound}
                    label="Клиент"
                    title={order.client.name}
                    detail={
                        order.client.phone ||
                        order.client.email ||
                        'Контакты не указаны'
                    }
                />
                <InfoBlock
                    icon={CarFront}
                    label="Автомобиль"
                    title={`${order.car.make} ${order.car.model}`}
                    detail={
                        [order.car.year, order.car.plate_number]
                            .filter(Boolean)
                            .join(' · ') || 'Госномер не указан'
                    }
                />
                <InfoBlock
                    icon={Wrench}
                    label="Мастер"
                    title={order.mechanic?.name || 'Не назначен'}
                    detail={
                        order.car.mileage
                            ? `${order.car.mileage.toLocaleString('ru-RU')} км`
                            : 'Пробег не указан'
                    }
                />
            </div>

            {canManageFinances && <>
                <Form action={`/orders/${order.id}/mechanic`} className="mt-3 flex flex-col gap-3 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-3 sm:flex-row sm:items-end" id="order-assignment" method="put">
                    {({ processing, errors }) => <>
                        <label className="min-w-0 flex-1 text-xs font-medium">Ответственный механик
                            <select aria-label="Назначить механика на заказ" className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)]" defaultValue={order.mechanic?.id ?? ''} name="mechanic_id">
                                <option value="">Не назначен</option>
                                {mechanics.map((mechanic) => <option key={mechanic.id} value={mechanic.id}>{mechanic.name}</option>)}
                            </select>
                            {errors.mechanic_id && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{errors.mechanic_id}</span>}
                        </label>
                        <button className="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-4 text-xs font-medium text-[var(--garage-text)] hover:border-[var(--garage-accent)] disabled:opacity-50" disabled={processing} type="submit"><Wrench size={14} />{processing ? 'Сохраняем...' : 'Сохранить мастера'}</button>
                    </>}
                </Form>

                <section aria-label="Проверка рабочей карточки" className="mt-3 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                    <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"><div><h2 className="text-sm font-semibold">Проверка карточки</h2><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Быстрый переход к незаполненным блокам и действиям.</p></div><span className="text-[10px] text-[var(--garage-text-muted)]">{checklist.filter((item) => item.complete).length} из {checklist.length} отмечено</span></div>
                    <div className="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                        {checklist.map((item) => <a className="flex min-w-0 items-start gap-2.5 rounded-md border border-[var(--garage-border-subtle)] bg-[var(--garage-surface-secondary)]/50 p-2.5 transition-colors hover:border-[var(--garage-text-muted)]" href={item.href} key={item.label}>
                            {item.complete ? <CheckCircle2 className="mt-0.5 shrink-0 text-[var(--garage-success)]" size={15} /> : <Circle className="mt-0.5 shrink-0 text-[var(--garage-warning)]" size={15} />}
                            <span className="min-w-0 flex-1"><span className="block truncate text-[10px] font-medium text-[var(--garage-text)]">{item.label}</span><span className={`mt-1 block truncate text-[10px] ${item.complete ? 'text-[var(--garage-text-muted)]' : 'text-[var(--garage-warning)]'}`}>{item.detail}</span></span>
                            <ArrowRight className="mt-0.5 shrink-0 text-[var(--garage-text-muted)]" size={13} />
                        </a>)}
                    </div>
                </section>
            </>}

            <div className="mt-4 grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                <div className="space-y-4">
                    <section className="scroll-mt-24 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]" id="order-items">
                        <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                            <div>
                                <h2 className="text-sm font-semibold">
                                    Работы и детали
                                </h2>
                                <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                    {order.items.length} позиции
                                </p>
                            </div>
                            <ClipboardList
                                className="text-[var(--garage-text-muted)]"
                                size={16}
                            />
                        </div>
                        {order.items.length ? (
                            <div className="divide-y divide-[var(--garage-border-subtle)]">
                                {order.items.map((item) => (
                                    <div
                                        className="grid grid-cols-[minmax(0,1fr)_70px_auto] items-center gap-3 px-4 py-3"
                                        key={item.id}
                                    >
                                        <p className="truncate text-sm font-medium">
                                            {item.name}
                                            <span className="ml-2 text-[10px] font-normal text-[var(--garage-text-muted)]">
                                                {item.type === 'part'
                                                    ? 'Запчасть'
                                                    : 'Работа'}
                                            </span>
                                        </p>
                                        <p className="text-right text-xs tabular-nums text-[var(--garage-text-muted)]">
                                            × {item.quantity}
                                        </p>
                                        {canManageFinances && (
                                            <p className="text-right text-sm font-medium tabular-nums">
                                                {money(item.total ?? 0)}
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                icon={Wrench}
                                text="Работы пока не добавлены."
                            />
                        )}

                        {canUpdate && (
                            <div className="border-t border-[var(--garage-border-subtle)] bg-[var(--garage-surface-secondary)]/40 p-4">
                                <Form
                                    action={`/orders/${order.id}/items`}
                                    method="post"
                                    className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_110px_auto] sm:items-end"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div>
                                                {canManageFinances &&
                                                    parts.length > 0 && (
                                                        <div
                                                            aria-label="Тип позиции"
                                                            className="mb-2 inline-flex rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-0.5"
                                                            role="group"
                                                        >
                                                            <button
                                                                className={`rounded px-2.5 py-1 text-[10px] ${itemType === 'service' ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)]'}`}
                                                                onClick={() =>
                                                                    setItemType(
                                                                        'service',
                                                                    )
                                                                }
                                                                type="button"
                                                            >
                                                                Работа
                                                            </button>
                                                            <button
                                                                className={`rounded px-2.5 py-1 text-[10px] ${itemType === 'part' ? 'bg-[var(--garage-surface-elevated)] text-[var(--garage-text)]' : 'text-[var(--garage-text-muted)]'}`}
                                                                onClick={() =>
                                                                    setItemType(
                                                                        'part',
                                                                    )
                                                                }
                                                                type="button"
                                                            >
                                                                Запчасть
                                                            </button>
                                                        </div>
                                                    )}
                                                {canManageFinances &&
                                                    parts.length === 0 && (
                                                        <Link
                                                            className="mb-2 inline-block text-[10px] text-[var(--garage-info)] hover:underline"
                                                            href="/inventory"
                                                        >
                                                            Запчастей на складе
                                                            нет · добавить
                                                        </Link>
                                                    )}
                                                <label className="block text-xs font-medium">
                                                    {itemType === 'part'
                                                        ? 'Запчасть со склада'
                                                        : 'Добавить работу'}
                                                    <input
                                                        name="type"
                                                        type="hidden"
                                                        value={itemType}
                                                    />
                                                    <select
                                                        className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none focus:border-[var(--garage-accent)]"
                                                        defaultValue=""
                                                        name={
                                                            itemType === 'part'
                                                                ? 'part_id'
                                                                : 'service_id'
                                                        }
                                                    >
                                                        <option
                                                            disabled
                                                            value=""
                                                        >
                                                            {itemType === 'part'
                                                                ? 'Выберите запчасть'
                                                                : 'Выберите услугу'}
                                                        </option>
                                                        {itemType === 'part'
                                                            ? parts.map(
                                                                  (part) => (
                                                                      <option
                                                                          key={
                                                                              part.id
                                                                          }
                                                                          value={
                                                                              part.id
                                                                          }
                                                                      >
                                                                          {
                                                                              part.name
                                                                          }{' '}
                                                                          ·
                                                                          доступно{' '}
                                                                          {Number(
                                                                              part.available_stock,
                                                                          ).toLocaleString(
                                                                              'ru-RU',
                                                                          )}{' '}
                                                                          {
                                                                              part.unit
                                                                          }{' '}
                                                                          ·{' '}
                                                                          {money(
                                                                              part.sale_price,
                                                                          )}
                                                                      </option>
                                                                  ),
                                                              )
                                                            : services.map(
                                                                  (service) => (
                                                                      <option
                                                                          key={
                                                                              service.id
                                                                          }
                                                                          value={
                                                                              service.id
                                                                          }
                                                                      >
                                                                          {
                                                                              service.name
                                                                          }
                                                                          {canManageFinances &&
                                                                          service.price !==
                                                                              undefined
                                                                              ? ` · ${money(service.price)}`
                                                                              : ''}
                                                                      </option>
                                                                  ),
                                                              )}
                                                    </select>
                                                    {errors.service_id && (
                                                        <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                                                            {errors.service_id}
                                                        </span>
                                                    )}
                                                    {errors.part_id && (
                                                        <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                                                            {errors.part_id}
                                                        </span>
                                                    )}
                                                </label>
                                            </div>
                                            <label className="block text-xs font-medium">
                                                Количество
                                                <input
                                                    className="mt-1.5 h-10 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm outline-none focus:border-[var(--garage-accent)]"
                                                    defaultValue="1"
                                                    min="0.01"
                                                    name="quantity"
                                                    step="0.01"
                                                    type="number"
                                                />
                                                {errors.quantity && (
                                                    <span className="mt-1 block text-[11px] text-[var(--garage-error)]">
                                                        {errors.quantity}
                                                    </span>
                                                )}
                                            </label>
                                            <button
                                                className="h-10 rounded-md bg-[var(--garage-accent)] px-4 text-xs font-semibold text-[var(--garage-background)] disabled:opacity-50"
                                                disabled={processing}
                                                type="submit"
                                            >
                                                {processing
                                                    ? 'Добавляем...'
                                                    : 'Добавить'}
                                            </button>
                                        </>
                                    )}
                                </Form>
                            </div>
                        )}
                    </section>

                    <section className="scroll-mt-24 overflow-hidden rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]" id="order-photos">
                        <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3.5">
                            <div>
                                <h2 className="text-sm font-semibold">
                                    Фото автомобиля
                                </h2>
                                <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                    {clientVisiblePhotos} из {order.photos.length} фото доступны клиенту по ссылке
                                </p>
                            </div>
                            <CarFront
                                className="text-[var(--garage-text-muted)]"
                                size={16}
                            />
                        </div>
                        {order.photos.length ? (
                            <div className="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                                {order.photos.map((photo, index) => (
                                    <figure
                                        className="overflow-hidden rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)]"
                                        key={photo.id}
                                    >
                                        <button aria-label={`Открыть фото: ${photo.caption || photoCategories[photo.category] || 'Фото автомобиля'}`} className="block w-full cursor-zoom-in" onClick={() => setLightboxIndex(index)} type="button">
                                            <img
                                                alt={photo.caption || photoCategories[photo.category] || 'Фото автомобиля'}
                                                className="aspect-[4/3] w-full object-cover"
                                                loading="lazy"
                                                src={photo.url}
                                            />
                                        </button>
                                        <figcaption className="flex items-center justify-between gap-2 px-3 py-2 text-[10px] text-[var(--garage-text-muted)]">
                                            <span className="truncate">
                                                {photo.caption ||
                                                    photoCategories[
                                                        photo.category
                                                    ] ||
                                                    'Фото'}
                                            </span>
                                            {canManageFinances && (
                                                <span className="shrink-0">
                                                    {photo.visible_to_client
                                                        ? 'Видно клиенту'
                                                        : 'Внутреннее'}
                                                </span>
                                            )}
                                        </figcaption>
                                        {canManageFinances && <details className="border-t border-[var(--garage-border)] px-3 py-2">
                                            <summary className="flex cursor-pointer list-none items-center gap-1.5 text-[10px] text-[var(--garage-text-secondary)]"><Pencil size={12} />Редактировать фото</summary>
                                            <Form action={`/orders/${order.id}/photos/${photo.id}`} className="mt-2 space-y-2" method="put">
                                                <select aria-label="Категория фото" className="h-8 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-xs" defaultValue={photo.category} name="category">
                                                    {Object.entries(photoCategories).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                                                </select>
                                                <input aria-label="Подпись фото" className="h-8 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-2 text-xs" defaultValue={photo.caption ?? ''} maxLength={160} name="caption" placeholder="Подпись" />
                                                <label className="flex items-center gap-2 text-[10px] text-[var(--garage-text-secondary)]"><input className="accent-[var(--garage-accent)]" defaultChecked={photo.visible_to_client} name="visible_to_client" type="checkbox" value="1" />Показывать клиенту</label>
                                                <button className="inline-flex h-8 items-center gap-1.5 rounded-md bg-[var(--garage-accent)] px-2.5 text-[10px] font-semibold text-[var(--garage-background)]" type="submit"><Check size={12} />Сохранить</button>
                                            </Form>
                                            <Form action={`/orders/${order.id}/photos/${photo.id}`} className="mt-2" method="delete" onSubmit={(event) => { if (!window.confirm('Архивировать это фото? Клиент больше не сможет открыть его.')) event.preventDefault(); }}>
                                                <button className="inline-flex h-8 items-center gap-1.5 rounded-md border border-[var(--garage-error)]/30 px-2.5 text-[10px] text-[var(--garage-error)]" type="submit"><Trash2 size={12} />Архивировать</button>
                                            </Form>
                                        </details>}
                                    </figure>
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                icon={CarFront}
                                text="Фото ремонта появятся в этой карточке."
                            />
                        )}
                    </section>
                    {canUpdate && <div className="scroll-mt-24" id="photo-upload"><PhotoUploadForm order={order} /></div>}

                    {(order.client_comment || order.internal_comment) && (
                        <section className="grid gap-3 sm:grid-cols-2">
                            {order.client_comment && (
                                <CommentBlock
                                    label="Комментарий клиента"
                                    text={order.client_comment}
                                />
                            )}
                            {order.internal_comment && canManageFinances && (
                                <CommentBlock
                                    label="Внутренняя заметка"
                                    text={order.internal_comment}
                                />
                            )}
                        </section>
                    )}
                </div>

                <aside className="space-y-4">
                    {canManageFinances && (
                        <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                            <div className="flex items-center gap-2">
                                <CreditCard
                                    className="text-[var(--garage-accent)]"
                                    size={16}
                                />
                                <h2 className="text-sm font-semibold">
                                    Расчёт
                                </h2>
                            </div>
                            <div className="mt-4 space-y-3 text-xs">
                                <SummaryRow
                                    label="Работы"
                                    value={money(order.subtotal ?? 0)}
                                />
                                {(order.discount ?? 0) > 0 && (
                                    <SummaryRow
                                        label="Скидка"
                                        value={`−${money(order.discount ?? 0)}`}
                                    />
                                )}
                                <SummaryRow
                                    label="Итого"
                                    value={money(order.total ?? 0)}
                                    strong
                                />
                                <SummaryRow
                                    label="Оплачено"
                                    value={money(order.paid_total ?? 0)}
                                />
                                <div className="border-t border-[var(--garage-border-subtle)] pt-3">
                                    <SummaryRow
                                        label="К оплате"
                                        value={money(order.balance ?? 0)}
                                        strong
                                        warning={(order.balance ?? 0) > 0}
                                    />
                                </div>
                            </div>
                        </section>
                    )}

                    <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                        <div className="flex items-center gap-2">
                            <Gauge
                                className="text-[var(--garage-accent)]"
                                size={16}
                            />
                            <h2 className="text-sm font-semibold">
                                Следующий этап
                            </h2>
                        </div>
                        <p className="mt-2 text-xs text-[var(--garage-text-muted)]">
                            Сейчас: {order.status.name}
                        </p>
                        {nextStatuses.length ? (
                            <div className="mt-4 space-y-2">
                                {nextStatuses.map((status) => {
                                    const closingWithDebt =
                                        status.slug === 'closed' &&
                                        (order.balance ?? 0) > 0;

                                    return (
                                        <Form
                                            action={`/orders/${order.id}/status`}
                                            method="post"
                                            key={status.slug}
                                            onSubmit={(event) => {
                                                if (
                                                    closingWithDebt &&
                                                    canManageFinances &&
                                                    !window.confirm(
                                                        `Закрыть заказ с остатком ${money(order.balance ?? 0)}? Долг останется в финансовой истории.`,
                                                    )
                                                ) {
                                                    event.preventDefault();
                                                }
                                            }}
                                        >
                                            <input
                                                name="status"
                                                type="hidden"
                                                value={status.slug}
                                            />
                                            {closingWithDebt &&
                                                canManageFinances && (
                                                    <label className="mb-2 flex items-start gap-2 rounded-md border border-[var(--garage-warning)]/30 bg-[var(--garage-warning)]/5 p-2.5 text-[10px] leading-4 text-[var(--garage-text-secondary)]">
                                                        <input
                                                            className="mt-0.5 accent-[var(--garage-accent)]"
                                                            name="allow_unpaid"
                                                            required
                                                            type="checkbox"
                                                            value="1"
                                                        />
                                                        <span>
                                                            Подтверждаю закрытие
                                                            с неоплаченным
                                                            остатком{' '}
                                                            {money(
                                                                order.balance ??
                                                                    0,
                                                            )}
                                                            .
                                                        </span>
                                                    </label>
                                                )}
                                            {closingWithDebt &&
                                                !canManageFinances && (
                                                    <p
                                                        className="mb-2 rounded-md border border-[var(--garage-warning)]/30 p-2 text-[10px] text-[var(--garage-warning)]"
                                                        role="status"
                                                    >
                                                        Для закрытия с долгом
                                                        нужно подтверждение
                                                        владельца или менеджера.
                                                    </p>
                                                )}
                                            <button
                                                className="h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-xs font-medium transition-colors hover:border-[var(--garage-accent)] hover:text-[var(--garage-accent)]"
                                                type="submit"
                                            >
                                                {statusActionLabel(
                                                    status.slug,
                                                    status.name,
                                                )}
                                            </button>
                                        </Form>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="mt-3 text-xs text-[var(--garage-text-muted)]">
                                Это конечный этап заказа.
                            </p>
                        )}
                    </section>

                    {canManageFinances && (
                        <>
                            <div className="scroll-mt-24" id="order-payment"><PaymentForm order={order} /></div>
                            <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                                <div className="flex items-center justify-between border-b border-[var(--garage-border-subtle)] px-4 py-3">
                                    <div>
                                        <h2 className="text-sm font-semibold">
                                            Платежи
                                        </h2>
                                        <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">
                                            История поступлений
                                        </p>
                                    </div>
                                    <CreditCard
                                        className="text-[var(--garage-text-muted)]"
                                        size={15}
                                    />
                                </div>
                                {order.payments.length ? (
                                    <div className="divide-y divide-[var(--garage-border-subtle)]">
                                        {order.payments.map((payment) => (
                                            <div
                                                className="flex items-center justify-between gap-3 px-4 py-3"
                                                key={payment.id}
                                            >
                                                <div>
                                                    <p className="text-xs font-medium">
                                                        {paymentMethods[
                                                            payment.method
                                                        ] || payment.method}
                                                    </p>
                                                    <p className="mt-1 text-[10px] text-[var(--garage-text-muted)]">
                                                        {formatDate(
                                                            payment.paid_at,
                                                        )}{' '}
                                                        ·{' '}
                                                        {payment.status ===
                                                        'successful'
                                                            ? 'Успешно'
                                                            : 'Отменён'}
                                                    </p>
                                                </div>
                                                <span className="shrink-0 text-xs font-semibold tabular-nums">
                                                    {money(payment.amount)}
                                                </span>
                                                {payment.status ===
                                                    'successful' && (
                                                    <Form
                                                        action={`/orders/${order.id}/payments/${payment.id}/cancel`}
                                                        className="shrink-0"
                                                        method="post"
                                                        onSubmit={(event) => {
                                                            if (
                                                                !window.confirm(
                                                                    `Отменить оплату ${money(payment.amount)}? Баланс заказа будет пересчитан.`,
                                                                )
                                                            ) {
                                                                event.preventDefault();
                                                            }
                                                        }}
                                                    >
                                                        <button
                                                            className="h-8 rounded border border-[var(--garage-border)] px-2 text-[10px] text-[var(--garage-error)] hover:border-[var(--garage-error)]"
                                                            type="submit"
                                                        >
                                                            Отменить
                                                        </button>
                                                    </Form>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="px-4 py-4 text-xs text-[var(--garage-text-muted)]">
                                        Платежей пока нет.
                                    </p>
                                )}
                            </section>
                            <section className="scroll-mt-24 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4" id="order-tracking">
                                <div className="flex items-center gap-2">
                                    <Link2
                                        className="text-[var(--garage-accent)]"
                                        size={16}
                                    />
                                    <h2 className="text-sm font-semibold">
                                        Ссылка для клиента
                                    </h2>
                                </div>
                                {publicOrderUrl ? (
                                    <div className="mt-3 space-y-2">
                                        <a
                                            className="block break-all text-xs text-[var(--garage-info)] underline underline-offset-2"
                                            href={publicOrderUrl}
                                            rel="noreferrer"
                                            target="_blank"
                                        >
                                            {publicOrderUrl}
                                        </a>
                                        <button
                                            className="inline-flex h-8 items-center gap-2 rounded-md border border-[var(--garage-border)] px-3 text-xs"
                                            onClick={copyPublicLink}
                                            type="button"
                                        >
                                            <Copy size={13} />
                                            {copied
                                                ? 'Скопировано'
                                                : 'Копировать'}
                                        </button>
                                    </div>
                                ) : (
                                    <p className="mt-2 text-xs text-[var(--garage-text-muted)]">
                                        {hasActivePublicLink
                                            ? 'Ссылка активна, но исходный URL больше не отображается.'
                                            : 'Покажите клиенту статус, работы и доступные фото.'}
                                    </p>
                                )}
                                <p className="mt-3 text-[10px] leading-4 text-[var(--garage-text-muted)]">Клиент увидит статус, работы, итог и только фотографии с отметкой «Показывать клиенту». Контакты и внутренние заметки остаются закрытыми.</p>
                                <div className="mt-3 flex gap-2">
                                    <Form
                                        action={`/orders/${order.id}/public-link`}
                                        method="post"
                                        className="flex-1"
                                    >
                                        <button
                                            className="h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] text-xs font-medium"
                                            type="submit"
                                        >
                                            {hasActivePublicLink
                                                ? 'Перевыпустить ссылку'
                                                : 'Создать ссылку'}
                                        </button>
                                    </Form>
                                    {hasActivePublicLink && (
                                        <Form
                                            action={`/orders/${order.id}/public-link/revoke`}
                                            method="post"
                                        >
                                            <button
                                                className="h-9 rounded-md border border-[var(--garage-border)] px-3 text-xs text-[var(--garage-error)]"
                                                type="submit"
                                            >
                                                Отозвать
                                            </button>
                                        </Form>
                                    )}
                                </div>
                            </section>
                        </>
                    )}
                </aside>
            </div>
            {lightboxIndex !== null && order.photos[lightboxIndex] && <div aria-label="Просмотр фотографии" aria-modal="true" className="fixed inset-0 z-50 grid place-items-center bg-black/90 p-4" onClick={() => setLightboxIndex(null)} role="dialog">
                <button aria-label="Закрыть просмотр фото" className="absolute right-4 top-4 grid size-10 place-items-center rounded-md border border-white/20 bg-black/40 text-white" onClick={() => setLightboxIndex(null)} type="button"><X size={18} /></button>
                <button aria-label="Предыдущее фото" className="absolute left-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-md border border-white/20 bg-black/40 text-white sm:left-6" onClick={(event) => { event.stopPropagation(); setLightboxIndex((lightboxIndex + order.photos.length - 1) % order.photos.length); }} type="button"><ChevronLeft size={20} /></button>
                <figure className="max-h-full max-w-6xl" onClick={(event) => event.stopPropagation()}>
                    <img alt={order.photos[lightboxIndex].caption || photoCategories[order.photos[lightboxIndex].category] || 'Фото автомобиля'} className="max-h-[82vh] max-w-full object-contain" src={order.photos[lightboxIndex].url} />
                    <figcaption className="mt-3 text-center text-xs text-white/80">{order.photos[lightboxIndex].caption || photoCategories[order.photos[lightboxIndex].category] || 'Фото'} · {lightboxIndex + 1} / {order.photos.length}</figcaption>
                </figure>
                <button aria-label="Следующее фото" className="absolute right-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-md border border-white/20 bg-black/40 text-white sm:right-6" onClick={(event) => { event.stopPropagation(); setLightboxIndex((lightboxIndex + 1) % order.photos.length); }} type="button"><ChevronRight size={20} /></button>
            </div>}
        </AppLayout>
    );
}

function InfoBlock({
    icon: Icon,
    label,
    title,
    detail,
}: {
    icon: typeof UserRound;
    label: string;
    title: string;
    detail: string;
}) {
    return (
        <section className="flex min-w-0 items-center gap-3 rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-3.5">
            <span className="grid size-9 shrink-0 place-items-center rounded-md bg-[var(--garage-surface-elevated)] text-[var(--garage-accent)]">
                <Icon size={16} />
            </span>
            <div className="min-w-0">
                <p className="text-[10px] uppercase tracking-[0.12em] text-[var(--garage-text-muted)]">
                    {label}
                </p>
                <p className="mt-1 truncate text-sm font-medium">{title}</p>
                <p className="mt-0.5 truncate text-[11px] text-[var(--garage-text-muted)]">
                    {detail}
                </p>
            </div>
        </section>
    );
}

function SummaryRow({
    label,
    value,
    strong,
    warning,
}: {
    label: string;
    value: string;
    strong?: boolean;
    warning?: boolean;
}) {
    return (
        <div
            className={`flex items-center justify-between gap-3 ${strong ? 'font-semibold' : ''}`}
        >
            <span className="text-[var(--garage-text-secondary)]">{label}</span>
            <span
                className={`tabular-nums ${warning ? 'text-[var(--garage-warning)]' : ''}`}
            >
                {value}
            </span>
        </div>
    );
}

function EmptyState({
    icon: Icon,
    text,
}: {
    icon: typeof Wrench;
    text: string;
}) {
    return (
        <div className="flex items-center gap-3 px-4 py-5 text-xs text-[var(--garage-text-muted)]">
            <Icon size={17} />
            {text}
        </div>
    );
}

function CommentBlock({ label, text }: { label: string; text: string }) {
    return (
        <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
            <div className="flex items-center gap-2">
                <FileText
                    className="text-[var(--garage-text-muted)]"
                    size={15}
                />
                <h2 className="text-xs font-semibold">{label}</h2>
            </div>
            <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-[var(--garage-text-secondary)]">
                {text}
            </p>
        </section>
    );
}

function statusActionLabel(slug: string, name: string) {
    return (
        (
            {
                accepted: 'Принять заказ',
                diagnostics: 'Начать диагностику',
                in_progress: 'Начать работу',
                waiting_parts: 'Ожидаем запчасти',
                ready: 'Отметить готовым',
                delivered: 'Выдать автомобиль',
                closed: 'Закрыть заказ',
                cancelled: 'Отменить заказ',
            } as Record<string, string>
        )[slug] ?? name
    );
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
