import path from 'path';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { execSync } from 'child_process';

export default defineConfig({
  define: {
    __GIT_HASH__: JSON.stringify(
      execSync('git rev-parse HEAD').toString().trim(),
    ),
  },
  test: {
    alias: {
      '@js': path.resolve(__dirname, 'assets/js/'),
      '@fonts': path.resolve(__dirname, 'assets/fonts/'),
      '@img': path.resolve(__dirname, '/img/'),
      '@styles': path.resolve(__dirname, 'assets/styles/'),
      '@test': path.resolve(__dirname, 'assets/js/test/'),
      '@utils': path.resolve(__dirname, 'assets/js/utils/'),
      '@admin-fe': path.resolve(__dirname, 'assets/js/admin/vue/'),
    },
    environment: 'jsdom',
    include: ['**/*.test.ts'],
    coverage: {
      provider: 'istanbul',
      reportsDirectory: './reports/vitest',
      reporter: ['text', 'lcov'],
      include: ['assets/js/**'],
    },
    setupFiles: ['./vitest.setup.ts'],
  },
  plugins: [vue()],
});
