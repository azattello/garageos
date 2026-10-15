/// <reference types="vite/client" />

import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob<{ default: any }>('./Pages/**/*.tsx');
        const page = pages[`./Pages/${name}.tsx`];

        return page ? page().then((module) => module.default) : undefined;
    },
    setup({ el, App, props }) {
        if (!el) {
            return;
        }

        createRoot(el).render(<App {...props} />);
    },
});