import { MENU_PRIORITY_ORDER } from './menuConfig';
import { useAuthenticationStore } from '../../../../Modules/Authentication/Resources/js/store';
const authenticationStore = useAuthenticationStore();
const { can } = authenticationStore;

const filterMenusByPermission = (menus, canFunc) => {
	return menus
		.map((menu) => ({ ...menu }))
		.filter((menu) => {
			if (menu.children && menu.children.length > 0) {
				const clonedChildren = JSON.parse(JSON.stringify(menu.children));
				menu.children = filterMenusByPermission(clonedChildren, canFunc);
				return menu.children.length > 0;
			}
			if (!menu.permission) return true;
			return canFunc(menu.permission);
		});
};

const footerMenus = [
	[
		{
			label: 'Laravel Docs',
			icon: 'i-lucide-book-text',
			to: 'https://laravel.com/docs/8.x',
			target: '_blank'
		},
		{
			label: 'Vue.js Docs',
			icon: 'i-lucide-book-text',
			to: 'https://vuejs.org/guide/introduction.html',
			target: '_blank'
		},
		{
			label: 'Nuxt UI Docs',
			icon: 'i-lucide-book-text',
			to: 'https://ui.nuxt.com/getting-started',
			target: '_blank'
		}
	]
];

const moduleFiles = import.meta.glob('/modules/*/Resources/js/menus.js', { eager: true });

const moduleMenus = Object.keys(moduleFiles).flatMap((path) => {
	const config = moduleFiles[path].default;
	const pathParts = path.split('/');
	const moduleName = pathParts[2];
	const rawMenus = typeof config === 'function' ? config(moduleName) : config || [];
	return filterMenusByPermission(rawMenus, can);
});

moduleMenus.sort((a, b) => {
	const indexA = MENU_PRIORITY_ORDER.indexOf(a.label);
	const indexB = MENU_PRIORITY_ORDER.indexOf(b.label);
	if (indexA !== -1 && indexB !== -1) {
		return indexA - indexB;
	}
	if (indexA !== -1) return -1;
	if (indexB !== -1) return 1;
	return a.label.localeCompare(b.label);
});

export const menus = [moduleMenus, ...footerMenus];
