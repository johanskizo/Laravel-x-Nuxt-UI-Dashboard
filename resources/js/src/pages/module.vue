<template>
	<UApp>
		<UDashboardGroup unit="rem" storage="local">
			<UDashboardPanel>
				<template #header>
					<UDashboardNavbar :ui="{ right: 'gap-3' }">
						<template #leading>
							<div class="inline-flex w-full items-center justify-start gap-1.5 rounded-md px-2.5 font-medium">
								<span class="truncate text-xl font-bold text-primary" data-slot="label">
									{{ appname }}
								</span>
							</div>
						</template>

						<template #right>
							<NotificationMenu />
							<UserMenu />
						</template>
					</UDashboardNavbar>
				</template>

				<template #body>
					<div class="mb-10 text-center">
						<h1 class="mb-3 text-3xl font-extrabold tracking-tight md:text-4xl">
							{{ $t('Integrated Module System') }}
						</h1>
						<p class="text-muted-foreground mx-auto max-w-2xl text-base">
							{{
								$t(
									'A control center for all application features. Select a module below to start managing your operations.'
								)
							}}
						</p>
					</div>

					<div class="flex flex-wrap justify-center gap-4 md:justify-start">
						<UButton
							v-for="item in links"
							:to="item.to"
							class="group flex h-28 w-28 flex-col items-center justify-center rounded-xl border border-transparent transition-all duration-200 hover:border-default hover:bg-white hover:shadow-sm dark:hover:bg-gray-900"
							:key="item.label"
							:target="item.target"
							variant="ghost"
							color="neutral"
						>
							<div
								class="mb-2 flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 transition-transform duration-300 group-hover:scale-110 dark:bg-gray-800"
							>
								<UIcon :name="item.icon" class="size-6 transition-colors group-hover:text-primary" />
							</div>

							<span
								class="line-clamp-2 px-1 text-center text-[11px] leading-tight font-medium transition-colors group-hover:text-primary"
							>
								{{ item.label }}
							</span>
						</UButton>
					</div>
				</template>
			</UDashboardPanel>
		</UDashboardGroup>
	</UApp>
</template>

<script>
	import UserMenu from '../components/UserMenu.vue';
	import NotificationMenu from '../components/NotificationMenu.vue';
	import { menus } from '../utils/menus';

	export default {
		components: {
			UserMenu,
			NotificationMenu
		},

		data() {
			return {
				appname: import.meta.env.VITE_APP_NAME || 'Nuxt UI Dashboard'
			};
		},

		computed: {
			/**
			 * Flattens the nested menu structure into a single array of
			 * accessible links for the module selection grid.
			 */
			links() {
				return menus.flat().flatMap((item) => {
					const items = [];

					// Add parent item if it has a route
					if (item.to) items.push(item);

					// Add children if they have routes
					if (item.children?.length) {
						items.push(...item.children.filter((child) => child.to));
					}

					return items;
				});
			}
		}
	};
</script>
