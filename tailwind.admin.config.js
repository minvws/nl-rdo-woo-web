const plugin = require('tailwindcss/plugin');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./templates/**/*.twig",
        "./src/Twig/**/*.php",
        "./assets/js/admin/**/*.js",
    ],
    theme: {
        extend: {
            backgroundImage: {
                'to-top': "url('/public/img/admin/to-top.svg')",
            },
            colors: {
                // https://www.color-name.com/hex/f3f3f3

                // Gray colors
                'anti-flash-white': '#f3f3f3',
                'black-olive':      '#404040',
                'chinese-silver':   '#ccc',
                'dim-gray':         '#696969',
                'independence':     '#475467',
                'light-silver':     '#d0d5dd',
                'platinum':         '#e6e6e6',

                // Blue colors
                'sea-blue':         '#01689b', // link color
                'ateneo-blue':      '#004161', // link hover color
                'ocean-boat-blue':  '#007bc7', // primary button bg color
                'blue-sapphire':    '#085585', // primary button border color
                'lavender-web':     '#e5f0f9',

                'eerie-black':      '#1d1d1d', // top bar bg color
                'davys-grey':       '#535353', // top bar active link bg color

                'maximum-red':      '#d52a1e',
                'philippine-green': '#027a48',
                'japanese-laurel':  '#348834', // confirmation icon fill color

                'plum':             '#814081', // purple
                'cornsilk':         '#fff4dc',
            },

            fontFamily: {
				sans: ['"RO Sans Web"', 'system-ui', 'sans-serif']
			},

            listStyleType: {
				square: 'square'
			}
        },
    },
    plugins: [
        plugin(function({ addVariant }) {
            addVariant('hover-focus', ['&:hover', '&:focus'])
          })
    ],
}

