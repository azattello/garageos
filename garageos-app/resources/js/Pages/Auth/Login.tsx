import { Form, Link } from '@inertiajs/react';
import BrandLogo from '../../components/BrandLogo';

export default function Login() {
    return (
        <main className="grid min-h-screen place-items-center bg-[var(--garage-background)] px-5 py-12 text-[var(--garage-text)]">
            <section className="w-full max-w-md rounded-xl border border-[var(--garage-border)] bg-[var(--garage-surface)] p-7 sm:p-9">
                <div className="mb-8">
                    <BrandLogo className="h-11 w-11" />
                    <h1 className="mt-6 text-3xl font-semibold tracking-tight">С возвращением</h1>
                    <p className="mt-3 text-sm leading-6 text-[var(--garage-text-secondary)]">Войдите, чтобы продолжить работу с сервисом.</p>
                </div>
                <Form action="/login" method="post" className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <Field label="Email" name="email" type="email" error={errors.email} />
                            <Field label="Пароль" name="password" type="password" error={errors.password} />
                            <label className="flex items-center gap-2 text-xs text-[var(--garage-text-secondary)]">
                                <input className="size-4 rounded border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] accent-[var(--garage-accent)]" name="remember" type="checkbox" value="1" />
                                Запомнить меня
                            </label>
                            <button className="mt-3 w-full rounded-lg bg-[var(--garage-accent)] px-4 py-3 text-sm font-semibold text-[var(--garage-background)] disabled:opacity-50" disabled={processing} type="submit">
                                {processing ? 'Входим...' : 'Войти'}
                            </button>
                            <Link className="block text-center text-xs text-[var(--garage-accent)] hover:underline" href="/forgot-password">Забыли пароль?</Link>
                            <p className="pt-2 text-center text-xs text-[var(--garage-text-muted)]">Нет организации? <Link className="text-[var(--garage-accent)] hover:underline" href="/register">Создать аккаунт</Link></p>
                        </>
                    )}
                </Form>
            </section>
        </main>
    );
}

function Field({ label, name, type, error }: { label: string; name: string; type: string; error?: string }) {
    return (
        <label className="block text-sm font-medium">
            {label}
            <input className="mt-2 h-10 w-full rounded-lg border border-[var(--garage-border)] bg-[var(--garage-surface-secondary)] px-3 text-sm text-[var(--garage-text)] outline-none transition focus:border-[var(--garage-accent)] focus:ring-1 focus:ring-[var(--garage-accent)]/40" name={name} type={type} />
            {error && <span className="mt-1 block text-xs font-normal text-[var(--garage-error)]">{error}</span>}
        </label>
    );
}
