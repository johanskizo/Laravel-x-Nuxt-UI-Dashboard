export default (Privilege) => [
	{
		path: '/module/privilege/role',
		component: () => import('./pages/role/index.vue'),
		meta: {
			permission: 'Privilege.role.view'
		}
	},
	{
		path: '/module/privilege/role/add',
		component: () => import('./pages/role/form.vue'),
		meta: {
			permission: 'Privilege.role.add'
		}
	},
	{
		path: '/module/privilege/role/edit/:id',
		component: () => import('./pages/role/form.vue'),
		meta: {
			permission: 'Privilege.role.edit'
		}
	},
	{
		path: '/module/privilege/role/user/:id',
		component: () => import('./pages/role/user.vue'),
		meta: {
			permission: 'Privilege.role.user.view'
		}
	},
	{
		path: '/module/privilege/role/permission/:id',
		component: () => import('./pages/role/permission.vue'),
		meta: {
			permission: 'Privilege.role.permission.view'
		}
	},
	{
		path: '/module/privilege/permission',
		component: () => import('./pages/permission/index.vue'),
		meta: {
			permission: 'Privilege.permission.view'
		}
	},
	{
		path: '/module/privilege/permission/add',
		component: () => import('./pages/permission/form.vue'),
		meta: {
			permission: 'Privilege.permission.add'
		}
	},
	{
		path: '/module/privilege/permission/edit/:id',
		component: () => import('./pages/permission/form.vue'),
		meta: {
			permission: 'Privilege.permission.edit'
		}
	}
];
