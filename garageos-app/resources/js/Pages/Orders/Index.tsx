import { Form, Link } from '@inertiajs/react';
import { ClipboardList, Plus, Wrench } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import PaymentForm from '../../components/PaymentForm';
import PhotoUploadForm from '../../components/PhotoUploadForm';

type Car = { id: number; make: string; model: string; plate_number?: string };
type Client = { id: number; name: string; cars: Car[] };
type Service = { id: number; name: string; price: number };
type Order = { id: number; number: string; total: number; paid_total: number; balance: number; client: { name: string }; car: { make: string; model: string; plate_number?: string }; status: { name: string; slug: string }; items: { name_snapshot: string; total: number }[] };
type Props = { orders: { data: Order[]; total: number }; clients: Client[]; services: Service[] };

export default function Index({ orders, clients, services }: Props) {
    const firstOrder = orders.data[0];

    return (
        <AppLayout>
            <div className="flex items-end justify-between gap-4">
                <div><p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Рабочее пространство</p><h1 className="mt-2 text-2xl font-semibold">Заказ-наряды</h1><p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Центр работы с автомобилями, услугами и оплатами.</p></div>
                <div className="flex gap-2"><Link className="inline-flex h-9 items-center rounded-md border border-[var(--garage-border)] px-3 text-xs font-medium" href="/orders/kanban">Kanban</Link><a className="inline-flex h-9 items-center gap-2 rounded-md bg-[var(--garage-accent)] px-3 text-xs font-semibold text-[var(--garage-background)]" href="#new-order"><Plus size={15} />Новый заказ-наряд</a></div>
            </div>
            <div className="mt-6 grid gap-4 lg:grid-cols-[1fr_340px]">
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)]">
                    {orders.data.length ? <div className="divide-y divide-[var(--garage-border-subtle)]">{orders.data.map((order) => <div className="grid gap-3 px-4 py-4 sm:grid-cols-[100px_1fr_auto] sm:items-center" key={order.id}><div><p className="text-sm font-semibold">{order.number}</p><p className="mt-1 text-[11px] text-[var(--garage-text-muted)]">{order.items.length} работ</p></div><div><p className="text-sm font-medium">{order.client.name}</p><p className="mt-1 text-xs text-[var(--garage-text-muted)]">{order.car.make} {order.car.model} · {order.car.plate_number || 'номер не указан'}</p><div className="mt-2 flex flex-wrap gap-1.5">{order.items.map((item) => <span className="rounded-full border border-[var(--garage-border)] px-2 py-0.5 text-[10px] text-[var(--garage-text-secondary)]" key={item.name_snapshot}>{item.name_snapshot}</span>)}</div></div><div className="text-right"><span className="rounded-full border border-[rgba(184,255,61,0.25)] bg-[rgba(184,255,61,0.08)] px-2 py-1 text-[10px] text-[var(--garage-accent)]">{order.status.name}</span><p className="mt-2 text-sm font-semibold">₸{order.total.toLocaleString('ru-RU')}</p><p className="mt-1 text-[11px] text-[var(--garage-warning)]">Долг: ₸{order.balance.toLocaleString('ru-RU')}</p></div></div>)}</div> : <div className="grid min-h-56 place-items-center p-8 text-center"><div><ClipboardList className="mx-auto text-[var(--garage-text-muted)]" size={24} /><p className="mt-3 text-sm font-medium">Заказ-нарядов пока нет</p><p className="mt-1 text-xs text-[var(--garage-text-muted)]">Создайте первый заказ, чтобы начать работу.</p></div></div>}
                </section>
                <div className="space-y-4">
                    <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4" id="new-order"><div className="flex items-center gap-2"><Plus className="text-[var(--garage-accent)]" size={16} /><h2 className="text-sm font-semibold">Новый заказ</h2></div><Form action="/orders" method="post" className="mt-5 space-y-3">{({ processing, errors }) => <><SelectField label="Клиент" name="client_id" options={clients.map((client) => ({ value: client.id, label: client.name }))} error={errors.client_id} /><SelectField label="Автомобиль" name="car_id" options={clients.flatMap((client) => client.cars.map((car) => ({ value: car.id, label: `${client.name} · ${car.make} ${car.model}` })))} error={errors.car_id} /><button className="mt-2 h-9 w-full rounded-md bg-[var(--garage-accent)] text-xs font-semibold text-[var(--garage-background)]" disabled={processing} type="submit">{processing ? 'Создаём...' : 'Создать заказ'}</button></>}</Form></section>
                    {firstOrder && <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-4"><div className="flex items-center gap-2"><Wrench className="text-[var(--garage-accent)]" size={16} /><h2 className="text-sm font-semibold">Добавить работу</h2></div><Form action={`/orders/${firstOrder.id}/items`} method="post" className="mt-5 space-y-3">{({ processing, errors }) => <><SelectField label={`В заказ ${firstOrder.number}`} name="service_id" options={services.map((service) => ({ value: service.id, label: `${service.name} · ₸${service.price.toLocaleString('ru-RU')}` }))} error={errors.service_id} /><Field label="Количество" name="quantity" type="number" defaultValue="1" error={errors.quantity} /><button className="h-9 w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] text-xs font-semibold" disabled={processing} type="submit">Добавить работу</button></>}</Form></section>}
                    {firstOrder && <PaymentForm order={firstOrder} />}
                    {firstOrder && <PhotoUploadForm order={firstOrder} />}
                </div>
            </div>
        </AppLayout>
    );
}

function Field({ label, name, type = 'text', defaultValue, error }: { label: string; name: string; type?: string; defaultValue?: string; error?: string }) { return <label className="block text-xs font-medium">{label}<input className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm outline-none focus:border-[var(--garage-accent)]" defaultValue={defaultValue} name={name} type={type} />{error && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{error}</span>}</label>; }
function SelectField({ label, name, options, error }: { label: string; name: string; options: { value: number; label: string }[]; error?: string }) { return <label className="block text-xs font-medium">{label}<select className="mt-1.5 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none focus:border-[var(--garage-accent)]" name={name} defaultValue=""><option className="bg-[var(--garage-surface)]" disabled value="">Выберите вариант</option>{options.map((option) => <option className="bg-[var(--garage-surface)]" key={option.value} value={option.value}>{option.label}</option>)}</select>{error && <span className="mt-1 block text-[11px] text-[var(--garage-error)]">{error}</span>}</label>; }
