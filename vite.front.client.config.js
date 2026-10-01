import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import {viteStaticCopy} from 'vite-plugin-static-copy';

const THEME_NAME = 'demo'; // myclient

export default defineConfig({
	plugins: [
		viteStaticCopy({
			targets: [
				{
					src: 'laracms/themes/' + THEME_NAME + '/_assets/_public/*',
					dest: '',
				},
			],
		}),
		laravel({
			buildDirectory: 'assets/themes/' + THEME_NAME,
			input: [
				'laracms/themes/' + THEME_NAME + '/_assets/css/app.css',
				'laracms/themes/' + THEME_NAME + '/_assets/js/app.js'
			],
			refresh: true,
		}),
		tailwindcss(),
	],
	resolve: {
		// laracms/core and laracms/themes/* are symlinks to ../laracms10pack: resolve
		// imports from the symlink path, so vendor/ and node_modules/ of this project are found.
		preserveSymlinks: true,
	},
	server: {
		watch: {
			ignored: ['**/storage/framework/views/**'],
		},
	},
});
