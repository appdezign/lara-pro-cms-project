import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
	        buildDirectory: 'assets/themes/base',
            input: [
				'laracms/themes/base/_assets/scss/app.scss',
				'laracms/themes/base/_assets/js/app.js',
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
