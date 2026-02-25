<script setup lang="ts">
	/**
	 * ============================================================================
	 * Role User Assignment Component (TypeScript Setup Script)
	 * ============================================================================
	 * Displays and manages user assignments for a role.
	 *
	 * Features:
	 * - Pagination, sorting, search
	 * - Row/item selection with bulk action
	 * - Add users to role via dropdown
	 * - Action buttons (delete)
	 * - User avatars and details
	 *
	 * Data Flow:
	 * 1. On mount → fetchData() & fetchUserOptions() load initial data
	 * 2. User interacts → watchers trigger fetchData()
	 * 3. User selects dropdown → handleAddUser() adds user to role
	 * 4. User selects items → selectedItems reflects selection
	 * 5. Bulk action → executeDelete() posts action
	 * 6. Success → table refreshed and selection reset
	 * ============================================================================
	 */

	// Vue core & reactivity utilities
	import { computed, h, ref, resolveComponent, watch } from 'vue';

	// Router & i18n
	import { useRouter } from 'vue-router';
	import { useI18n } from 'vue-i18n';

	// Utility composables
	import { watchDebounced } from '@vueuse/core';

	// Nuxt UI types
	import type { BreadcrumbItem, TableColumn } from '@nuxt/ui';

	// Network & layout
	import instance from '../../../../../../resources/js/src/network/instance';
	import DashboardLayout from '../../../../../../resources/js/src/components/DashboardLayout.vue';

	// Stores
	import { useAuthenticationStore } from '../../../../../Authentication/Resources/js/store';

	/**
	 * --- INITIALIZATION ---
	 */
	const router = useRouter();
	const { t } = useI18n();
	const table = ref('table');
	const authenticationStore = useAuthenticationStore();
	const { can } = authenticationStore;

	// Resolving Nuxt UI components
	const UButton = resolveComponent('UButton');
	const UCheckbox = resolveComponent('UCheckbox');
	const UAvatar = resolveComponent('UAvatar');
	const UTooltip = resolveComponent('UTooltip');

	/**
	 * --- CORE DATA STATE ---
	 */
	const id = router.currentRoute.value.params.id;
	const name = ref('');
	const tableData = ref<any[]>([]);
	const isFetching = ref(false);
	const isFetchingUserOptions = ref(false);
	const breadcrumbItems = ref([
		{
			label: 'Module',
			icon: 'i-lucide-box',
			to: '/module'
		},
		{
			label: 'Privilege',
			icon: 'i-lucide-shield-check'
		},
		{
			label: 'Role',
			icon: 'i-lucide-hash',
			to: '/module/privilege/role'
		}
	]);

	const userOptions = ref<any[]>([]);
	const searchUser = ref('');

	const form = ref({
		user: userOptions.value[0]
	});

	/**
	 * --- SELECTION & DELETION STATE ---
	 */
	const isDeleteModalOpen = ref(false);
	const activeRecord = ref<any>(null); // Stores record for single action (Edit/Delete)
	const rowSelection = ref<Record<string, boolean>>({}); // Tracks selected checkboxes

	/**
	 * --- TABLE CONFIGURATION ---
	 */
	const columnVisibility = ref({ id: false, select: false });
	const sorting = ref([{ id: 'id', desc: true }]);
	const pagination = ref({ currentPage: 1, perPage: 10, from: 0, to: 0, total: 0 });

	/**
	 * --- FILTERS ---
	 */
	const searchQuery = ref('');

	/**
	 * --- COMPUTED LOGIC ---
	 */
	/**
	 * selectedItems: Determines which records to delete (Single from button vs Bulk from checkboxes)
	 */
	const selectedItems = computed(() => {
		// If activeRecord is set, it means user clicked a specific row's delete button
		if (activeRecord.value) return [activeRecord.value];

		// Otherwise, get all items selected via checkboxes
		const selectedIndices = Object.keys(rowSelection.value)
			.filter((key) => rowSelection.value[key])
			.map(Number);

		return tableData.value.filter((_, index) => selectedIndices.includes(index));
	});

	const perPageOptions = ref([
		{ label: '10', value: 10 },
		{ label: '25', value: 25 },
		{ label: '50', value: 50 },
		{ label: '100', value: 100 },
		{ label: t('All'), value: -1 }
	]);

	/**
	 * --- API INTERACTION ---
	 */
	async function fetchData() {
		isFetching.value = true;
		try {
			const { data: res } = await instance.get(`/privilege/role/user/data/${id}`, {
				params: {
					page: pagination.value.currentPage,
					per_page: pagination.value.perPage,
					search: searchQuery.value,
					sort_by: sorting.value[0]?.id || 'id',
					sort_order: sorting.value[0]?.desc ? 'desc' : 'asc'
				}
			});

			name.value = res.name;

			breadcrumbItems.value = [
				{ label: 'Module', icon: 'i-lucide-box', to: '/module' },
				{ label: 'Privilege', icon: 'i-lucide-shield-check' },
				{ label: 'Role', icon: 'i-lucide-hash', to: '/module/privilege/role' }
			];

			breadcrumbItems.value.push(
				{
					label: 'Users',
					icon: 'i-lucide-users',
					to: `/module/privilege/role/user/${id}`
				},
				{
					label: res.name,
					icon: 'i-lucide-pencil'
				}
			);
			tableData.value = res.pagination.data;
			pagination.value.perPage = res.pagination.per_page;
			pagination.value.from = res.pagination.from;
			pagination.value.to = res.pagination.to;
			pagination.value.total = res.pagination.total;
			perPageOptions.value = perPageOptions.value.map((option) => {
				if (option.value === -1) {
					return { label: t('All'), value: res.pagination.total };
				}
				return option;
			});
			rowSelection.value = {}; // Reset selection after data refresh
		} catch (error) {
			useToast().add({
				title: 'Error',
				description: (error as any).response?.data?.message,
				icon: 'i-lucide-ban',
				color: 'error'
			});
		} finally {
			isFetching.value = false;
		}
	}

	async function fetchUserOptions(query = '') {
		isFetchingUserOptions.value = true;
		try {
			const { data: resp } = await instance.get(`/privilege/role/user/options/${id}`, {
				params: { q: query }
			});
			userOptions.value = resp.data;
		} catch (error) {
			useToast().add({
				title: 'Error',
				description: (error as any).response?.data?.message,
				icon: 'i-lucide-ban',
				color: 'error'
			});
		} finally {
			isFetchingUserOptions.value = false;
		}
	}

	async function handleAddUser() {
		if (!form.value.user) return;

		isFetching.value = true;
		try {
			await instance.post(`/privilege/role/user/store/${id}`, { user_id: form.value.user.id });
			form.value.user = null;
			fetchData();
			fetchUserOptions();
		} catch (error) {
			useToast().add({
				title: 'Error',
				description: (error as any).response?.data?.message,
				icon: 'i-lucide-ban',
				color: 'error'
			});
		} finally {
			isFetching.value = false;
		}
	}

	/**
	 * --- ACTION HANDLERS ---
	 */
	const openDeleteModal = (item: any = null) => {
		activeRecord.value = item; // null means Bulk Delete
		isDeleteModalOpen.value = true;
	};

	async function executeDelete() {
		if (selectedItems.value.length === 0) return;

		isFetching.value = true;
		try {
			const ids = selectedItems.value.map((item) => item.id);
			await instance.post(`/privilege/role/user/bulk-delete/${id}`, { user_ids: ids });

			isDeleteModalOpen.value = false;
			rowSelection.value = {};
			fetchData();
			fetchUserOptions();
		} catch (error) {
			useToast().add({
				title: 'Error',
				description: (error as any).response?.data?.message,
				icon: 'i-lucide-ban',
				color: 'error'
			});
		} finally {
			isFetching.value = false;
		}
	}

	// Reset activeRecord when modal closes to avoid state collision
	watch(isDeleteModalOpen, (isOpen) => {
		if (!isOpen) activeRecord.value = null;
	});

	/**
	 * --- AUTOMATIC RE-FETCHING (WATCHERS) ---
	 */
	// Refetch on sort, filter, or page size change (Reset to page 1)
	watch(
		[sorting, () => pagination.value.perPage],
		() => {
			pagination.value.currentPage = 1;
			fetchData();
		},
		{ deep: true }
	);

	// Refetch when page number changes
	watch(
		() => pagination.value.currentPage,
		() => fetchData()
	);

	// Refetch user options when searchUser changes
	watchDebounced(
		searchUser,
		(newValue) => {
			fetchUserOptions(newValue);
		},
		{ debounce: 500 }
	);

	// Debounced search to prevent API spamming
	watchDebounced(
		searchQuery,
		() => {
			pagination.value.currentPage = 1;
			fetchData();
		},
		{ debounce: 500 }
	);

	// Initial Data Load
	fetchData();
	fetchUserOptions();

	/**
	 * --- TABLE COLUMN DEFINITIONS ---
	 */
	const columns: TableColumn<any>[] = [
		{
			id: 'select',
			header: ({ table }) =>
				h(UCheckbox, {
					modelValue: table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected(),
					'onUpdate:modelValue': (value: any) => table.toggleAllPageRowsSelected(!!value)
				}),
			cell: ({ row }) =>
				h(UCheckbox, {
					modelValue: row.getIsSelected(),
					'onUpdate:modelValue': (value: any) => row.toggleSelected(!!value)
				})
		},
		{
			accessorKey: 'id',
			header: ({ column }) => {
				const isSorted = column.getIsSorted();
				return h(UButton, {
					color: 'neutral',
					variant: 'ghost',
					label: 'ID',
					icon: isSorted
						? isSorted === 'asc'
							? 'i-lucide-arrow-up'
							: 'i-lucide-arrow-down'
						: 'i-lucide-arrow-up-down',
					class: '-mx-2.5',
					onClick: () => column.toggleSorting(column.getIsSorted() === 'asc')
				});
			}
		},
		{
			accessorKey: 'name',
			header: ({ column }) => {
				const isSorted = column.getIsSorted();
				return h(UButton, {
					color: 'neutral',
					variant: 'ghost',
					label: t('Username'),
					icon: isSorted
						? isSorted === 'asc'
							? 'i-lucide-arrow-up-narrow-wide'
							: 'i-lucide-arrow-down-wide-narrow'
						: 'i-lucide-arrow-up-down',
					class: '-mx-2.5',
					onClick: () => column.toggleSorting(column.getIsSorted() === 'asc')
				});
			},
			cell: ({ row }) => {
				return h('div', { class: 'flex items-center gap-3' }, [
					h(UAvatar, {
						src: row.original.photo_url,
						alt: row.original.name,
						size: 'sm'
					}),
					h('p', undefined, row.original.name)
				]);
			}
		},
		{
			accessorKey: 'email',
			header: ({ column }) => {
				const isSorted = column.getIsSorted();
				return h(UButton, {
					color: 'neutral',
					variant: 'ghost',
					label: t('Email'),
					icon: isSorted
						? isSorted === 'asc'
							? 'i-lucide-arrow-up-narrow-wide'
							: 'i-lucide-arrow-down-wide-narrow'
						: 'i-lucide-arrow-up-down',
					class: '-mx-2.5',
					onClick: () => column.toggleSorting(column.getIsSorted() === 'asc')
				});
			}
		},
		{
			accessorKey: 'full_name',
			header: ({ column }) => {
				const isSorted = column.getIsSorted();
				return h(UButton, {
					color: 'neutral',
					variant: 'ghost',
					label: t('Name'),
					icon: isSorted
						? isSorted === 'asc'
							? 'i-lucide-arrow-up-narrow-wide'
							: 'i-lucide-arrow-down-wide-narrow'
						: 'i-lucide-arrow-up-down',
					class: '-mx-2.5',
					onClick: () => column.toggleSorting(column.getIsSorted() === 'asc')
				});
			}
		}
	];

	if (can('Privilege.role.user.delete')) {
		columns.push({
			id: 'action',
			header: t('Action'),
			cell: ({ row }) => {
				return h(
					'div',
					{ class: 'flex items-center justify-center gap-2' },
					[
						can('Privilege.role.user.delete')
							? h(
									UTooltip,
									{
										text: t('Delete') + ' ' + row.original.name,
										delayDuration: 0
									},
									() =>
										h(UButton, {
											icon: 'i-lucide-trash-2',
											size: 'xs',
											variant: 'ghost',
											color: 'error',
											onClick: () => openDeleteModal(row.original)
										})
								)
							: null
					].filter(Boolean)
				);
			}
		});
	}
</script>

<template>
	<DashboardLayout>
		<template #toolbar>
			<UDashboardToolbar>
				<template #left>
					<UBreadcrumb :items="breadcrumbItems" />
				</template>
			</UDashboardToolbar>
		</template>
		<template #body>
			<div class="space-y-4 p-4">
				<UCard :ui="{ body: 'p-2 sm:p-3' }" variant="subtle">
					<div class="flex items-center justify-between">
						<div class="mx-auto flex w-full flex-col">
							<UFormField
								name="name"
								:label="$t('Role')"
								:ui="{ labelWrapper: 'w-10 shrink-0' }"
								class="flex items-start gap-4 max-sm:flex-col max-sm:gap-0 sm:items-center"
							>
								<UBadge v-if="name" variant="subtle" color="primary">
									<UIcon name="i-lucide-hash" />
									{{ name }}
								</UBadge>
							</UFormField>
						</div>
						<UButton
							:label="$t('Back')"
							icon="i-lucide-arrow-left"
							color="neutral"
							variant="outline"
							@click="$router.back()"
						/>
					</div>
				</UCard>
				<div
					v-if="can('Privilege.role.user.add')"
					class="flex flex-col items-center justify-between gap-2 sm:flex-row sm:gap-4"
				>
					<div class="flex w-full flex-wrap justify-center gap-2 sm:justify-start">
						<USelectMenu
							v-model="form.user"
							v-model:search-term="searchUser"
							:loading="isFetchingUserOptions"
							class="w-[calc(100%-88.1px)]"
							:avatar="form.user?.photo_url"
							:items="userOptions"
							:search="fetchUserOptions"
						>
						</USelectMenu>
						<UButton
							icon="i-lucide-square-plus"
							:loading="isFetching || isFetchingUserOptions"
							color="primary"
							size="xs"
							:disabled="!form.user"
							@click="handleAddUser"
						>
							{{ t('Add') }}
						</UButton>
					</div>
					<div class="flex flex-wrap justify-center gap-2 sm:justify-start">
						<UButton
							v-if="can('User.delete') && Object.keys(rowSelection).length > 0"
							icon="i-lucide-trash-2"
							color="error"
							variant="soft"
							@click="openDeleteModal()"
						>
							{{ t('Delete Selected') }} ({{ Object.keys(rowSelection).length }})
						</UButton>
					</div>

					<div class="flex flex-wrap justify-center gap-2 sm:justify-end">
						<UInput v-model="searchQuery" icon="i-lucide-search" :placeholder="t('Search...')" />
					</div>
				</div>

				<UTable
					v-model:sorting="sorting"
					v-model:column-visibility="columnVisibility"
					v-model:row-selection="rowSelection"
					:loading="isFetching"
					:ui="{
						base: 'table-fixed border border-default border-spacing-0',
						thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
						tbody: '[&>tr]:last:[&>td]:border-b-0',
						th: 'py-2 px-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r text-xs',
						td: 'py-2 px-2 border-b border-default text-xs',
						separator: 'h-0'
					}"
					ref="table"
					:data="tableData"
					:columns="columns"
				/>

				<div class="flex flex-col items-center justify-between gap-2 sm:flex-row sm:gap-4">
					<div class="text-sm text-gray-500">
						{{ t('Showing') }}
						<span class="font-medium">{{ pagination.from }}</span>
						{{ t('to') }}
						<span class="font-medium">{{ pagination.to }}</span>
						{{ t('of') }}
						<span class="font-medium">{{ pagination.total }}</span> data
					</div>

					<div class="flex items-center gap-2">
						<span class="text-sm text-gray-500">{{ t('Showing') }}</span>
						<USelect v-model="pagination.perPage" :items="perPageOptions" />
						<span class="text-sm text-gray-500">{{ t('entries per page') }}</span>
					</div>
					<UPagination
						v-model:page="pagination.currentPage"
						:items-per-page="Number(pagination.perPage)"
						:total="pagination.total"
						:active-button="{ variant: 'solid' }"
						:inactive-button="{ variant: 'ghost' }"
					/>
				</div>

				<UModal v-model:open="isDeleteModalOpen">
					<template #content>
						<div class="space-y-4 p-4">
							<h3 class="text-lg font-bold">
								{{ t('Confirm Deletion') }}
							</h3>

							<p class="text-sm text-gray-600">
								{{ t('Are you sure you want to delete') }}
								<span class="font-bold text-black">{{ selectedItems.length }}</span>
								{{ t('data? This action cannot be undone.') }}
							</p>

							<div class="max-h-40 overflow-y-auto rounded border bg-gray-50 p-2 dark:bg-gray-900">
								<ul class="space-y-1 text-xs">
									<li
										v-for="item in selectedItems"
										class="flex justify-between border-b pb-1 last:border-0"
										:key="item.id"
									>
										<span class="font-medium text-gray-700 dark:text-gray-300">{{ item.name || item.id }}</span>
										<span class="text-gray-400">#{{ item.id }}</span>
									</li>
								</ul>
							</div>

							<div class="mt-4 flex justify-end gap-3">
								<UButton color="neutral" variant="ghost" @click="isDeleteModalOpen = false">{{ t('Cancel') }}</UButton>
								<UButton :loading="isFetching" color="error" @click="executeDelete">{{ t('Delete') }}</UButton>
							</div>
						</div>
					</template>
				</UModal>
			</div>
		</template>
	</DashboardLayout>
</template>
