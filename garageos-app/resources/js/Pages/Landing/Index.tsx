import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import { ArrowDown, ArrowRight, ArrowUpRight, Check, ChevronDown, Menu, X } from 'lucide-react';
import { useState } from 'react';
import '../../../css/landing.css';

type Plan = {
    key: string;
    name: string;
    price: number;
    currency: string;
    max_users: number;
    features: string[];
};

type Props = { plans: Plan[]; canonical: string };

const featureNames: Record<string, string> = {
    clients: 'Клиентская база',
    cars: 'Карточки автомобилей',
    appointments: 'Записи и расписание',
    orders: 'Заказ-наряды и статусы',
    services: 'Каталог услуг',
    employees: 'Команда до {count} сотрудников',
    vehicle_history: 'История обслуживания',
    photos: 'Фотографии работ',
    payments: 'Учёт оплат и задолженности',
    basic_analytics: 'Базовая аналитика',
    inventory: 'Склад и запчасти',
    expenses: 'Учёт расходов',
    advanced_analytics: 'Расширенная аналитика',
    financial_reports: 'Финансовые отчёты',
    employee_workload: 'Загрузка сотрудников',
};

const benefits = [
    { number: '01', title: 'Клиенты и автомобили', text: 'Связывайте карточку клиента с его автомобилями и сохраняйте историю обслуживания в одном месте.' },
    { number: '02', title: 'Записи и заказ-наряды', text: 'Ведите расписание, фиксируйте состав работ и отслеживайте статус каждого заказа.' },
    { number: '03', title: 'Показатели сервиса', text: 'Следите за выручкой, заказами и загрузкой команды в аналитике GarageOS.' },
];

const questions = [
    { question: 'Что такое GarageOS?', answer: 'GarageOS объединяет клиентскую базу, автомобили, записи, заказ-наряды, услуги и аналитику автосервиса в одном рабочем пространстве.' },
    { question: 'Для какого автосервиса подходит система?', answer: 'Для автосервисов, которым важно вести клиентов и автомобили, планировать записи и контролировать выполнение заказов.' },
    { question: 'Какие тарифы доступны?', answer: 'Доступны два тарифа: Start и Business. Состав возможностей и стоимость указаны ниже и берутся из действующего каталога GarageOS.' },
    { question: 'Можно ли посмотреть систему до регистрации?', answer: 'Да. Откройте демонстрацию: это отдельная организация только для просмотра, она не изменяет рабочие данные.' },
];

const formatPrice = (price: number) => new Intl.NumberFormat('ru-RU').format(price);

export default function Landing({ plans, canonical }: Props) {
    const [menuOpen, setMenuOpen] = useState(false);
    const reduceMotion = useReducedMotion();
    const appear = reduceMotion ? {} : { initial: { opacity: 1, y: 12 }, animate: { opacity: 1, y: 0 }, transition: { duration: 0.45, ease: 'easeOut' as const } };

    return (
        <div className="landing-shell">
            <Head title="GarageOS — управление автосервисом">
                <meta name="description" content="Клиенты, автомобили, записи, заказ-наряды и аналитика автосервиса в одной системе GarageOS. Посмотрите рабочую демонстрацию и выберите тариф." />
                <meta property="og:title" content="GarageOS — операционная система автосервиса" />
                <meta property="og:description" content="Управляйте клиентами, автомобилями, записями и заказ-нарядами в GarageOS." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={canonical} />
                <link rel="canonical" href={canonical} />
            </Head>

            <header className="landing-header">
                <nav className="landing-nav" aria-label="Основная навигация">
                    <a className="landing-brand" href="#top" aria-label="GarageOS — на главную"><img src="/logo.jpg" alt="" /><span>GarageOS</span></a>
                    <div className={`landing-nav-links${menuOpen ? ' is-open' : ''}`}>
                        <a href="#features" onClick={() => setMenuOpen(false)}>Возможности</a>
                        <a href="#pricing" onClick={() => setMenuOpen(false)}>Тарифы</a>
                        <a href="#faq" onClick={() => setMenuOpen(false)}>FAQ</a>
                        <Link href="/login">Войти</Link>
                        <Link className="landing-nav-cta" href="/register">Начать работу <ArrowUpRight size={15} /></Link>
                    </div>
                    <button className="landing-menu-toggle" type="button" aria-label={menuOpen ? 'Закрыть меню' : 'Открыть меню'} aria-expanded={menuOpen} onClick={() => setMenuOpen(!menuOpen)}>{menuOpen ? <X size={20} /> : <Menu size={20} />}</button>
                </nav>
            </header>

            <main id="top">
                <section className="landing-hero">
                    <div className="landing-hero-inner">
                        <motion.div {...appear}>
                            <p className="landing-eyebrow"><span /> Рабочая система для автосервиса</p>
                            <h1>Операционная система <span>для современного автосервиса</span></h1>
                            <div className="landing-hero-bottom">
                                <p>Клиенты, автомобили, записи, заказ-наряды и аналитика — в одной системе.</p>
                                <div className="landing-actions">
                                    <Link className="landing-button landing-button-primary" href="/register">Начать работу <ArrowRight size={17} /></Link>
                                    <a className="landing-button landing-button-secondary" href="#showcase">Посмотреть систему <ArrowDown size={16} /></a>
                                </div>
                            </div>
                        </motion.div>

                        <motion.div className="landing-browser" id="showcase" {...(reduceMotion ? {} : { initial: { opacity: 1, y: 18, scale: 0.99 }, animate: { opacity: 1, y: 0, scale: 1 }, transition: { delay: 0.18, duration: 0.55, ease: 'easeOut' as const } })}>
                            <div className="landing-browser-bar">
                                <div className="landing-window-controls" aria-hidden="true"><i /><i /><i /></div>
                                <div className="landing-address"><span />GarageOS.local/demo</div>
                                <span className="landing-live-label"><i /> Демо</span>
                            </div>
                            <iframe src="/demo" title="Рабочая демонстрация GarageOS" loading="lazy" />
                        </motion.div>
                        <div className="landing-hero-caption"><span>Реальный интерфейс GarageOS</span><span>Демо-организация · только просмотр</span></div>
                    </div>
                </section>

                <section className="landing-intro" aria-label="GarageOS в работе">
                    <div className="landing-content landing-intro-grid">
                        <p className="landing-section-index">01 / РАБОЧИЙ ПРОЦЕСС</p>
                        <div><h2>Сервис под контролем.<br /><span>Без таблиц вразнобой.</span></h2><p className="landing-lead">От первого звонка до закрытого заказ-наряда — вся история работы остаётся связанной.</p></div>
                        <div className="landing-flow" aria-label="Процесс работы в GarageOS"><span>Клиент</span><b>→</b><span>Автомобиль</span><b>→</b><span>Запись</span><b>→</b><span>Заказ-наряд</span></div>
                    </div>
                </section>

                <section className="landing-features" id="features">
                    <div className="landing-content">
                        <div className="landing-section-heading"><p className="landing-section-index">02 / ВОЗМОЖНОСТИ</p><h2>Всё связано.<br /><span>Ничего не теряется.</span></h2><p>Основные процессы сервиса — в одном понятном рабочем пространстве.</p></div>
                        <div className="landing-feature-list">
                            {benefits.map((item) => <article className="landing-feature-row" key={item.number}><span className="landing-feature-number">{item.number}</span><h3>{item.title}</h3><p>{item.text}</p><ArrowUpRight size={19} aria-hidden="true" /></article>)}
                        </div>
                    </div>
                </section>

                <section className="landing-steps">
                    <div className="landing-content landing-steps-grid">
                        <div><p className="landing-section-index">03 / БЫСТРЫЙ СТАРТ</p><h2>Начните с простого.</h2></div>
                        <ol>{['Создайте организацию', 'Добавьте клиентов и автомобили', 'Управляйте сервисом в GarageOS'].map((step, index) => <li key={step}><span>0{index + 1}</span><p>{step}</p><Check size={17} /></li>)}</ol>
                    </div>
                </section>

                <section className="landing-pricing" id="pricing">
                    <div className="landing-content">
                        <div className="landing-section-heading landing-pricing-heading"><p className="landing-section-index">04 / ТАРИФЫ</p><h2>Выберите масштаб.</h2><p>Два действующих тарифа GarageOS. Без скрытых пакетов.</p></div>
                        <div className="landing-plans">
                            {plans.map((plan, index) => <article className={`landing-plan${index === 1 ? ' landing-plan-featured' : ''}`} key={plan.key}>
                                <div className="landing-plan-top"><h3>{plan.name}</h3>{index === 1 && <span>Для растущей команды</span>}</div>
                                <p className="landing-plan-price">{formatPrice(plan.price)} <span>₸ <small>/ месяц</small></span></p>
                                <p className="landing-plan-limit">До {plan.max_users} сотрудников</p>
                                <ul>{plan.features.filter((feature) => featureNames[feature]).map((feature) => <li key={feature}><Check size={15} /><span>{featureNames[feature].replace('{count}', String(plan.max_users))}</span></li>)}</ul>
                                <Link className="landing-button landing-button-plan" href="/register">Выбрать {plan.name} <ArrowRight size={16} /></Link>
                            </article>)}
                        </div>
                    </div>
                </section>

                <section className="landing-faq" id="faq">
                    <div className="landing-content landing-faq-grid">
                        <div><p className="landing-section-index">05 / ВОПРОСЫ</p><h2>Коротко<br />о важном.</h2><Link href="/demo" className="landing-text-link">Открыть демо <ArrowUpRight size={15} /></Link></div>
                        <div className="landing-questions">{questions.map(({ question, answer }) => <details key={question}><summary>{question}<ChevronDown size={18} /></summary><p>{answer}</p></details>)}</div>
                    </div>
                </section>

                <section className="landing-final-cta"><div className="landing-content"><p className="landing-section-index">GarageOS / WORKSPACE</p><h2>Пора навести порядок<br />в работе сервиса.</h2><Link className="landing-button landing-button-primary" href="/register">Создать рабочее пространство <ArrowRight size={17} /></Link></div></section>
            </main>

            <footer className="landing-footer"><div className="landing-content"><a className="landing-brand" href="#top"><img src="/logo.jpg" alt="" /><span>GarageOS</span></a><p>Операционная система современного автосервиса</p><div><a href="#features">Возможности</a><a href="#pricing">Тарифы</a><a href="#faq">FAQ</a><Link href="/demo">Демо</Link><Link href="/login">Войти</Link></div><small>© {new Date().getFullYear()} GarageOS</small></div></footer>
        </div>
    );
}