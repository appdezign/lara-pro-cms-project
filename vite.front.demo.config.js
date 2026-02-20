import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

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
	        buildDirectory: 'assets/themes/demo',
            input: [
				'laracms/themes/demo/_assets/scss/app.scss',
				'laracms/themes/demo/_assets/js/app.js',
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
					'if-function',
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
					'if-function',
				],
				quietDeps: true,
			},
		},
	},
});
