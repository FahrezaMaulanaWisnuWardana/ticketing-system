import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    // Driven by <html data-theme="..."> so the choice can be persisted server-side
    // and matched by resources/css/gantt.css (which uses the same attribute).
    darkMode: ['selector', 'html[data-theme="dark"]'],
    content: [
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
        './vendor/livewire/livewire/src/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                    400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                    800: '#3730a3', 900: '#312e81',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [typography],
};
