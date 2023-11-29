/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./templates/**/*.html.twig",
    ],
    theme: {
        extend: {
            colors: {
                // https://www.color-name.com/hex/f3f3f3

                'anti-flash-white': '#f3f3f3',
                'black-olive': '#404040',
                'dim-gray': '#696969',
            },
        },
    },
    plugins: [],
}

