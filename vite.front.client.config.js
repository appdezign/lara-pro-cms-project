import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
	    viteStaticCopy({
		    targets: [
			    {
				    src: 'laracms/themes/client/_assets/_public/*',
				    dest: '',
			    },
		    ],
	    }),

        laravel({
	        // publicDirectory: "../httpdocs",
	        buildDirectory: 'assets/themes/client',
            input: [
				'laracms/themes/client/_assets/scss/app.scss',
				'laracms/themes/client/_assets/js/app.js',
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
	css: {
		preprocessorOptions: {
			sass: {
				silenceDeprecations: [
					'import',
					'slash-div',
					'global-builtin',
					'color-functions',
					'abs-percent',
					'if-function'
				],
				quietDeps: true,
			},
			scss: {
				silenceDeprecations: [
					'import',
					'slash-div',
					'global-builtin',
					'color-functions',
					'abs-percent',
					'if-function'
				],
				quietDeps: true,
			},
		},
	},
	build: {
		emptyOutDir: true,
	},
});
