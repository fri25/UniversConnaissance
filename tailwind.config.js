import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Couleur de marque : #10BAF1 (brand-500).
 * Contrastes (WCAG) :
 *  - texte blanc sur brand-700 (#0A779C) ≈ 5,1:1  → OK pour boutons « solides »
 *  - texte ink (#0B1F2A) sur brand-500 ≈ 7,5:1    → OK pour boutons « clairs »
 *  - texte blanc sur brand-500/600 < 4,5:1        → à éviter
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#EBFAFE',
                    100: '#D3F3FD',
                    200: '#ACE7FB',
                    300: '#72D7F8',
                    400: '#38C7F4',
                    500: '#10BAF1',
                    600: '#0896C5',
                    700: '#0A779C',
                    800: '#0F617F',
                    900: '#12516A',
                    950: '#093447',
                    DEFAULT: '#10BAF1',
                },
                ink: {
                    DEFAULT: '#0B1F2A',
                    900: '#0B1F2A',
                    800: '#10293A',
                    700: '#173749',
                    600: '#21465C',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
            },
            aspectRatio: {
                cover: '2 / 3',
            },
            boxShadow: {
                cover: '0 10px 30px -12px rgba(11, 31, 42, 0.45)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up .6s ease-out both',
            },
        },
    },

    plugins: [forms],
};
