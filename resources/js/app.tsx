import { createInertiaApp } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Filmo';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name === 'welcome':
            case name.startsWith('catalog/'):
            case name.startsWith('contributor/'):
                return PublicLayout;
            default:
                return null;
        }
    },
    strictMode: true,
    progress: {
        color: '#4B5563',
    },
});
