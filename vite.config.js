import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import ui from '@nuxt/ui/vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
	server: {
		host: true,
		cors: true,
		watch: {
			ignored: ['**/storage/framework/views/**']
		}
	},
	plugins: [
		laravel({
			input: ['resources/js/src/main.js'],
			refresh: true
		}),
		tailwindcss(),
		vue(),
		ui({
			ui: {
				colors: {
					primary: 'green',
					neutral: 'zinc'
				}
			}
		})
	]
});
