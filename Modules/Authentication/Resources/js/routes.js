export default (Authentication) => [
	{
		path: '/',
		component: () => import('../../../../Modules/Authentication/Resources/js/pages/login.vue')
	},
	{
		path: '/authentication/login',
		component: () => import('../../../../Modules/Authentication/Resources/js/pages/login.vue')
	},
	{
		path: '/authentication/signup',
		component: () => import('../../../../Modules/Authentication/Resources/js/pages/signup.vue')
	},
	{
		path: '/authentication/reset-password/:token',
		component: () => import('../../../../Modules/Authentication/Resources/js/pages/resetPassword.vue')
	},
	{
		path: '/authentication/forgot-password',
		component: () => import('../../../../Modules/Authentication/Resources/js/pages/forgotPassword.vue')
	}
];
