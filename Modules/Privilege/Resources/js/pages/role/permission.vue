<template>
	<DashboardLayout>
		<!-- Toolbar section with breadcrumb navigation -->
		<template #toolbar>
			<UDashboardToolbar>
				<template #left>
					<UBreadcrumb :items="breadcrumbItems" />
				</template>
			</UDashboardToolbar>
		</template>

		<!-- Main content area -->
		<template #body>
			<div class="space-y-4">
				<!-- Role information header card -->
				<UCard :ui="{ body: 'p-2 sm:p-3' }" variant="subtle">
					<div class="flex items-center justify-between">
						<!-- Role name display -->
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

						<!-- Back button -->
						<UButton
							:label="$t('Back')"
							icon="i-lucide-arrow-left"
							color="neutral"
							variant="outline"
							@click="$router.back()"
						/>
					</div>
				</UCard>

				<!-- Search controls -->
				<div class="flex flex-col items-center justify-between gap-2 sm:flex-row sm:gap-4">
					<!-- Empty placeholder for left alignment -->
					<div class="flex flex-wrap justify-center gap-2 sm:justify-start"></div>

					<!-- Search input -->
					<div class="flex flex-wrap justify-center gap-2 sm:justify-start">
						<UInput
							v-model="searchQuery"
							icon="i-lucide-search"
							:placeholder="$t('Search...')"
						/>
					</div>
				</div>

				<!-- Permissions form -->
				<UForm @submit="handleSubmit">
					<UPageCard variant="subtle">
						<!-- Loading state -->
						<div
							v-if="ui.isPermissionLoading"
							class="flex flex-col items-center justify-center py-12 text-muted"
						>
							<UIcon
								name="i-lucide-loader-2"
								class="mb-2 h-8 w-8 animate-spin text-primary"
							/>
						</div>

						<!-- Permission groups grid layout -->
						<div
							v-else
							class="grid grid-cols-1 gap-6 p-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5"
						>
							<!-- Permission group card -->
							<UCard
								v-for="(permission, group) in groupedPermissions"
								:key="group"
								:ui="{ root: 'rounded-none', body: 'p-0 sm:p-0' }"
								class="flex flex-col"
							>
								<!-- Group header with select-all checkbox -->
								<template #header>
									<div class="flex items-center justify-between">
										<span class="flex items-center gap-2 text-sm font-bold tracking-wider text-primary uppercase">
											<UIcon name="i-lucide-folder" />
											{{ group }}
										</span>
										<UCheckbox
											:model-value="selectedValue(permission)"
											@update:model-value="(value) => toggleGroup(permission, value)"
										/>
									</div>
								</template>

								<!-- Individual permission checkboxes -->
								<div class="space-y-3 p-4">
									<div v-for="perm in permission" :key="perm">
										<UCheckboxGroup
											v-model="form.permission"
											size="sm"
											:items="[
												{
													label: formatPermissionLabel(perm),
													value: perm
												}
											]"
											color="primary"
										/>
									</div>
								</div>
							</UCard>
						</div>
					</UPageCard>

					<!-- Form action buttons -->
					<div class="flex items-start justify-end gap-3 py-4">
						<!-- Back button -->
						<UButton
							:label="$t('Back')"
							icon="i-lucide-arrow-left"
							color="neutral"
							variant="outline"
							@click="$router.back()"
						/>

						<!-- Submit button -->
						<UButton
							type="submit"
							:label="$t('Save')"
							icon="i-lucide-save"
							:loading="ui.isLoading"
							color="primary"
						/>
					</div>
				</UForm>
			</div>
		</template>
	</DashboardLayout>
</template>

<script>
/**
 * ============================================================================
 * Role Permission Assignment Component (Options API)
 * ============================================================================
 * Handles assigning and updating permissions for a specific role.
 * Users can select permissions individually or by group.
 * Features:
 * - Permission grouping by category (e.g., "dashboard", "user")
 * - Select/deselect permissions with group-level toggle support
 * - Search and filter permissions by keyword
 * - Form submission with validation
 * ============================================================================
 */

// External libraries & utilities
// (none)

// Internal resources (networking & layout)
import instance from '../../../../../../resources/js/src/network/instance';
import DashboardLayout from '../../../../../../resources/js/src/components/DashboardLayout.vue';

export default {
	// Components used on this page
	components: {
		DashboardLayout
	},

	// Composables and injections
	setup() {
		const toast = useToast();
		return { toast };
	},

	// Local reactive state
	data() {
		return {
			// Route/resource identification
			id: null,
			name: '',

			// Breadcrumb navigation
			breadcrumbItems: [
				{ label: 'Module', icon: 'i-lucide-box', to: '/module' },
				{ label: 'Privilege', icon: 'i-lucide-shield-check' },
				{ label: 'Role', icon: 'i-lucide-hash', to: '/module/privilege/role' }
			],

			// UI state flags
			ui: {
				isLoading: false,
				isLoadingData: false,
				isPermissionLoading: false
			},

			// Main form payload
			form: {
				permission: []
			},

			// Reference data & helper state
			searchQuery: '',
			permissionsOptions: []
		};
	},

	// Lifecycle hooks
	mounted() {
		// Load available permissions from backend
		this.fetchPermissionsOptions();

		// Load specific role's current permissions if in edit mode
		if (this.$route.params.id) {
			this.id = this.$route.params.id;
			this.fetchData();
		}
	},

	// Computed properties
	computed: {
		/**
		 * Group filtered permissions by their first segment (e.g., "dashboard.user.view" -> "dashboard")
		 * Filters permissions based on current search query
		 * Returns object with group names as keys and permission arrays as values
		 */
		groupedPermissions() {
			const groups = {};

			// 1. Filter permissions based on search query
			const filtered = this.permissionsOptions.filter((permission) => {
				const label = this.formatPermissionLabel(permission).toLowerCase();
				const raw = permission.toLowerCase();
				const query = this.searchQuery.toLowerCase();

				return raw.includes(query) || label.includes(query);
			});

			// 2. Group filtered permissions by category
			filtered.forEach((permission) => {
				const groupName = permission.split('.')[0] || this.$t('Other');
				if (!groups[groupName]) {
					groups[groupName] = [];
				}
				groups[groupName].push(permission);
			});

			return groups;
		}
	},

	// Methods grouped by responsibility
	methods: {
		// ====== FORMATTING & SELECTION HELPERS ======

		/**
		 * Convert permission key to human-readable label
		 * Example: "dashboard.user.view" -> "User View"
		 * @param {string} value - The permission key
		 * @returns {string} Formatted label
		 */
		formatPermissionLabel(value) {
			const parts = value.split('.');
			return parts.length > 1
				? parts
						.slice(1)
						.join(' ')
						.replace(/\b\w/g, (l) => l.toUpperCase())
				: value;
		},

		/**
		 * Determine the selection state of a permission group
		 * Returns: false (none), true (all), or 'indeterminate' (partial)
		 * @param {array} groupItems - Array of permissions in the group
		 * @returns {boolean|string} Selection state
		 */
		selectedValue(groupItems) {
			const selectedCount = groupItems.filter((item) => this.form.permission.includes(item)).length;

			if (selectedCount === 0) return false;
			if (selectedCount === groupItems.length) return true;
			if (selectedCount > 0 && selectedCount < groupItems.length) return 'indeterminate';
		},

		/**
		 * Toggle all permissions in a group
		 * @param {array} groupItems - Array of permissions in the group
		 * @param {boolean} value - True to select all, false to deselect all
		 */
		toggleGroup(groupItems, value) {
			if (value === true) {
				// Add all group items to selected permissions (prevent duplicates)
				const combined = [...this.form.permission, ...groupItems];
				this.form.permission = [...new Set(combined)];
			} else {
				// Remove all group items from selected permissions
				this.form.permission = this.form.permission.filter((item) => !groupItems.includes(item));
			}
		},

		// ====== DATA FETCHING ======

		/**
		 * Fetch current role's assigned permissions and details
		 * Updates form state and breadcrumb trail
		 */
		async fetchData() {
			this.ui.isLoadingData = true;
			try {
				const { data: response } = await instance.get(`/privilege/role/permission/show/${this.id}`);

				// Update form with current permissions
				this.form.permission = response.data;
				this.name = response.name;

				// Update breadcrumb to show current role
				this.breadcrumbItems = [
					{ label: 'Module', icon: 'i-lucide-box', to: '/module' },
					{ label: 'Privilege', icon: 'i-lucide-shield-check' },
					{ label: 'Role', icon: 'i-lucide-hash', to: '/module/privilege/role' },
					{ label: 'Permissions', icon: 'i-lucide-key', to: `/module/privilege/role/permission/${this.id}` },
					{ label: response.name, icon: 'i-lucide-pencil' }
				];
			} catch (error) {
				this.handleError(error);
			} finally {
				this.ui.isLoadingData = false;
			}
		},

		/**
		 * Fetch all available permissions from backend
		 * Populates permissionsOptions for rendering
		 */
		async fetchPermissionsOptions() {
			this.ui.isPermissionLoading = true;
			try {
				const { data: response } = await instance.get('/privilege/role/permission/options');
				this.permissionsOptions = response.data || [];
			} catch (error) {
				this.handleError(error);
			} finally {
				this.ui.isPermissionLoading = false;
			}
		},

		// ====== FORM SUBMISSION ======

		/**
		 * Submit form to save role's permissions
		 * Sends selected permissions to backend via PUT request
		 */
		async handleSubmit() {
			this.ui.isLoading = true;
			try {
				const { data: response } = await instance.put(`/privilege/role/permission/update/${this.id}`, this.form);

				// Show success notification
				this.toast.add({
					title: this.$t('Success'),
					description: response.message,
					icon: 'i-lucide-circle-check',
					color: 'success'
				});

				// Redirect back to role list
				this.$router.push('/module/privilege/role');
			} catch (error) {
				this.handleError(error);
			} finally {
				this.ui.isLoading = false;
			}
		},

		// ====== ERROR HANDLING ======

		/**
		 * Generic error notification helper
		 * Extracts and shows API error message or generic fallback
		 * @param {object} error - Error object from API response
		 */
		handleError(error) {
			this.toast.add({
				title: 'Error',
				description: error.response?.data?.message || this.$t('Something went wrong'),
				icon: 'i-lucide-ban',
				color: 'error'
			});
		}
	}
};
</script>
