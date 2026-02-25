<template>
	<!-- Global dashboard shell with sidebar navigation, top navbar, and footer -->
	<UApp>
		<UDashboardGroup unit="rem" storage="local">
			<!-- Collapsible/resizable sidebar with main navigation -->
			<UDashboardSidebar
				v-model:open="open"
				id="default"
				:ui="{ footer: 'lg:border-t lg:border-default' }"
				class="bg-elevated/25"
				collapsible
				resizable
			>
				<!-- App name in the sidebar header -->
				<template #header>
					<div class="inline-flex w-full items-center justify-start gap-1.5 rounded-md px-2.5 font-medium">
						<span class="truncate text-xl font-bold text-primary" data-slot="label">
							{{ appname }}
						</span>
					</div>
				</template>

				<!-- Primary and secondary navigation stacks -->
				<template #default="{ collapsed }">
					<UDashboardSearchButton class="bg-transparent ring-default" :collapsed="collapsed" />

					<UNavigationMenu orientation="vertical" :items="links[0]" :collapsed="collapsed" tooltip popover />

					<UNavigationMenu orientation="vertical" class="mt-auto" :items="links[1]" :collapsed="collapsed" tooltip />
				</template>
			</UDashboardSidebar>

			<!-- Global search over all navigation links -->
			<UDashboardSearch :groups="groups" />

			<!-- Main panel with navbar, content, and footer slots -->
			<UDashboardPanel>
				<template #header>
					<UDashboardNavbar :ui="{ right: 'gap-3' }">
						<template #leading>
							<UDashboardSidebarCollapse />
						</template>

						<template #right>
							<NotificationMenu />
							<UserMenu />
						</template>
					</UDashboardNavbar>

					<!-- Toolbar content provided by page components -->
					<slot name="toolbar" />
				</template>

				<template #body>
					<!-- Main page body injected by child pages -->
					<slot name="body" />
				</template>

				<template #footer>
					<UFooter :ui="{ container: 'py-4 lg:py-2' }" class="border-t border-default bg-elevated/25">
						<template #left>
							<span class="text-sm text-muted">
								Copyright © {{ currentYear }}
								<ULink
									to="https://github.com/johanskizo"
									class="text-primary-500 hover:text-primary-600 hover:underline"
									target="_blank"
								>
									johanskizo
								</ULink>
								. All Rights Reserved.
							</span>
						</template>

						<template #right>
							<UButton
								to="https://github.com/johanskizo/Laravel-x-Nuxt-UI-Dashboard"
								icon="grommet-icons:github"
								color="neutral"
								variant="ghost"
								target="_blank"
								aria-label="GitHub"
							/>
						</template>
					</UFooter>
				</template>
			</UDashboardPanel>
		</UDashboardGroup>
	</UApp>
</template>

<script>
	/**
	 * Reusable dashboard layout wrapper for all module pages.
	 * Provides sidebar navigation, global search, top navbar with user
	 * and notification menus, and a footer with branding.
	 */

	// External dependencies
	import Cookies from 'js-cookie';
	import { useRoute } from 'vue-router';

	// Local components and utilities
	import NotificationMenu from '../components/NotificationMenu.vue';
	import UserMenu from '../components/UserMenu.vue';
	import { menus } from '../utils/menus';

	export default {
		// Child components used in the layout shell
		components: {
			NotificationMenu,
			UserMenu
		},

		// Composition API setup for reactive route and toast composable
		setup() {
			const route = useRoute();
			const toast = useToast();

			return {
				route,
				toast
			};
		},

		// Local reactive state
		data() {
			return {
				// Application name shown in the sidebar header
				appname: import.meta.env.VITE_APP_NAME || 'Nuxt UI Dashboard',
				// Controls whether the sidebar is expanded or collapsed
				open: false
			};
		},

		// Derived values used across the layout
		computed: {
			/**
			 * Extracts the current year for the footer copyright notice.
			 */
			currentYear() {
				return new Date().getFullYear();
			},

			/**
			 * Formats navigation links and determines active states
			 * based on the current route path, including nested children.
			 */
			links() {
				return menus.map((group) =>
					group.map((item) => {
						const isActive = (path) => (path ? this.route.path.startsWith(path) : false);

						// Handle nested menu items
						if (item.children?.length) {
							const isChildActive = item.children.some((child) => isActive(child.to));

							return {
								...item,
								active: isChildActive,
								defaultOpen: isChildActive || item.defaultOpen,
								children: item.children.map((child) => ({
									...child,
									active: isActive(child.to)
								}))
							};
						}

						return {
							...item,
							active: isActive(item.to)
						};
					})
				);
			},

			/**
			 * Flattens the menu structure into a list of links
			 * consumable by the `UDashboardSearch` component.
			 */
			groups() {
				const allLinks = this.links.flat().reduce((acc, item) => {
					if (item.to) acc.push(item);
					if (item.children) {
						acc.push(...item.children.filter((child) => child.to));
					}
					return acc;
				}, []);

				return [
					{
						id: 'links',
						label: 'Go to',
						items: allLinks
					}
				];
			}
		},

		// Lifecycle hooks
		mounted() {
			this.checkCookieConsent();
		},

		// Layout methods
		methods: {
			/**
			 * Checks for user cookie consent and triggers a persistent toast
			 * to request consent when none has been recorded yet.
			 */
			checkCookieConsent() {
				const consent = Cookies.get('cookie-consent');
				if (consent === 'accepted') return;

				this.toast.add({
					title: this.$t('We use first-party cookies to enhance your experience on our website.'),
					duration: 0,
					close: false,
					actions: [
						{
							label: this.$t('Accept'),
							color: 'neutral',
							variant: 'outline',
							onClick: () => {
								Cookies.set('cookie-consent', 'accepted', { expires: 365 });
							}
						},
						{
							label: this.$t('Opt out'),
							color: 'neutral',
							variant: 'ghost'
						}
					]
				});
			}
		}
	};
</script>
