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
	 * Role Create/Edit Form Component (Options API)
	 * ============================================================================
	 * Handles creating new roles and editing existing ones.
	 * Features:
	 * - Form validation with error message display
	 * - Role name, description, and guard type configuration
	 * - Support for both 'web' and 'api' guard types
	 * - Breadcrumb navigation with context-aware titles
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

				// Breadcrumb navigation
				breadcrumbItems: [
					{ label: 'Module', icon: 'i-lucide-box', to: '/module' },
					{ label: 'Privilege', icon: 'i-lucide-shield-check' },
					{ label: 'Role', icon: 'i-lucide-hash', to: '/module/privilege/role' }
				],

				// UI state flags
				ui: {
					isLoading: false,
					isLoadingData: false
				},

				// Main form payload
				form: {
					name: '',
					description: '',
					guard_name: 'api'
				},

				// Validation errors from backend
				errors: {}
			};
		},

		// Lifecycle hooks
		mounted() {
			// Check if editing an existing role or creating new one
			if (this.$route.params.id) {
				// Edit mode: load existing role data
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
			 * Fetch existing role details from API for editing
			 * Updates form fields and breadcrumb navigation
			 */
			async fetchData() {
				this.ui.isLoadingData = true;
				this.errors = {};
				try {
					const { data: response } = await instance.get(`/privilege/role/show/${this.id}`);

					// Populate form with existing data
					this.form.name = response.data.name;
					this.form.description = response.data.description;
					this.form.guard_name = response.data.guard_name;

					// Update breadcrumbs to show current role being edited
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
			 * Submit form to create new role or update existing one
			 * Automatically determines endpoint and HTTP method based on mode (create/edit)
			 */
			async handleSubmit() {
				this.ui.isLoading = true;
				this.errors = {};

				// Determine endpoint and method based on whether editing or creating
				const url = this.id ? `/privilege/role/update/${this.id}` : '/privilege/role/store';
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

					// Redirect back to role list
					this.$router.push('/module/privilege/role');
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
