export default (User) => {
	return [
		{
			label: 'User',
			icon: 'i-lucide-users',
			to: '/module/user',
			permission: 'User.view'
		}
	];
};
