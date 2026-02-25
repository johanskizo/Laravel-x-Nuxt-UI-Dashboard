<template>
	<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gray-50 p-4 dark:bg-gray-950">
		<div class="w-full max-w-md">
			<div class="mb-6 text-center">
				<h1 class="text-4xl font-black tracking-tighter text-gray-900 italic dark:text-white">
					{{ appName }}<span class="text-5xl text-primary-500">.</span>
				</h1>
				<p class="font-medium text-gray-500 dark:text-gray-400">
					{{ $t('Create a new account') }}
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
				<UForm class="space-y-5" @submit="handleSignup">
					<UFormField name="name" :label="$t('Username')" :error="errors.name?.[0]" size="xl">
						<UInput
							v-model="form.name"
							type="text"
							icon="i-heroicons-user"
							placeholder="username123"
							class="w-full"
							variant="soft"
						/>
					</UFormField>

					<UFormField name="email" :label="$t('Email')" :error="errors.email?.[0]" size="xl">
						<UInput
							v-model="form.email"
							type="email"
							icon="i-heroicons-envelope"
							placeholder="example@mail.com"
							class="w-full"
							variant="soft"
						/>
					</UFormField>

					<UFormField name="password" :label="$t('Password')" :error="errors.password?.[0]" size="xl">
						<UInput
							v-model="form.password"
							:type="ui.showPassword ? 'text' : 'password'"
							icon="i-heroicons-lock-closed"
							placeholder="••••••••"
							:ui="{ trailing: 'pr-2' }"
							class="w-full"
							variant="soft"
						>
							<template #trailing>
								<UButton
									:icon="ui.showPassword ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'"
									class="-mr-1"
									color="gray"
									variant="ghost"
									@click="ui.showPassword = !ui.showPassword"
								/>
							</template>
						</UInput>
					</UFormField>

					<UFormField name="password_confirmation" :label="$t('Confirm Password')" size="xl">
						<UInput
							v-model="form.password_confirmation"
							:type="ui.showPassword ? 'text' : 'password'"
							icon="i-heroicons-lock-closed"
							placeholder="••••••••"
							class="w-full"
							variant="soft"
						/>
					</UFormField>

					<UButton
						type="submit"
						:label="$t('Sign Up')"
						:loading="ui.isLoading"
						class="rounded-xl font-bold shadow-lg shadow-primary-500/20 transition-all active:scale-[0.98]"
						block
						size="xl"
					/>
				</UForm>

				<template #footer>
					<div class="text-center">
						<span class="text-sm text-gray-500">{{ $t('Already have an account?') }} &nbsp;</span>
						<ULink
							to="/authentication/login"
							class="text-sm font-bold text-primary-500 hover:text-primary-600 hover:underline"
						>
							{{ $t('Login') }}
						</ULink>
					</div>
				</template>
			</UCard>
		</div>
	</div>
</template>

<script>
	/**
	 * ============================================================================
	 * User Registration/Sign-Up Page (Options API)
	 * ============================================================================
	 * Public-facing authentication page for new user account creation.
	 *
	 * Features:
	 * - Form validation with error message display (422 handling)
	 * - Password visibility toggle for better UX
	 * - Username, email, password, and password confirmation fields
	 * - Success toast notification before redirect to login
	 * - Responsive design with dark mode support
	 * - Link to existing login page for account holders
	 *
	 * Data Flow:
	 * 1. User fills in registration form (name, email, password, confirmation)
	 * 2. User clicks Sign Up button → handleSignup() validates and sends POST request
	 * 3. Backend validates (422 = validation errors) or creates user (201 = success)
	 * 4. Success: Show toast + redirect to login page
	 * 5. Error: Display field-level errors or generic error message
	 *
	 * Authentication Context:
	 * - Public route - no authentication required
	 * - Post-signup: User redirected to login page with credentials
	 * - Form data: name, email, password, password_confirmation
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
		data: () => ({
			// Application configuration
			appName: import.meta.env.VITE_APP_NAME || 'Laravel Nuxt',

			// UI state flags
			ui: {
				isLoading: false,
				showPassword: false
			},

			// Form fields for registration
			form: {
				name: '',
				email: '',
				password: '',
				password_confirmation: ''
			},

			// Validation errors from backend
			errors: {}
		}),

		/**
		 * --- METHODS ---
		 * All form submission and error handling logic
		 */
		methods: {
			/**
			 * ====== FORM SUBMISSION ======
			 */

			/**
			 * Handle user registration form submission
			 * Sends registration data to backend API
			 * Handles validation errors (422) and general errors
			 */
			async handleSignup() {
				this.ui.isLoading = true;
				this.errors = {};

				try {
					const { data: response } = await instance.post('/authentication/signup', this.form);

					// Show success notification
					this.toast.add({
						title: this.$t('Success'),
						description: response.message || 'Your account has been successfully registered.',
						icon: 'i-lucide-circle-check',
						color: 'success'
					});

					// Redirect to login page for user to sign in
					this.$router.push('/authentication/login');
				} catch (error) {
					// Handle validation errors (HTTP 422 Unprocessable Entity)
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						// Handle other backend errors
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
			}
		}
	};
</script>
