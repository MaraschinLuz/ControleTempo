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
            colors: {
                brand: {
                    50: '#F2F6F7',
                    100: '#DDE9EB',
                    200: '#BCD2D7',
                    300: '#8FB4BC',
                    400: '#5B8792',
                    500: '#0F4C5C',
                    600: '#0D4351',
                    700: '#0B3945',
                    800: '#092E38',
                    900: '#07242C',
                    950: '#04171C',
                },
                ink: '#121212',
                canvas: '#E8E8E8',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
