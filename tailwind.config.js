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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, require('daisyui')],
    
    daisyui: {
        themes: [
            {
                dokterku: {
                    "primary": "#667eea",
                    "secondary": "#764ba2", 
                    "accent": "#10b981",
                    "neutral": "#3d4451",
                    "base-100": "#ffffff",
                    "info": "#3abff8",
                    "success": "#10b981",
                    "warning": "#fbbd23",
                    "error": "#ef4444",
                },
            },
            "light",
            "dark",
        ],
        base: true,
        styled: true,
        utils: true,
    },
};
