import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'public/react-build',
    rollupOptions: {
      input: {
        'paramedis-dashboard': resolve(__dirname, 'resources/react/paramedis-dashboard/main.jsx'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]'
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/react'),
    },
  },
  server: {
    port: 5174,
    host: true
  }
});