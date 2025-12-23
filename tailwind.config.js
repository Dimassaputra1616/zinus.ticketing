import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'shadow-amber-200/30',
        'shadow-indigo-200/30',
        'shadow-emerald-200/30',
        'shadow-slate-200/30',
        'text-amber-600',
        'text-indigo-600',
        'text-emerald-600',
        'text-slate-600',
        'bg-amber-100',
        'text-amber-700',
        'bg-indigo-100',
        'text-indigo-700',
        'bg-emerald-100',
        'text-emerald-700',
        'bg-slate-200',
        'text-slate-700',
        'border-slate-200/60',
        'bg-white/80',
        'shadow-slate-100',
        'bg-blue-100',
        'text-blue-600',
        'ring-1',
        'ring-slate-200',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f2f8f6',
                    100: '#deefe7',
                    200: '#bde0d3',
                    300: '#92cbb5',
                    400: '#63b094',
                    500: '#3d987c',
                    600: '#2d7f67',
                    700: '#246753',
                    800: '#1f5345',
                    900: '#174236',
                },
                ink: {
                    50: '#f8fafc',
                    100: '#eef2f6',
                    200: '#e0e7ef',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#67748b',
                    600: '#475569',
                    700: '#344256',
                    800: '#1f2937',
                    900: '#0f172a',
                },
            },
            boxShadow: {
                card: '0 18px 45px -35px rgba(23, 66, 54, 0.35)',
                panel: '0 30px 120px -80px rgba(23, 66, 54, 0.45)',
                button: '0 16px 34px -24px rgba(61, 152, 124, 0.55)',
            },
            fontSize: {
                '2xs': ['0.72rem', { lineHeight: '1rem' }],
            },
            keyframes: {
                'fade-in': {
                    '0%': { opacity: 0 },
                    '100%': { opacity: 1 },
                },
                rise: {
                    '0%': { opacity: 0, transform: 'translateY(12px)' },
                    '100%': { opacity: 1, transform: 'translateY(0)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                'glow-pulse': {
                    '0%': { opacity: 0.4 },
                    '100%': { opacity: 0.85 },
                },
            },
            animation: {
                'fade-in': 'fade-in 0.8s ease-out both',
                rise: 'rise 0.9s ease-out both',
                'float-slow': 'float 12s ease-in-out infinite',
                'glow-soft': 'glow-pulse 6s ease-in-out infinite alternate',
            },
        },
    },

    plugins: [forms],
};
