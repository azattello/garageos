import { useEffect, useState } from 'react';
import { X } from 'lucide-react';
import BrandLogo from '../../components/BrandLogo';

type LineItem = {
    type: string;
    name: string;
    quantity: number;
    unit_price: number;
    total: number;
};
type Photo = { url: string; caption?: string; category: string };
export type ClientOrderProps = {
    order: {
        number: string;
        organization?: string;
        car: string;
        plate?: string;
        status: { slug?: string | null; name?: string | null };
        status_updated_at?: string | null;
        items: LineItem[];
        photos: Photo[];
        total: number;
        paid: number;
        balance: number;
    };
};

const statusLabels: Record<string, string> = {
    new: 'Новый',
    accepted: 'Принят',
    diagnostics: 'Диагностика',
    in_progress: 'В работе',
    waiting_parts: 'Ожидание запчастей',
    ready: 'Готов',
    delivered: 'Выдан',
    closed: 'Закрыт',
    cancelled: 'Отменён',
};
const statusStyles: Record<string, string> = {
    new: 'border-[var(--garage-info)]/30 bg-[var(--garage-info)]/10 text-[var(--garage-info)]',
    accepted:
        'border-[var(--garage-info)]/30 bg-[var(--garage-info)]/10 text-[var(--garage-info)]',
    diagnostics:
        'border-[var(--garage-info)]/30 bg-[var(--garage-info)]/10 text-[var(--garage-info)]',
    in_progress:
        'border-[var(--garage-info)]/30 bg-[var(--garage-info)]/10 text-[var(--garage-info)]',
    waiting_parts:
        'border-[var(--garage-warning)]/30 bg-[var(--garage-warning)]/10 text-[var(--garage-warning)]',
    ready: 'border-[var(--garage-success)]/30 bg-[var(--garage-success)]/10 text-[var(--garage-success)]',
    delivered:
        'border-[var(--garage-success)]/30 bg-[var(--garage-success)]/10 text-[var(--garage-success)]',
    closed: 'border-[var(--garage-border)] bg-[var(--garage-surface-elevated)] text-[var(--garage-text-secondary)]',
    cancelled:
        'border-[var(--garage-error)]/30 bg-[var(--garage-error)]/10 text-[var(--garage-error)]',
};
const photoLabels: Record<string, string> = {
    before: 'До ремонта',
    damage: 'Повреждение',
    process: 'В процессе',
    after: 'После ремонта',
    other: 'Фото ремонта',
};
export default function Show({ order }: ClientOrderProps) {
    return <ClientOrderDetails order={order} />;
}

export function ClientOrderDetails({
    order,
    showServiceBrand = true,
}: ClientOrderProps & { showServiceBrand?: boolean }) {
    const [activePhoto, setActivePhoto] = useState<number | null>(null);
    const works = order.items.filter((item) => item.type !== 'part');
    const parts = order.items.filter((item) => item.type === 'part');
    const statusSlug = order.status.slug ?? '';
    const statusName =
        statusLabels[statusSlug] ?? order.status.name ?? 'Статус заказа';

    useEffect(() => {
        if (activePhoto === null) return;
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setActivePhoto(null);
            if (event.key === 'ArrowRight')
                setActivePhoto((index) =>
                    index === null ? null : (index + 1) % order.photos.length,
                );
            if (event.key === 'ArrowLeft')
                setActivePhoto((index) =>
                    index === null
                        ? null
                        : (index - 1 + order.photos.length) %
                          order.photos.length,
                );
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [activePhoto, order.photos.length]);

    return (
        <div
            className={
                showServiceBrand
                    ? 'min-h-screen bg-[var(--garage-background)] px-4 py-6 text-[var(--garage-text)] sm:px-8 sm:py-10'
                    : 'text-[var(--garage-text)]'
            }
        >
            <div className="mx-auto max-w-3xl">
                {showServiceBrand && (
                    <header className="flex flex-col gap-4 border-b border-[var(--garage-border)] pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <BrandLogo compact />
                            <p className="mt-2 text-xs text-[var(--garage-text-muted)]">
                                {order.organization || 'Автосервис'}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span
                                className={`inline-flex min-h-10 items-center rounded-md border px-4 text-sm font-semibold ${statusStyles[statusSlug] ?? statusStyles.new}`}
                            >
                                {statusName}
                            </span>
                            {order.status_updated_at && (
                                <span className="text-xs text-[var(--garage-text-muted)]">
                                    Обновлён{' '}
                                    {formatDate(order.status_updated_at)}
                                </span>
                            )}
                        </div>
                    </header>
                )}

                <section className="mt-7">
                    <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">
                        Заказ-наряд
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold sm:text-3xl">
                        {order.number}
                    </h1>
                    <div className="mt-5 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4">
                        <p className="text-[10px] uppercase text-[var(--garage-text-muted)]">
                            Автомобиль
                        </p>
                        <p className="mt-2 text-base font-semibold">
                            {order.car}
                        </p>
                        <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">
                            {order.plate || 'Госномер не указан'}
                        </p>
                    </div>
                </section>

                <section className="mt-5 overflow-hidden rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3">
                        <h2 className="text-sm font-semibold">Работы</h2>
                    </div>
                    <LineItems items={works} />
                    {works.length === 0 && (
                        <EmptyRows text="Работы пока не добавлены." />
                    )}
                </section>

                {parts.length > 0 && (
                    <section className="mt-4 overflow-hidden rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                        <div className="border-b border-[var(--garage-border-subtle)] px-4 py-3">
                            <h2 className="text-sm font-semibold">Запчасти</h2>
                        </div>
                        <LineItems items={parts} />
                    </section>
                )}

                <section
                    aria-label="Итоги заказа"
                    className="mt-4 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"
                >
                    <div className="space-y-3 text-sm">
                        <MoneyRow label="Итого" value={order.total} strong />
                        <MoneyRow
                            label="Оплачено"
                            value={order.paid}
                            tone="success"
                        />
                        <div className="border-t border-[var(--garage-border-subtle)] pt-3">
                            <MoneyRow
                                label="К оплате"
                                value={order.balance}
                                tone={order.balance > 0 ? 'warning' : undefined}
                                strong
                            />
                        </div>
                    </div>
                </section>

                {order.photos.length > 0 && (
                    <section className="mt-7">
                        <div className="flex items-end justify-between gap-3">
                            <div>
                                <h2 className="text-sm font-semibold">
                                    Фото ремонта
                                </h2>
                                <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                                    Фотографии, которыми сервис поделился с вами
                                </p>
                            </div>
                            <span className="text-xs text-[var(--garage-text-muted)]">
                                {order.photos.length}
                            </span>
                        </div>
                        <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            {order.photos.map((photo, index) => (
                                <button
                                    aria-label={`Открыть фото: ${photo.caption || photoLabels[photo.category] || 'Фото ремонта'}`}
                                    className="group overflow-hidden rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] text-left"
                                    key={photo.url}
                                    onClick={() => setActivePhoto(index)}
                                    type="button"
                                >
                                    <img
                                        alt={
                                            photo.caption ||
                                            photoLabels[photo.category] ||
                                            'Фото ремонта'
                                        }
                                        className="aspect-[4/3] w-full object-cover transition-transform group-hover:scale-[1.02]"
                                        loading="lazy"
                                        src={photo.url}
                                    />
                                    <span className="block px-2.5 py-2 text-[10px] text-[var(--garage-text-secondary)]">
                                        {photo.caption ||
                                            photoLabels[photo.category] ||
                                            'Фото ремонта'}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </section>
                )}

                <p className="mt-8 border-t border-[var(--garage-border)] pt-4 text-xs text-[var(--garage-text-muted)]">
                    Данные заказа обновляются сервисом.
                </p>
            </div>

            {activePhoto !== null && order.photos[activePhoto] && (
                <div
                    aria-label="Просмотр фотографии"
                    aria-modal="true"
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                    role="dialog"
                >
                    <button
                        aria-label="Закрыть просмотр"
                        className="absolute right-4 top-4 grid size-11 place-items-center rounded-md border border-white/20 text-white hover:bg-white/10"
                        onClick={() => setActivePhoto(null)}
                        type="button"
                    >
                        <X size={20} />
                    </button>
                    <figure className="max-h-full max-w-full">
                        <img
                            alt={
                                order.photos[activePhoto].caption ||
                                photoLabels[
                                    order.photos[activePhoto].category
                                ] ||
                                'Фото ремонта'
                            }
                            className="max-h-[78vh] max-w-full rounded-md object-contain"
                            src={order.photos[activePhoto].url}
                        />
                        <figcaption className="mt-3 text-center text-sm text-white">
                            {order.photos[activePhoto].caption ||
                                photoLabels[
                                    order.photos[activePhoto].category
                                ] ||
                                'Фото ремонта'}{' '}
                            · {activePhoto + 1} / {order.photos.length}
                        </figcaption>
                        {order.photos.length > 1 && (
                            <div className="mt-3 flex justify-center gap-3">
                                <button
                                    className="h-10 rounded-md border border-white/20 px-4 text-xs text-white"
                                    onClick={() =>
                                        setActivePhoto(
                                            (activePhoto -
                                                1 +
                                                order.photos.length) %
                                                order.photos.length,
                                        )
                                    }
                                    type="button"
                                >
                                    Назад
                                </button>
                                <button
                                    className="h-10 rounded-md border border-white/20 px-4 text-xs text-white"
                                    onClick={() =>
                                        setActivePhoto(
                                            (activePhoto + 1) %
                                                order.photos.length,
                                        )
                                    }
                                    type="button"
                                >
                                    Далее
                                </button>
                            </div>
                        )}
                    </figure>
                </div>
            )}
        </div>
    );
}

function LineItems({ items }: { items: LineItem[] }) {
    return (
        <div className="divide-y divide-[var(--garage-border-subtle)]">
            {items.map((item) => (
                <div
                    className="grid grid-cols-[minmax(0,1fr)_auto] gap-3 px-4 py-3 text-sm"
                    key={`${item.type}-${item.name}`}
                >
                    <div className="min-w-0">
                        <p className="break-words font-medium">{item.name}</p>
                        <p className="mt-1 text-xs text-[var(--garage-text-muted)]">
                            {item.quantity} × {money(item.unit_price)}
                        </p>
                    </div>
                    <span className="self-center text-right tabular-nums">
                        {money(item.total)}
                    </span>
                </div>
            ))}
        </div>
    );
}

function MoneyRow({
    label,
    value,
    strong = false,
    tone,
}: {
    label: string;
    value: number;
    strong?: boolean;
    tone?: 'success' | 'warning';
}) {
    const color =
        tone === 'success'
            ? 'text-[var(--garage-success)]'
            : tone === 'warning'
              ? 'text-[var(--garage-warning)]'
              : 'text-[var(--garage-text)]';
    return (
        <div
            className={`flex items-center justify-between gap-3 ${strong ? 'font-semibold' : ''}`}
        >
            <span className="text-[var(--garage-text-secondary)]">{label}</span>
            <span className={`tabular-nums ${color}`}>{money(value)}</span>
        </div>
    );
}

function EmptyRows({ text }: { text: string }) {
    return (
        <p className="px-4 py-6 text-center text-xs text-[var(--garage-text-muted)]">
            {text}
        </p>
    );
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'short',
        timeStyle: 'short',
        timeZone: 'Asia/Almaty',
    }).format(new Date(value));
}

function money(value: number) {
    return `${value.toLocaleString('ru-RU')} ₸`;
}
