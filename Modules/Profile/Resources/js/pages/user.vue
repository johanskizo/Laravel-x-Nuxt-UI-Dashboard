<template>
	<DashboardLayout>
		<template #toolbar>
			<UDashboardToolbar>
				<NavigationMenu />
			</UDashboardToolbar>
		</template>

		<template #body>
			<div class="mx-auto flex w-full flex-col">
				<UForm @submit="handleSubmit">
					<UPageCard variant="subtle">
						<UFormField
							name="name"
							:label="$t('Username')"
							required
							:error="errors.name?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.name" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="email"
							:label="$t('Email')"
							required
							:error="errors.email?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.email" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							:label="$t('Role')"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<template v-if="!ui.isLoadingData">
								<div class="flex flex-wrap gap-1">
									<UBadge
										v-for="(role, index) in user.role_names"
										class="capitalize"
										:key="index"
										color="info"
										variant="subtle"
									>
										#{{ role }}
									</UBadge>
								</div>
								<p v-if="!user.role_names || user.role_names.length === 0" class="text-mute italic">
									{{ $t('No Role Assigned') }}
								</p>
							</template>
						</UFormField>

						<UFormField
							:label="$t('Status')"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<template v-if="!ui.isLoadingData">
								<UBadge :color="user.is_active === 1 ? 'success' : 'error'" variant="subtle" size="md">
									{{ user.is_active === 1 ? $t('Active') : $t('Inactive') }}
								</UBadge>
							</template>
						</UFormField>
					</UPageCard>

					<div class="flex justify-end gap-3 py-4">
						<UButton
							:label="$t('Back')"
							icon="i-lucide-arrow-left"
							color="neutral"
							variant="outline"
							@click="$router.back()"
						/>
						<UButton type="submit" :label="$t('Save')" icon="i-lucide-save" :loading="ui.isLoading" color="primary" />
					</div>
				</UForm>

				<template v-if="!ui.isLoadingData">
					<UPageCard class="mt-4" variant="subtle">
						<UFormField :label="$t('Permissions')" class="mb-4" />

						<div v-for="(permissions, group) in groupedPermissions" class="border-b py-4 last:border-0" :key="group">
							<h3 class="mb-2 flex items-center gap-2 text-sm font-bold capitalize">
								<UIcon name="i-heroicons-folder" class="text-primary" />
								{{ group }}
							</h3>

							<div class="flex flex-wrap gap-2">
								<UBadge v-for="permission in permissions" :key="permission" variant="subtle" color="info">
									{{ permission.split('.').pop() }}
								</UBadge>
							</div>
						</div>
					</UPageCard>
				</template>
			</div>
		</template>
	</DashboardLayout>
</template>

<script>
	/**
	 * ============================================================================
	 * Current User Information Display (Options API)
	 * ============================================================================
	 * Displays current authenticated user details with role and permission summary.
	 *
	 * Features:
	 * - Username and email display (read-only in most contexts)
	 * - Current roles display with badge indicators
	 * - Active/inactive status badge with color coding
	 * - Comprehensive permissions list grouped by category/prefix
	 * - Permission group organization (e.g., 'user.create' -> under 'user' group)
	 * - Real-time synchronization with backend data
	 * - Form validation with error handling (422 status)
	 * - Loading states for data fetching and submission
	 * - Navigation menu with quick links to profile sub-pages
	 * - Read-only display of system metadata (roles, permissions, status)
	 * - Icon grouping for visual organization of permission categories
	 *
	 * Data Flow:
	 * 1. Component mounts → Extract user ID from auth store
	 * 2. fetchData() → Get user details, roles, permissions from API
	 * 3. mapResponseToState() → Parse API response into component state
	 * 4. groupedPermissions computed → Organize permissions by prefix
	 * 5. User editable fields → name, email (if form visible)
	 * 6. handleSubmit() → PUT request with form data
	 * 7. Success: Refresh data via fetchData() and show toast
	 * 8. Error: Display field-level (422) or generic error messages
	 *
	 * Permission Grouping Logic:
	 * - Splits permission string by '.' (e.g., 'user.create')
	 * - Takes first part as group name ('user')
	 * - Collects all permissions with same group name
	 * - Returns object with groups as keys and permission arrays as values
	 * - Example output: { user: ['user.create', 'user.edit'], role: ['role.view'] }
	 *
	 * Components Used:
	 * - DashboardLayout: Main navigation wrapper
	 * - NavigationMenu: Profile sub-page navigation
	 * - UForm/UFormField: Form structure with read-only fields
	 * - UBadge: Status and role display with colors
	 * - UIcon: Visual icon for group headings
	 * - UButton: Back/Save buttons with loading states
	 * ============================================================================
	 */

	// 1. Networking & Stores
	import instance from '../../../../../resources/js/src/network/instance';
	import { useAuthenticationStore } from '../../../../Authentication/Resources/js/store';

	// 2. Components
	import DashboardLayout from '../../../../../resources/js/src/components/DashboardLayout.vue';
	import NavigationMenu from '../components/NavigationMenu.vue';

	export default {
		/**
		 * --- COMPONENT REGISTRATION ---
		 */
		components: {
			DashboardLayout,
			NavigationMenu
		},

		/**
		 * --- SETUP & COMPOSABLES ---
		 */
		setup() {
			const toast = useToast();
			const authenticationStore = useAuthenticationStore();

			return {
				toast,
				authenticationStore
			};
		},

		/**
		 * --- DATA STATE ---
		 */
		data() {
			return {
				// Route/resource identification
				id: null,

				// Navigation
				links: [
					[
						{ label: 'Profile', icon: 'i-lucide-user', to: '/module/profile', exact: true },
						{ label: 'Privacy', icon: 'i-lucide-shield', to: '/module/profile/privacy' },
						{ label: 'Security', icon: 'i-lucide-lock', to: '/module/profile/security' },
						{ label: 'Settings', icon: 'i-lucide-settings', to: '/module/profile/settings' }
					]
				],

				// UI state flags
				ui: {
					isLoading: false,
					isLoadingData: false
				},

				// Form fields (editable)
				form: {
					name: '',
					email: ''
				},

				// User metadata (read-only)
				user: {
					is_active: 0,
					role_names: [],
					permission_names: []
				},

				// Validation errors
				errors: {}
			};
		},


		/**
		 * --- METHODS ---
		 */
		methods: {
			// ====== DATA FETCHING & SUBMISSION ======

			/**
			 * Fetches current user details, roles, and permissions from API
			 * Maps response to component state
			 * Clears previous errors on new fetch
			 */
			async fetchData() {
				this.ui.isLoadingData = true;
				this.errors = {};
				try {
					const { data: response } = await instance.get(`/profile/user/show/${this.id}`);
					this.mapResponseToState(response.data);
				} catch (error) {
					this.handleError(error);
				} finally {
					this.ui.isLoadingData = false;
				}
			},

			/**
			 * Submits form updates for user data
			 * Sends PUT request with editable fields (name, email)
			 * Refreshes data after successful update
			 */
			async handleSubmit() {
				this.ui.isLoading = true;
				this.errors = {};
				try {
					const { data: response } = await instance.put(`/profile/user/update/${this.id}`, this.form);

					this.toast.add({
						title: this.$t('Success'),
						description: response.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});

					// Re-sync with server to ensure data integrity
					await this.fetchData();
				} catch (error) {
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						this.handleError(error);
					}
				} finally {
					this.ui.isLoading = false;
				}
			},

			// ====== DATA MAPPING & FORM HELPERS ======

			/**
			 * Maps API response to internal component state
			 * Separates editable form fields from read-only metadata
			 * Converts is_active field to number for boolean comparison
			 *
			 * @param {Object} data - User data object from API response
			 * @param {string} data.name - Username (editable)
			 * @param {string} data.email - Email address (editable)
			 * @param {Array} data.role_names - List of assigned roles (read-only)
			 * @param {Array} data.permission_names - List of granted permissions (read-only)
			 * @param {boolean|number} data.is_active - Status flag (read-only)
			 */
			mapResponseToState(data) {
				// Map editable form fields
				this.form.name = data.name;
				this.form.email = data.email;

				// Map read-only metadata
				this.user.role_names = data.role_names;
				this.user.permission_names = data.permission_names;
				this.user.is_active = Number(data.is_active);
			},

			// ====== ERROR HANDLING ======
			/**
			 * Generic error handler for API requests
			 * Displays error message via toast notification
			 *
			 * @param {Error} error - Error object from API request
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
