import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/widget-animations.js',
                'resources/js/dokter-mobile-app.tsx',
                'resources/js/paramedis/modern-dashboard.tsx',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/bendahara/theme.css',
                'resources/css/filament/manajer/theme.css',
                'resources/css/filament/petugas/theme.css',
                'resources/css/filament/paramedis-mobile.css',
                'resources/react/paramedis-jaspel/main.jsx',
                'resources/react/paramedis-jaspel/styles/ParamedisJaspelDashboard.css',
                'resources/react/premium-paramedis-dashboard/main.jsx',
                'resources/react/premium-paramedis-dashboard/styles/PremiumParamedisDashboard.css',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name]-[hash][extname]`;
                },
            },
        },
    },
});
