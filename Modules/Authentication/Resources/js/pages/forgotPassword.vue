<template>
	<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gray-50 p-4 dark:bg-gray-950">
		<div class="w-full max-w-md">
			<div class="mb-6 text-center">
				<h1 class="text-4xl font-black tracking-tighter text-gray-900 italic dark:text-white">
					{{ appName }}<span class="text-5xl text-primary-500">.</span>
				</h1>
				<p class="font-medium text-gray-500 italic dark:text-gray-400">
					{{ $t('Set your new password') }}
				</p>
			</div>

			<UCard
				:ui="{
					body: { base: 'p-8' },
					ring: 'ring-1 ring-gray-200 dark:ring-gray-800',
					shadow: 'shadow-2xl',
					rounded: 'rounded-3xl'
				}"
			>
				<UForm @submit="handleResetPassword" class="space-y-5">
					<UFormField name="password" :label="$t('New Password')" :error="errors.password?.[0]" size="xl">
						<UInput
							v-model="form.password"
							type="password"
							placeholder="••••••••"
							icon="i-heroicons-lock-closed"
							variant="soft"
							class="w-full"
						/>
					</UFormField>

					<UFormField name="password_confirmation" :label="$t('Confirm New Password')" size="xl">
						<UInput
							v-model="form.password_confirmation"
							type="password"
							placeholder="••••••••"
							icon="i-heroicons-lock-closed"
							variant="soft"
							class="w-full"
						/>
					</UFormField>

					<UButton
						type="submit"
						:label="$t('Update Password')"
						:loading="ui.isLoading"
						size="xl"
						class="rounded-xl font-bold shadow-lg shadow-primary-500/20 transition-all active:scale-[0.98]"
						block
					/>
				</UForm>
			</UCard>
		</div>
	</div>
</template>

<script>
	/**
	 * ============================================================================
	 * Forgot Password Reset Page (Options API)
	 * ============================================================================
	 * Page accessed from "Forgot Password" email link with reset token.
	 * Allows users to set a new password after clicking email link.
	 *
	 * Features:
	 * - Reset token validation (passed as URL parameter)
	 * - Email verification (passed as URL query parameter)
	 * - New password input with confirmation field
	 * - Form validation with error message display (422 handling)
	 * - Success notification before redirect to login
	 * - Responsive design with dark mode support
	 *
	 * Data Flow:
	 * 1. User receives email with reset link: /forgot-password/resettoken?email=user@example.com
	 * 2. User lands on this page, token and email extracted from URL
	 * 3. User enters new password and confirmation → handleResetPassword()
	 * 4. Backend validates token, email, and password strength
	 * 5. Success: Show toast + redirect to login
	 * 6. Error: Display validation errors or show generic error
	 *
	 * URL Parameters:
	 * - :token (URL param) - Password reset token from email link
	 * - ?email (query param) - User email address for verification
	 *
	 * Authentication Context:
	 * - Public route - accessible without authentication
	 * - Pre-authenticated link - token provides access control
	 * - Post-reset: User must login with new password
	 * ============================================================================
	 */

	import instance from '../../../../../resources/js/src/network/instance';

	export default {
		/**
		 * --- SETUP & COMPOSABLES INITIALIZATION ---
		 */
		setup() {
			const toast = useToast();
			return { toast };
		},

		/**
		 * --- DATA STATE ---
		 * All reactive properties organized by purpose
		 */
		data() {
			return {
				// Application configuration
				appName: import.meta.env.VITE_APP_NAME || 'Laravel Nuxt',

				// UI state flags
				ui: {
					isLoading: false
				},

				// Form fields including reset token and email from URL
				form: {
					token: this.$route.params.token,
					email: this.$route.query.email,
					password: '',
					password_confirmation: ''
				},

				// Validation errors from backend
				errors: {}
			};
		},

		/**
		 * --- METHODS ---
		 * Password reset form submission
		 */
		methods: {
			/**
			 * ====== FORM SUBMISSION ======
			 */

			/**
			 * Submit new password to reset account password
			 * Validates token, email, and password before API call
			 * Redirects to login on success
			 */
			async handleResetPassword() {
				this.ui.isLoading = true;
				this.errors = {};

				try {
					const { data: response } = await instance.post('/authentication/reset-password', this.form);

					// Show success notification to user
					this.toast.add({
						title: this.$t('Success'),
						description: response.message || 'Your password has been updated.',
						icon: 'i-lucide-circle-check',
						color: 'success'
					});

					// Redirect user to login with new credentials
					this.$router.push('/authentication/login');
				} catch (error) {
					// Handle validation errors (HTTP 422 Unprocessable Entity)
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						// Handle other errors (token expired, etc.)
						this.toast.add({
							title: this.$t('Error'),
							description: error.response?.data?.message || this.$t('Something went wrong'),
							icon: 'i-lucide-ban',
							color: 'error'
						});
					}
				} finally {
					this.ui.isLoading = false;
				}
			}
		}
	};
</script>
