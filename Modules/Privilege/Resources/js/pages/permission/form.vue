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
			<div class="mx-auto flex w-full flex-col">
				<UForm @submit="handleSubmit">
					<UPageCard variant="subtle">
						<UFormField
							name="module"
							:label="$t('Module')"
							required
							:error="errors.module?.[0]"
							:ui="{ labelWrapper: 'w-24 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<USelectMenu
								v-model="form.module"
								:loading="ui.isLoadingData || ui.isLoadingModuleItems"
								class="w-100"
								:items="moduleItems"
							/>
						</UFormField>

						<UFormField
							name="name"
							:label="$t('Name')"
							required
							:error="errors.name?.[0]"
							:ui="{ labelWrapper: 'w-24 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.name" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="description"
							:label="$t('Description')"
							:error="errors.description?.[0]"
							:ui="{ labelWrapper: 'w-24 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UTextarea v-model="form.description" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="guard"
							:label="$t('Guard')"
							required
							:error="errors.guard_name?.[0]"
							:ui="{ labelWrapper: 'w-24 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<USelect
								v-model="form.guard_name"
								:loading="ui.isLoadingData"
								class="w-100"
								:items="[
									{ label: 'Web', value: 'web' },
									{ label: 'API', value: 'api' }
								]"
							/>
						</UFormField>
					</UPageCard>

					<div class="flex items-start justify-end gap-3 py-4">
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
			</div>
		</template>
	</DashboardLayout>
</template>

<script>
	/**
	 * ============================================================================
	 * Permission Create/Edit Form Component (Options API)
	 * ============================================================================
	 * Handles creating new permissions and editing existing ones.
	 * Features:
	 * - Module selection from modules_statuses.json (active modules only)
	 * - Permission name, description, and guard type configuration
	 * - Support for both 'web' and 'api' guard types
	 * - Form validation with error message display
	 * - Breadcrumb navigation with context-aware titles
	 * ============================================================================
	 */

	// External libraries & utilities
	// (none)

	// Internal resources (networking & layout)
	import instance from '../../../../../../resources/js/src/network/instance';
	import DashboardLayout from '../../../../../../resources/js/src/components/DashboardLayout.vue';
	import rawModules from '../../../../../../modules_statuses.json';

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

				// Breadcrumb navigation
				breadcrumbItems: [
					{ label: 'Module', icon: 'i-lucide-box', to: '/module' },
					{ label: 'Privilege', icon: 'i-lucide-shield-check' },
					{ label: 'Permission', icon: 'i-lucide-key-round', to: '/module/privilege/permission' }
				],

				// UI state flags
				ui: {
					isLoading: false,
					isLoadingData: false,
					isLoadingModuleItems: false
				},

				// Main form payload
				form: {
					module: '',
					name: '',
					description: '',
					guard_name: 'api'
				},

				// Reference data & helper state
				moduleItems: [],

				// Validation errors from backend
				errors: {}
			};
		},

		// Lifecycle hooks
		mounted() {
			// Load available modules from configuration
			this.fetchModuleItems();

			// Check if editing an existing permission or creating new one
			if (this.$route.params.id) {
				// Edit mode: load existing permission data
				this.id = this.$route.params.id;
				this.fetchData();
			} else {
				// Create mode: add 'Add Data' breadcrumb
				this.breadcrumbItems.push({
					label: this.$t('Add Data'),
					icon: 'i-lucide-plus'
				});
			}
		},

		// Methods grouped by responsibility
		methods: {
			// ====== DATA FETCHING ======

			/**
			 * Load available modules from modules_statuses.json configuration
			 * Filters to show only active modules (value === true)
			 */
			async fetchModuleItems() {
				this.ui.isLoadingModuleItems = true;
				try {
					// Filter modules where status is true (active)
					this.moduleItems = Object.keys(rawModules).filter((key) => rawModules[key] === true);
				} catch (error) {
					this.handleError(error);
				} finally {
					this.ui.isLoadingModuleItems = false;
				}
			},

			/**
			 * Fetch existing permission details from API for editing
			 * Updates form fields and breadcrumb navigation
			 */
			async fetchData() {
				this.ui.isLoadingData = true;
				this.errors = {};
				try {
					const { data: response } = await instance.get(`/privilege/permission/show/${this.id}`);

					// Populate form with existing data
					this.form.name = response.data.name;
					this.form.description = response.data.description;
					this.form.module = response.data.module;
					this.form.guard_name = response.data.guard_name;

					// Update breadcrumbs to show current permission being edited
					this.breadcrumbItems.push({
						label: response.data.name,
						icon: 'i-lucide-pencil'
					});
				} catch (error) {
					this.handleError(error);
				} finally {
					this.ui.isLoadingData = false;
				}
			},

			// ====== FORM SUBMISSION ======

			/**
			 * Submit form to create new permission or update existing one
			 * Automatically determines endpoint and HTTP method based on mode (create/edit)
			 */
			async handleSubmit() {
				this.ui.isLoading = true;
				this.errors = {};

				// Determine endpoint and method based on whether editing or creating
				const url = this.id ? `/privilege/permission/update/${this.id}` : '/privilege/permission/store';
				const method = this.id ? 'put' : 'post';

				try {
					const { data: response } = await instance[method](url, this.form);

					// Show success notification
					this.toast.add({
						title: this.$t('Success'),
						description: response.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});

					// Redirect back to permission list
					this.$router.push('/module/privilege/permission');
				} catch (error) {
					// Handle validation errors (422 Unprocessable Entity)
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						this.handleError(error);
					}
				} finally {
					this.ui.isLoading = false;
				}
			},

			// ====== ERROR HANDLING ======

			/**
			 * Generic error notification helper
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
