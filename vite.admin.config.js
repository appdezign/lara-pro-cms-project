import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';


export default defineConfig({
    plugins: [
	    viteStaticCopy({
		    targets: [
			    {
				    src: 'laracms/core/resources/_filament/*',
				    dest: './../../filament/',
			    },
			    {
				    src: 'laracms/core/resources/js/google-maps.js',
				    dest: './../../filament/js/app',
			    },
		    ],
	    }),

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
