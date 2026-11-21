import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
                heading: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: 'rgb(var(--color-primary-rgb) / <alpha-value>)',
                    hover: 'rgb(var(--color-primary-hover-rgb) / <alpha-value>)',
                    dark: 'rgb(var(--color-primary-hover-rgb) / <alpha-value>)',
                    subtle: 'var(--color-primary-subtle)',
                },
                'on-primary': 'var(--color-on-primary)',
                secondary: {
                    DEFAULT: 'rgb(var(--color-secondary-rgb) / <alpha-value>)',
                },
                accent: {
                    DEFAULT: 'rgb(var(--color-accent-rgb) / <alpha-value>)',
                },
                background: 'rgb(var(--color-background-rgb) / <alpha-value>)',
                surface: 'rgb(var(--color-surface-rgb) / <alpha-value>)',
                foreground: 'rgb(var(--color-foreground-rgb) / <alpha-value>)',
                muted: {
                    DEFAULT: 'rgb(var(--color-muted-rgb) / <alpha-value>)',
                    foreground: 'rgb(var(--color-muted-foreground-rgb) / <alpha-value>)',
                },
                border: 'rgb(var(--color-border-rgb) / <alpha-value>)',
                destructive: 'rgb(var(--color-destructive-rgb) / <alpha-value>)',
                ring: 'rgb(var(--color-ring-rgb) / <alpha-value>)',
                seal: 'var(--color-seal-bg)',
            },
            spacing: {
                xs: 'var(--space-xs)',
                sm: 'var(--space-sm)',
                md: 'var(--space-md)',
                lg: 'var(--space-lg)',
                xl: 'var(--space-xl)',
                '2xl': 'var(--space-2xl)',
                '3xl': 'var(--space-3xl)',
            },
            borderRadius: {
                'token-sm': 'var(--radius-sm)',
                'token-md': 'var(--radius-md)',
                'token-lg': 'var(--radius-lg)',
            },
            boxShadow: {
                '2xs': 'var(--shadow-2xs)',
                xs: 'var(--shadow-xs)',
                sm: 'var(--shadow-sm)',
                md: 'var(--shadow-md)',
                lg: 'var(--shadow-lg)',
                xl: 'var(--shadow-xl)',
            },
        },
    },

    plugins: [forms],
};
