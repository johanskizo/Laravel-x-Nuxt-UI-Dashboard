<template>
	<DashboardLayout>
		<template #toolbar>
			<UDashboardToolbar>
				<NavigationMenu />
			</UDashboardToolbar>
		</template>

		<template #body>
			<UPageCard
				:title="$t('Password')"
				:description="$t('Confirm your current password before setting a new one.')"
				variant="subtle"
			>
				<UForm @submit="handleSubmit">
					<div class="mx-auto flex w-full flex-col gap-x-8 gap-y-4">
						<UFormField
							name="old_password"
							:label="$t('Current Password')"
							required
							:error="errors.old_password?.[0]"
							:ui="{ labelWrapper: 'w-47 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.old_password" type="password" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="password"
							:label="$t('New Password')"
							required
							:error="errors.password?.[0]"
							:ui="{ labelWrapper: 'w-47 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.password" type="password" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="password_confirmation"
							:label="$t('Confirm New Password')"
							required
							:error="errors.password_confirmation?.[0]"
							:ui="{ labelWrapper: 'w-47 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.password_confirmation" type="password" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>
					</div>

					<div class="flex justify-end gap-3 py-4">
						<UButton
							:label="$t('Back')"
							icon="i-lucide-arrow-left"
							color="neutral"
							variant="outline"
							@click="$router.back()"
						/>
						<UButton type="submit" :label="$t('Update Password')" :loading="ui.isLoading" color="primary" />
					</div>
				</UForm>
			</UPageCard>

			<UPageCard class="mt-8" variant="naked" orientation="horizontal">
				<template #title>{{ $t('Active Logins') }}</template>
			</UPageCard>

			<div v-if="active_logins.length > 0" class="mt-4">
				<UPageCard variant="subtle">
					<ul class="divide-y divide-gray-200 dark:divide-gray-800">
						<li v-for="login in active_logins" class="flex items-center justify-between py-4" :key="login.id">
							<div class="flex items-center gap-4">
								<UIcon name="i-lucide-monitor" class="h-8 w-8 text-gray-400" />
								<div class="flex flex-col">
									<span class="line-clamp-1 text-sm font-semibold">{{ login.name }}</span>
									<div class="flex flex-col text-xs text-gray-500">
										<span>IP: {{ login.ip_address }}</span>
										<span>{{ $t('Last active') }}: {{ new Date(login.last_used_at).toLocaleString() }}</span>
									</div>
								</div>
							</div>
							<UButton
								icon="i-lucide-log-out"
								color="error"
								variant="ghost"
								size="sm"
								@click="handleLogoutSession(login.id)"
							/>
						</li>
					</ul>
				</UPageCard>
			</div>

			<div class="mt-4" v-else>
				<UPageCard class="flex justify-center py-8" variant="subtle">
					<span class="text-sm text-gray-500 italic">{{ $t('No other active sessions found') }}</span>
				</UPageCard>
			</div>
		</template>
	</DashboardLayout>
</template>

<script>
	/**
	 * ============================================================================
	 * User Security & Password Management (Options API)
	 * ============================================================================
	 * Password change and active session management for enhanced account security.
	 *
	 * Features:
	 * - Current password verification before allowing password change
	 * - New password with confirmation matching validation (422 errors)
	 * - Active login sessions display with device information
	 * - Session logout from specific devices/browsers
	 * - IP address and last-active timestamp display
	 * - Real-time session list refresh after logout
	 * - Form reset after successful password update
	 * - Error handling with field-level (422) and generic messages
	 * - Navigation menu with quick links to profile sub-pages
	 * - Empty state message when no other sessions active
	 *
	 * Data Flow:
	 * 1. Component mounts → Fetch active sessions from API
	 * 2. User enters password credentials → handleSubmit() → validation
	 * 3. Success: Show toast + Reset form + Refresh sessions
	 * 4. User clicks logout on session → handleLogoutSession(sessionId)
	 * 5. Backend removes session → Refresh active_logins list -> UI updates
	 * 6. Error: Display field-level (422) or generic error messages
	 *
	 * Components Used:
	 * - DashboardLayout: Main navigation wrapper
	 * - NavigationMenu: Profile sub-page navigation (Profile, Privacy, Security, Settings)
	 * - UForm: Form wrapper with submit handler
	 * - UInput/UFormField: Password input fields with error display
	 * - UButton: Update button with loading state and individual session logout buttons
	 * - UIcon: Device icon for login session visual
	 * - UPageCard: Card wrapper sections for password form and sessions list
	 * ============================================================================
	 */

	// 1. Networking & Store
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
			return { toast, authenticationStore };
		},

		/**
		 * --- DATA STATE ---
		 */
		data() {
			return {
				// User Identity
				id: null,

				// Navigation Menu Links
				links: [
					[
						{ label: 'Profile', icon: 'i-lucide-user', to: '/module/profile', exact: true },
						{ label: 'Privacy', icon: 'i-lucide-shield', to: '/module/profile/privacy' },
						{ label: 'Security', icon: 'i-lucide-lock', to: '/module/profile/security' },
						{ label: 'Settings', icon: 'i-lucide-settings', to: '/module/profile/settings' }
					]
				],

				// UI Loading States
				ui: {
					isLoading: false,
					isLoadingData: false
				},

				// Password Form Fields
				form: {
					old_password: '',
					password: '',
					password_confirmation: ''
				},

				// Session & Validation
				active_logins: [],
				errors: {}
			};
		},

		/**
		 * --- LIFECYCLE HOOKS ---
		 */
		mounted() {
			if (this.authenticationStore.user?.id) {
				this.id = this.authenticationStore.user?.id;
				this.fetchData();
			}
		},

		/**
		 * --- METHODS ---
		 */
		methods: {
			// ====== DATA FETCHING ======

			/**
			 * Fetches active login sessions from API
			 * Populates active_logins array with device info and timestamps
			 * Clears any previous errors on fetch
			 */
			async fetchData() {
				this.ui.isLoadingData = true;
				this.errors = {};
				try {
					const { data: response } = await instance.get(`/profile/security/show/${this.id}`);
					this.active_logins = response.data.active_logins || [];
				} catch (error) {
					this.toast.add({
						title: 'Error',
						description: error.response?.data?.message || this.$t('Something went wrong'),
						icon: 'i-lucide-ban',
						color: 'error'
					});
				} finally {
					this.ui.isLoadingData = false;
				}
			},

			// ====== PASSWORD UPDATE ======
			/**
			 * Submits password change form with authentication
			 * Validates old password and new password confirmation
			 * Resets form and refreshes sessions on success
			 */
			async handleSubmit() {
				this.ui.isLoading = true;
				this.errors = {};
				try {
					const { data: response } = await instance.put(`/profile/security/update-password/${this.id}`, this.form);

					this.toast.add({
						title: this.$t('Success'),
						description: response.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});

					this.resetForm();
					this.fetchData();
				} catch (error) {
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						this.toast.add({
							title: 'Error',
							description: error.response?.data?.message || this.$t('Something went wrong'),
							icon: 'i-lucide-ban',
							color: 'error'
						});
					}
				} finally {
					this.ui.isLoading = false;
				}
			},

			// ====== SESSION MANAGEMENT ======
			/**
			 * Logs out a specific device session by ID
			 * Removes device from active_logins after successful deletion
			 * Refreshes session list to reflect changes
			 *
			 * @param {number} sessionId - ID of session to log out
			 */
			async handleLogoutSession(sessionId) {
				try {
					const { data: response } = await instance.delete(`/profile/security/session-logout/${sessionId}`);
					this.toast.add({
						title: this.$t('Success'),
						description: response.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});
					this.fetchData();
				} catch (error) {
					this.toast.add({
						title: 'Error',
						description: error.response?.data?.message || this.$t('Something went wrong'),
						icon: 'i-lucide-ban',
						color: 'error'
					});
				}
			},

			// ====== FORM HELPERS ======
			/**
			 * Resets password form fields and errors to initial state
			 * Called after successful password update
			 */
			resetForm() {
				this.form = { old_password: '', password: '', password_confirmation: '' };
				this.errors = {};
			}
		}
	};
</script>
