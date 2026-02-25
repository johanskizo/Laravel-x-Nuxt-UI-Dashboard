<template>
	<div class="flex items-center gap-2">
		<UDropdownMenu :ui="{ content: 'w-80' }" :items="dropdownItems" :content="{ align: 'end', sideOffset: 8 }">
			<UChip
				color="error"
				size="3xl"
				:text="unreadCount > 0 ? unreadCount.toString() : undefined"
				:show="unreadCount > 0"
				inset
			>
				<UButton icon="i-lucide-bell" color="neutral" variant="ghost" class="rounded-full" />
			</UChip>

			<template #item-label="{ item }">
				<div class="relative flex w-full flex-col gap-0.5 pr-6">
					<div v-if="item.unread" class="absolute top-1 right-0 h-2 w-2 rounded-full bg-red-500" />

					<span class="truncate leading-tight font-medium transition-colors">
						{{ item.label }}
					</span>

					<span class="text-xs text-neutral-500">
						{{ item.time }}
					</span>
				</div>
			</template>

			<template #content-bottom>
				<div class="border-t border-neutral-200 p-1 dark:border-neutral-800">
					<UButton
						:label="$t('Mark all as read')"
						color="primary"
						variant="ghost"
						size="xs"
						@click="markAllAsRead"
						block
					/>
				</div>
			</template>
		</UDropdownMenu>
	</div>
</template>

<script>
	import { useAuthenticationStore } from '../../../../Modules/Authentication/Resources/js/store';

	export default {
		setup() {
			const authenticationStore = useAuthenticationStore();

			return {
				authenticationStore
			};
		},

		data() {
			return {
				notifications: [
					{
						id: 1,
						title: 'New Notification received',
						time: '00:00',
						unread: true
					},
					{
						id: 2,
						title: 'Old Notification received',
						time: '00:00',
						unread: false
					},
					{
						id: 3,
						title: 'New Notification received',
						time: '00:00',
						unread: true
					}
				]
			};
		},

		computed: {
			/**
			 * Returns the total number of unread notifications
			 */
			unreadCount() {
				return this.notifications.filter((n) => n.unread).length;
			},

			/**
			 * Maps the notifications data into a format compatible with UDropdownMenu
			 */
			dropdownItems() {
				return [
					this.notifications.map((n) => ({
						label: n.title,
						time: n.time,
						unread: n.unread,
						onSelect: (e) => {
							// Prevent dropdown from closing immediately on click
							e.preventDefault();
							this.markAsRead(n.id);
						}
					}))
				];
			}
		},

		methods: {
			/**
			 * Marks a specific notification as read by its ID
			 * @param {number|string} id
			 */
			markAsRead(id) {
				const notification = this.notifications.find((n) => n.id === id);
				if (notification) {
					notification.unread = false;
				}
			},

			/**
			 * Marks all notifications in the list as read
			 */
			markAllAsRead() {
				this.notifications.forEach((n) => {
					n.unread = false;
				});
			}
		}
	};
</script>
