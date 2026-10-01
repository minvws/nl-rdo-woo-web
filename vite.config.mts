import vuePlugin from '@vitejs/plugin-vue';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';
import { execSync } from 'child_process';
import tenants from './tenants/tenants.json';

const tenantEntries = Object.fromEntries(
  tenants.map((tenantId) => [
    `tenant-${tenantId}-public`,
    `./tenants/${tenantId}/assets/styles/public.css`,
  ]),
);

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [
    vuePlugin(),
    symfonyPlugin({
      stimulus: './assets/js/shared/vue-controllers.json',
    }),
    moveApiDocsPlugin(),
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
        public: './assets/js/public/index.ts',
        'api-docs': './assets/api-docs/index.html',
        ...tenantEntries,
      },
      output: {
        assetFileNames: () => {
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
    assetsInlineLimit: 0,
  },

  html: {
    cspNonce: 'vite-nonce',
  },

  server: {
    cors: {
      origin: [
        'http://localhost:8000',
        'http://localhost:8001',
        'http://localhost:8100',
        'http://localhost:8101',
      ],
    },
    host: 'localhost',
    port: 8010,
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

function moveApiDocsPlugin() {
  return {
    name: 'move-api-docs',
    closeBundle() {
      const source = path.resolve(
        __dirname,
        'public/build/assets/api-docs/index.html',
      );
      const dest = path.resolve(__dirname, 'public/api/index.html');

      if (fs.existsSync(source)) {
        fs.copyFileSync(source, dest);
        fs.unlinkSync(source);

        const dir = path.dirname(source);
        if (fs.existsSync(dir) && fs.readdirSync(dir).length === 0) {
          fs.rmdirSync(dir);
        }
      }
    },
  };
}
