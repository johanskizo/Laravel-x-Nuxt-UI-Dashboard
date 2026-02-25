export default (Profile) => [
	{
		path: '/module/profile',
		component: () => import('./pages/index.vue')
	},
	{
		path: '/module/profile/user',
		component: () => import('./pages/user.vue')
	},
	{
		path: '/module/profile/security',
		component: () => import('./pages/security.vue')
	},
	{
		path: '/module/profile/settings',
		component: () => import('./pages/settings.vue')
	}
];
