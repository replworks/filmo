import { createInertiaApp } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Filmo';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            default:
                return null;
        }
    },
    strictMode: true,
    progress: {
        color: '#4B5563',
    },
});
