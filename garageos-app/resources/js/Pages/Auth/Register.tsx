import { Form } from '@inertiajs/react';
import BrandLogo from '../../components/BrandLogo';

export default function Register() {
    return (
        <main className="grid min-h-screen place-items-center bg-[var(--garage-background)] px-5 py-12 text-[var(--garage-text)]">
            <section className="w-full max-w-md rounded-xl border border-[var(--garage-border)] bg-[var(--garage-surface)] p-7 sm:p-9">
                <div className="mb-8">
                    <BrandLogo className="h-11 w-11" />
                    <h1 className="mt-6 text-3xl font-semibold tracking-tight">Начните работу сервиса</h1>
                    <p className="mt-3 text-sm leading-6 text-[var(--garage-text-secondary)]">14 дней полного доступа. Без банковской карты.</p>
                </div>
                <Form action="/register" method="post" className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <Field label="Название бизнеса" name="business_name" error={errors.business_name} />
                            <Field label="Ваше имя" name="name" error={errors.name} />
                            <Field label="Телефон" name="phone" type="tel" error={errors.phone} />
                            <Field label="Email" name="email" type="email" error={errors.email} />
                            <Field label="Пароль" name="password" type="password" error={errors.password} />
                            <Field label="Подтверждение пароля" name="password_confirmation" type="password" />
                            <button className="mt-3 w-full rounded-lg bg-[var(--garage-accent)] px-4 py-3 text-sm font-semibold text-[var(--garage-background)] disabled:opacity-50" disabled={processing} type="submit">
                                {processing ? 'Создаём пространство...' : 'Создать GarageOS'}
                            </button>
                        </>
                    )}
                </Form>
            </section>
        </main>
    );
}

function Field({ label, name, type = 'text', error }: { label: string; name: string; type?: string; error?: string }) {
    return (
        <label className="block text-sm font-medium">
            {label}
            <input className="mt-2 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none transition placeholder:text-[var(--garage-text-muted)] focus:border-[var(--garage-accent)] focus:ring-1 focus:ring-[var(--garage-accent)]/40" name={name} type={type} />
            {error && <span className="mt-1 block text-xs font-normal text-red-600">{error}</span>}
        </label>
    );
}