import { createRouter, createWebHistory } from 'vue-router';
import { routes } from './routes';
import { useAuthenticationStore } from '../../../../Modules/Authentication/Resources/js/store';

const baseUrl = import.meta.env.VITE_APP_URL ? import.meta.env.VITE_APP_URL : window.Laravel.baseUrl;

const basePath = new URL(baseUrl).pathname.replace(/\/$/, '');

export const router = createRouter({
	history: createWebHistory(basePath),
	routes
});

router.beforeEach(async (to, from, next) => {
	const authenticationStore = useAuthenticationStore();

	if (to.meta.role && !authenticationStore.hasRole(to.meta.role)) {
		return next('/403');
	}

	if (to.meta.permission && !authenticationStore.can(to.meta.permission)) {
		return next('/403');
	}

	next();
});
