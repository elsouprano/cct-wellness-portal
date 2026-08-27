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
                sans: ['"Nunito Sans"', ...defaultTheme.fontFamily.sans],
                heading: ['"Varela Round"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: '#8B1C1C',
                'on-primary': '#FFFFFF',
                secondary: '#2E7D32',
                accent: '#E5A91E',
                background: '#FDFBF7',
                foreground: '#1F1A1A',
                muted: '#F5F0E6',
                border: '#E8E1D5',
                destructive: '#DC2626',
                ring: '#8B1C1C',
            },
            spacing: {
                xs: '0.25rem',
                sm: '0.5rem',
                md: '1rem',
                lg: '1.5rem',
                xl: '2rem',
                '2xl': '3rem',
                '3xl': '4rem',
            },
            boxShadow: {
                sm: '0 1px 2px rgba(0,0,0,0.05)',
                md: '0 4px 6px rgba(0,0,0,0.1)',
                lg: '0 10px 15px rgba(0,0,0,0.1)',
                xl: '0 20px 25px rgba(0,0,0,0.15)',
            },
        },
    },

    plugins: [forms],
};
