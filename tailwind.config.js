/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./app/Livewire/**/*.php",
        "./app/**/*.php",
    ],
    theme: {
        extend: {
            colors: {
                gray: {
                    950: '#030712',
                },
            },
        },
    },
    plugins: [],
};