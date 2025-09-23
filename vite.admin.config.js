import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
	        buildDirectory: 'assets/admin/build',
            input: [
	            'laracms/core/resources/css/app.css',
	            'laracms/core/resources/js/app.js',
	            'laracms/core/resources/css/theme.css',
	            'laracms/core/resources/css/lara.scss'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
