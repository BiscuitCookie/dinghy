import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/ts/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                },
                surface: {
                    DEFAULT: '#ffffff',
                    muted: '#f4f5f7',
                },
                ink: {
                    DEFAULT: '#1f2430',
                    soft: '#6b7280',
                    faint: '#9ca3af',
                },
                phase: {
                    draft: '#9ca3af',
                    collect: '#10b981',
                    vote: '#f59e0b',
                    discuss: '#3b82f6',
                    done: '#6b7280',
                },
            },

            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            borderRadius: {
                card: '0.875rem',
            },

            boxShadow: {
                card: '0 1px 2px rgb(16 24 40 / 0.06), 0 1px 3px rgb(16 24 40 / 0.1)',
                'card-hover': '0 4px 12px rgb(16 24 40 / 0.12), 0 2px 4px rgb(16 24 40 / 0.08)',
            },

            transitionDuration: {
                DEFAULT: '150ms',
            },
        },
    },

    plugins: [forms],
};
