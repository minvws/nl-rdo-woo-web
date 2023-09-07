/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./templates/**/*.html.twig",
    ],
    theme: {
        extend: {
            // https://www.color-name.com/hex/f3f3f3
            colors: {
                'anti-flash-white': '#f3f3f3',
                'dim-gray': '#696969',
                independence: '#475467'
            },
        },
    },
    plugins: [],
}

