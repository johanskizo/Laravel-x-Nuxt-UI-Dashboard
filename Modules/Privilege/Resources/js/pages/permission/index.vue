<script setup lang="ts">
	/**
	 * ============================================================================
	 * Permission Management List Component (TypeScript Script Setup)
	 * ============================================================================
	 * Displays a paginated, filterable table of permissions.
	 *
	 * Features:
	 * - Pagination, sorting, search
	 * - Row/item selection with bulk action
	 * - Action buttons (edit, delete)
	 * - Status badges
	 *
	 * Data Flow:
	 * 1. On mount → fetchData() loads initial list
	 * 2. User interacts → watchers trigger fetchData()
	 * 3. User selects items → selectedItems reflects selection
	 * 4. Bulk action → execute() posts action
	 * 5. Success → table refreshed and selection reset
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
	const UBadge = resolveComponent('UBadge');

	/**
	 * --- CORE DATA STATE ---
	 */
	const tableData = ref<any[]>([]);
	const isFetching = ref(false);
	const breadcrumbItems = ref<BreadcrumbItem[]>([
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
			label: 'Permission',
			icon: 'i-lucide-key-round',
			to: '/module/privilege/permission'
		}
	]);

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
			const { data: res } = await instance.get('/privilege/permission/data', {
				params: {
					page: pagination.value.currentPage,
					per_page: pagination.value.perPage,
					search: searchQuery.value,
					sort_by: sorting.value[0]?.id || 'id',
					sort_order: sorting.value[0]?.desc ? 'desc' : 'asc'
				}
			});
			tableData.value = res.data;
			pagination.value.perPage = res.per_page;
			pagination.value.from = res.from;
			pagination.value.to = res.to;
			pagination.value.total = res.total;
			perPageOptions.value = perPageOptions.value.map((option) => {
				if (option.value === -1) {
					return { label: t('All'), value: res.total };
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
			await instance.post('/privilege/permission/bulk-delete', { ids });

			isDeleteModalOpen.value = false;
			rowSelection.value = {};
			fetchData();
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
		},
		{
			accessorKey: 'description',
			header: t('Description')
		},
		{
			accessorKey: 'guard_name',
			header: t('Guard'),
			cell: ({ row }) => {
				return h(UBadge, {
					label: row.original.guard_name,
					color: 'info',
					variant: 'soft'
				});
			}
		}
	];

	if (can('Privilege.permission.edit') || can('Privilege.permission.delete')) {
		columns.push({
			id: 'action',
			header: t('Action'),
			cell: ({ row }) => {
				return h(
					'div',
					{ class: 'flex items-center justify-center gap-2' },
					[
						can('Privilege.permission.edit')
							? h(UButton, {
									icon: 'i-lucide-pencil',
									size: 'xs',
									variant: 'ghost',
									color: 'primary',
									onClick: () => router.push(`/module/privilege/permission/edit/${row.original.id}`)
								})
							: null,

						can('Privilege.permission.delete')
							? h(UButton, {
									icon: 'i-lucide-trash-2',
									size: 'xs',
									variant: 'ghost',
									color: 'error',
									onClick: () => openDeleteModal(row.original)
								})
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
				<div class="flex flex-col items-center justify-between gap-2 sm:flex-row sm:gap-4">
					<div class="flex flex-wrap justify-center gap-2 sm:justify-start">
						<UButton
							v-if="can('Privilege.permission.add')"
							to="/module/privilege/permission/add"
							icon="i-lucide-plus"
							color="success"
						>
							{{ t('Add Data') }}
						</UButton>
						<UButton
							v-if="Object.keys(rowSelection).length > 0"
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
						th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
						td: 'border-b border-default',
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
