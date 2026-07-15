/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/views/**/*.blade.php",
        "./app/Livewire/**/*.php",
        "./app/**/*.php",
    ],
    safelist: [
        {pattern: /.*/}
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