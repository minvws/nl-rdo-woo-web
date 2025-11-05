import vuePlugin from '@vitejs/plugin-vue';
import path from 'path';
import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';
import { execSync } from 'child_process';

export default defineConfig({
  plugins: [
    vuePlugin(),
    symfonyPlugin({
      stimulus: './assets/js/shared/vue-controllers.json',
    }),
  ],

  define: {
    __GIT_HASH__: JSON.stringify(
      execSync('git rev-parse HEAD').toString().trim(),
    ),
  },

  build: {
    manifest: true,
    rollupOptions: {
      input: {
        admin: './assets/js/admin/index.ts',
        api: './assets/js/api/index.ts',
        public: './assets/js/public/index.ts',
        'worker-charts': './assets/js/misc/charts.js',
      },
    },
    assetsInlineLimit: 0,
  },

  html: {
    cspNonce: 'vite-nonce',
  },

  server: {
    host: 'localhost',
    port: 8001,
  },

  resolve: {
    alias: {
      /**
       * The import aliases below are defined in multiple files. So remember to update them all if you change one.
       * - tsconfig.json
       * - vitest.config.ts
       * - vite.config.js
       */

      '@js': path.resolve(__dirname, 'assets/js/'),
      '@fonts': path.resolve(__dirname, 'assets/fonts/'),
      '@img': path.resolve(__dirname, 'public/img/'),
      '@styles': path.resolve(__dirname, 'assets/styles/'),
      '@test': path.resolve(__dirname, 'assets/js/test/'),
      '@utils': path.resolve(__dirname, 'assets/js/utils/'),
      '@admin-fe': path.resolve(__dirname, 'assets/js/admin/vue/'),
    },
  },
});
