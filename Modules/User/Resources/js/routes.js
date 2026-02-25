export default (User) => [
	{
		path: '/module/user',
		component: () => import('./pages/index.vue'),
		meta: {
			permission: 'User.view'
		}
	},
	{
		path: '/module/user/edit/:id',
		component: () => import('./pages/form.vue'),
		meta: {
			permission: 'User.edit'
		}
	}
];
