export default (Privileges) => [
	{
		label: 'Privilege',
		icon: 'i-lucide-shield-check',
		defaultOpen: false,
		children: [
			{
				label: 'Role',
				icon: 'i-lucide-hash',
				to: '/module/privilege/role',
				permission: 'Privilege.role.view'
			},
			{
				label: 'Permission',
				icon: 'i-lucide-key-round',
				to: '/module/privilege/permission',
				permission: 'Privilege.permission.view'
			}
		]
	}
];
