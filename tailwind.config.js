const plugin = require('tailwindcss/plugin');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig',
    './src/**/*.php',
    './assets/js/public/**/*.ts',
    './assets/js/utils/**/*.ts',
  ],
  theme: {
    extend: {
      aria: {
        'current-page': 'current="page"',
      },

      backgroundImage: {
        'woo-hero': 'url(/public/img/hero/krimpenerwaard-tineke-dijkstra.jpg)',
      },

      colors: {
        'woo-anti-flash-white': '#f3f3f3',
        'woo-ateneo-blue': '#004161',
        'woo-chinese-silver': '#ccc',
        'woo-cornsilk': '#fff4dc',
        'woo-davys-grey': '#535353',
        'woo-dim-gray': '#696969',
        'woo-platinum': '#e6e6e6',
        'woo-plum': '#814081',
        'woo-rose-garnet': '#8d0041',
        'woo-maximum-red': '#d52a1e',
        'woo-royal-red': '#ca005d',
        'woo-sea-blue': '#01689b',
        'woo-spanish-gray': '#999999',
        'woo-japanese-laurel': '#358734',
        'woo-san-felix': '#1D611C',
      },

      fontFamily: {
        sans: ['RO Sans Web', 'system-ui', 'sans-serif'],
        serif: ['RO Serif Web', 'serif'],
      },

      listStyleType: {
        circle: 'circle',
      },

      screens: {
        xs: '480px',
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
      addVariant('not-last', '&:not(:last-child)');
    }),
  ],
};
