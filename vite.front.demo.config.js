import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import {viteStaticCopy} from 'vite-plugin-static-copy';

export default defineConfig({
	plugins: [
		viteStaticCopy({
			targets: [
				{
					src: 'laracms/themes/demo/_assets/_public/*',
					dest: '',
				},
			],
		}),
		laravel({
			// publicDirectory: "../httpdocs",
			buildDirectory: 'assets/themes/demo',
			input: [
				'laracms/themes/demo/_assets/css/app.css',
				'laracms/themes/demo/_assets/js/app.js'
			],
			refresh: true,
		}),
		tailwindcss(),
	],
	server: {
		watch: {
			ignored: ['**/storage/framework/views/**'],
		},
	},
});
