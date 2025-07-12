import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/petugas-table-ux.css',
                'resources/css/filament/paramedis-mobile.css'
            ],
            refresh: true,
        }),
    ],
});
