export default (Dashboard) => [
	{
		path: '/module/dashboard',
		component: () => import('./pages/index.vue'),
		meta: {
			permission: 'Dashboard.view'
		}
	}
];
