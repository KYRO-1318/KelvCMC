/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        // Client portal
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        // Modules & plugins
        './app/Modules/**/resources/views/**/*.blade.php',
        './plugins/**/resources/views/**/*.blade.php',
        // Admin (Filament) pages & resources
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#8b5cf6',
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                    950: '#2e1065',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
