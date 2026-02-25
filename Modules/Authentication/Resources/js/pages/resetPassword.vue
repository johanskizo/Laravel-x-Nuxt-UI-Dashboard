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
	 * Password Reset Page (Options API)
	 * ============================================================================
	 * Page reached from password reset email link with secure token.
	 * Allows users to set a new password for their account.
	 *
	 * Features:
	 * - Reset token extraction from URL path parameter
	 * - Email verification via query parameter
	 * - Password and confirmation fields
	 * - Form validation with field-level error display (422 handling)
	 * - Success notification and redirect to login
	 * - Responsive UI with dark mode support
	 *
	 * Data Flow:
	 * 1. User receives password reset email with link:
	 *    /reset-password/securetoken123?email=user@example.com
	 * 2. Page loads and extracts token and email from URL
	 * 3. User enters new password → handleResetPassword()
	 * 4. API validates token, email, and password requirements
	 * 5. Success: Display toast notification + redirect to login
	 * 6. Failure: Show form validation errors or general error message
	 *
	 * URL Structure:
	 * - Route param: :token - Unique password reset token
	 * - Query param: ?email - User email for verification
	 *
	 * Security Context:
	 * - Token-based access control (no session required)
	 * - Email verification for added security
	 * - One-time use tokens (typically expire after 60 minutes)
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

				// Form fields for password reset
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
			 * Submit password reset form to backend API
			 * Validates token, email, and new password
			 * Redirects to login on successful password update
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

					// Redirect to login page
					this.$router.push('/authentication/login');
				} catch (error) {
					// Handle validation errors (HTTP 422 Unprocessable Entity)
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						// Handle other errors (token expired, server error, etc.)
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
