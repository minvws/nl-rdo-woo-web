const plugin = require('tailwindcss/plugin');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig',
    './src/**/*.php',
    './assets/js/admin/**/*.{ts,vue}',
    './assets/js/utils/**/*.ts',
  ],
  theme: {
    extend: {
      backgroundImage: {
        check: 'url(/public/img/admin/check.svg)',
        cross: 'url(/public/img/admin/cross.svg)',
      },

      content: {
        empty: '""',
      },

      colors: {
        // Gray colors
        'bhr-anti-flash-white': '#f3f3f3',
        'bhr-black-olive': '#404040',
        'bhr-chinese-silver': '#ccc',
        'bhr-dim-gray': '#696969',
        'bhr-independence': '#475467',
        'bhr-light-silver': '#d0d5dd',
        'bhr-platinum': '#e6e6e6',
        'bhr-spanish-gray': '#949494',

        // Blue colors
        'bhr-sea-blue': '#01689b', // link color
        'bhr-ateneo-blue': '#004161', // link hover color
        'bhr-ocean-boat-blue': '#007bc7', // primary button bg color
        'bhr-blue-sapphire': '#085585', // primary button border color
        'bhr-lavender-web': '#e5f0f9',
        'bhr-azureish-white': '#d9ebf7',

        'bhr-eerie-black': '#1d1d1d', // top bar bg color
        'bhr-davys-grey': '#535353', // top bar active link bg color

        'bhr-maximum-red': '#d52a1e',
        'bhr-philippine-green': '#027a48',
        'bhr-japanese-laurel': '#348834', // confirmation icon fill color
        'bhr-chinese-white': '#e1eddb', // confirmation icon fill color

        'bhr-pale-pink': '#f9dfdd',

        'bhr-plum': '#814081', // purple
        'bhr-cornsilk': '#fff4dc',
      },

      fontFamily: {
        sans: ['RO Sans Web', 'system-ui', 'sans-serif'],
      },

      listStyleType: {
        square: 'square',
      },

      screens: {
        print: { raw: 'print' },
        screen: { raw: 'screen' },
      },

      zIndex: {
        1: 1,
        2: 2,
      },
    },
  },
  plugins: [
    plugin(({ addVariant }) => {
      addVariant('hover-focus', ['&:hover', '&:focus']);
      addVariant('js', '.js &');
      addVariant('no-js', '.no-js &');
      addVariant('not-first', '&:not(:first-child)');
    }),
  ],
};
