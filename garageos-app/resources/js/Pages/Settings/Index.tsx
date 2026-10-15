import { Form } from '@inertiajs/react';
import { Building2, ListTree, Mail, Phone, Save, TimerReset } from 'lucide-react';
import { Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

type Organization = {
    id: number;
    name: string;
    phone?: string | null;
    email?: string | null;
    timezone?: string | null;
    currency?: string | null;
};

export default function Index({ organization }: { organization: Organization }) {
    return (
        <AppLayout>
            <div>
                <p className="text-xs font-medium uppercase tracking-[0.16em] text-[var(--garage-text-muted)]">Система</p>
                <h1 className="mt-2 text-2xl font-semibold">Настройки организации</h1>
                <p className="mt-1 text-sm text-[var(--garage-text-secondary)]">Основные параметры вашей площадки и рабочей зоны.</p>
            </div>

            <div className="mt-6 max-w-3xl">
                <Link className="mb-4 inline-flex items-center gap-2 rounded-md border border-[var(--garage-border)] bg-[var(--garage-surface)] px-3 py-2 text-xs text-[var(--garage-text-secondary)] hover:text-[var(--garage-text)]" href="/settings/catalog"><ListTree size={15} />Категории и статусы</Link>
                <section className="rounded-[10px] border border-[var(--garage-border)] bg-[var(--garage-surface)] p-5">
                    <Form action="/settings" method="put" className="space-y-5">
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <label className="block text-sm text-[var(--garage-text-secondary)]">
                                        <span className="mb-1.5 flex items-center gap-2 text-xs font-medium uppercase tracking-[0.14em] text-[var(--garage-text-muted)]"><Building2 size={14} />Название</span>
                                        <input defaultValue={organization.name} name="name" className="w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-background)] px-3 py-2 text-sm text-[var(--garage-text)] outline-none ring-0 focus:border-[var(--garage-accent)]" />
                                        {errors.name && <span className="mt-1 block text-xs text-[var(--garage-danger)]">{errors.name}</span>}
                                    </label>

                                    <label className="block text-sm text-[var(--garage-text-secondary)]">
                                        <span className="mb-1.5 flex items-center gap-2 text-xs font-medium uppercase tracking-[0.14em] text-[var(--garage-text-muted)]"><Phone size={14} />Телефон</span>
                                        <input defaultValue={organization.phone ?? ''} name="phone" className="w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-background)] px-3 py-2 text-sm text-[var(--garage-text)] outline-none ring-0 focus:border-[var(--garage-accent)]" />
                                        {errors.phone && <span className="mt-1 block text-xs text-[var(--garage-danger)]">{errors.phone}</span>}
                                    </label>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <label className="block text-sm text-[var(--garage-text-secondary)]">
                                        <span className="mb-1.5 flex items-center gap-2 text-xs font-medium uppercase tracking-[0.14em] text-[var(--garage-text-muted)]"><Mail size={14} />Email</span>
                                        <input defaultValue={organization.email ?? ''} name="email" className="w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-background)] px-3 py-2 text-sm text-[var(--garage-text)] outline-none ring-0 focus:border-[var(--garage-accent)]" />
                                        {errors.email && <span className="mt-1 block text-xs text-[var(--garage-danger)]">{errors.email}</span>}
                                    </label>

                                    <label className="block text-sm text-[var(--garage-text-secondary)]">
                                        <span className="mb-1.5 flex items-center gap-2 text-xs font-medium uppercase tracking-[0.14em] text-[var(--garage-text-muted)]"><TimerReset size={14} />Часовой пояс</span>
                                        <input defaultValue={organization.timezone ?? 'Asia/Almaty'} name="timezone" className="w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-background)] px-3 py-2 text-sm text-[var(--garage-text)] outline-none ring-0 focus:border-[var(--garage-accent)]" />
                                        {errors.timezone && <span className="mt-1 block text-xs text-[var(--garage-danger)]">{errors.timezone}</span>}
                                    </label>
                                </div>

                                <div className="max-w-xs">
                                    <label className="block text-sm text-[var(--garage-text-secondary)]">
                                        <span className="mb-1.5 text-xs font-medium uppercase tracking-[0.14em] text-[var(--garage-text-muted)]">Валюта</span>
                                        <input defaultValue={organization.currency ?? 'KZT'} name="currency" className="w-full rounded-md border border-[var(--garage-border)] bg-[var(--garage-background)] px-3 py-2 text-sm text-[var(--garage-text)] outline-none ring-0 focus:border-[var(--garage-accent)]" />
                                        {errors.currency && <span className="mt-1 block text-xs text-[var(--garage-danger)]">{errors.currency}</span>}
                                    </label>
                                </div>

                                <div className="flex justify-end">
                                    <button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-[var(--garage-accent)] px-4 py-2 text-sm font-medium text-[var(--garage-background)] disabled:opacity-60">
                                        <Save size={16} />
                                        {processing ? 'Сохранение...' : 'Сохранить'}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </AppLayout>
    );
}
