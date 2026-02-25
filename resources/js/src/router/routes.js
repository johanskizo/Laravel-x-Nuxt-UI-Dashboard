const baseRoutes = [
	{
		path: '/module',
		component: () => import('../pages/module.vue')
	},
	{
		path: '/401',
		component: () => import('../pages/401.vue')
	},
	{
		path: '/403',
		component: () => import('../pages/403.vue')
	}
];

const moduleFiles = import.meta.glob('/Modules/*/Resources/js/routes.js', { eager: true });

const moduleRoutes = Object.keys(moduleFiles).flatMap((path) => {
	const config = moduleFiles[path].default;
	const pathParts = path.split('/');
	const moduleName = pathParts[2];
	return typeof config === 'function' ? config(moduleName) : config || [];
});

export const routes = [
	...baseRoutes,
	...moduleRoutes,
	{
		path: '/:pathMatch(.*)*',
		component: () => import('../pages/404.vue')
	}
];
