<template>
	<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gray-50 p-4 dark:bg-gray-950">
		<div class="w-full max-w-md">
			<div class="mb-6 text-center">
				<h1 class="text-4xl font-black tracking-tighter text-gray-900 italic dark:text-white">
					{{ appName }}<span class="text-5xl text-primary-500">.</span>
				</h1>
				<p class="font-medium text-gray-500 dark:text-gray-400">
					{{ $t('Login to your account') }}
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
				<UForm class="space-y-5" @submit="handleLogin">
					<UFormField name="email" :error="errors.email?.[0]" size="xl">
						<UInput
							v-model="form.email"
							type="email"
							icon="i-heroicons-envelope"
							placeholder="admin@mail.com"
							class="w-full"
							variant="soft"
						/>
					</UFormField>

					<UFormField name="password" :error="errors.password?.[0]" size="xl">
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

					<div class="flex items-center justify-between">
						<UCheckbox v-model="form.remember" :label="$t('Remember me')" />
						<ULink
							to="/authentication/forgot-password"
							class="text-sm text-primary-500 hover:text-primary-600 hover:underline"
						>
							{{ $t('Forgot your password?') }}
						</ULink>
					</div>

					<UButton
						type="submit"
						:label="$t('Login')"
						:loading="ui.isLoading"
						class="rounded-xl font-bold shadow-lg shadow-primary-500/20 transition-all active:scale-[0.98]"
						block
						size="xl"
					/>
				</UForm>

				<template #footer>
					<div class="text-center">
						<span class="text-sm text-gray-500">{{ $t("Don't have an account?") }} &nbsp;</span>
						<ULink
							to="/authentication/signup"
							class="text-sm font-bold text-primary-500 hover:text-primary-600 hover:underline"
						>
							{{ $t('Sign Up') }}
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
	 * User Login Authentication Page (Options API)
	 * ============================================================================
	 * Primary authentication entry point for existing users with session recovery.
	 *
	 * Features:
	 * - Email and password credential validation
	 * - Password visibility toggle for better UX
	 * - "Remember Me" checkbox for persistent sessions
	 * - Session recovery from existing auth tokens
	 * - Automatic user preference sync (theme, language) after login
	 * - Form validation with error message display (422 handling)
	 * - Pinia store integration for global auth state
	 * - Cookie-based token persistence with secure settings
	 * - Conditional navigation based on user profile existence
	 * - Responsive design with dark mode support
	 *
	 * Data Flow:
	 * 1. Page mounts → checkExistingSession() attempts token recovery from cookie
	 * 2. User enters email/password → handleLogin() validates and sends POST
	 * 3. Success: Store token in store + cookie → Sync user preferences → Navigate
	 * 4. Profile exists: Redirect to /module/dashboard
	 * 5. No profile: Redirect to /module/profile/edit (complete profile setup)
	 * 6. Error: Display field-level errors or generic error message
	 * ============================================================================
	 */

	import Cookies from 'js-cookie';
	import { useI18n } from 'vue-i18n';
	import { useColorMode } from '@vueuse/core';
	import instance from '../../../../../resources/js/src/network/instance';
	import { useAuthenticationStore } from '../store';
	import { useLangStore } from '../../../../../resources/js/src/stores/lang';
	import { useThemeStore } from '../../../../../resources/js/src/stores/theme';

	export default {
		/**
		 * --- SETUP & COMPOSABLES INITIALIZATION ---
		 */
		setup() {
			const { locale, setLocale } = useI18n();
			const appConfig = useAppConfig();
			const colorMode = useColorMode();
			const toast = useToast();

			const authenticationStore = useAuthenticationStore();
			const langStore = useLangStore();
			const themeStore = useThemeStore();

			return {
				locale,
				setLocale,
				appConfig,
				colorMode,
				authenticationStore,
				toast,
				langStore,
				themeStore
			};
		},

		/**
		 * --- DATA STATE ---
		 */
		data: () => ({
			// Application configuration
			appName: import.meta.env.VITE_APP_NAME || 'Laravel Nuxt',

			// UI state flags
			ui: {
				showPassword: false,
				isLoading: false
			},

			// Form fields
			form: {
				email: '',
				password: '',
				remember: false
			},

			// Validation errors from backend
			errors: {}
		}),

		/**
		 * --- LIFECYCLE HOOKS ---
		 */
		async mounted() {
			await this.checkExistingSession();
		},

		/**
		 * --- METHODS ---
		 */
		methods: {
			// ====== AUTHENTICATION HANDLERS ======

			/**
			 * Handles the login form submission with validation and error handling
			 * Sends email/password to backend and processes response
			 */
			async handleLogin() {
				this.ui.isLoading = true;
				this.errors = {};

				try {
					const { data: response } = await instance.post('/authentication/login', this.form);

					// Save Auth data to Store and Cookie
					this.setAuthenticationData(response);

					// Sync User Preferences (Theme/Lang) if available
					if (response.user.settings) {
						const settings = response.user.settings;

						// Language Sync
						this.langStore.setData(settings.language);
						if (typeof this.setLocale === 'function') {
							this.setLocale(settings.language);
						} else {
							this.locale = settings.language;
						}

						// Theme Sync
						this.themeStore.setPrimary(settings.ui_primary);
						this.appConfig.ui.colors.primary = settings.ui_primary;
						this.themeStore.setNeutral(settings.ui_neutral);
						this.appConfig.ui.colors.neutral = settings.ui_neutral;

						// Dark Mode Sync
						this.colorMode = settings.ui_dark_mode;
						this.themeStore.setDarkMode(settings.ui_dark_mode === 'dark');
					}

					// Navigation Logic based on Profile existence
					if (response.user.profile) {
						this.$router.push('/module/dashboard');
					} else {
						this.$router.push('/module/profile');
					}
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

			// ====== SESSION RECOVERY ======
			/**
			 * Attempts to recover an existing session from stored auth token
			 * Validates token and restores user data if token is valid
			 * On failure, removes invalid token from cookies
			 */
			async checkExistingSession() {
				if (!Cookies.get('auth_token')) return;

				this.ui.isLoading = true;
				try {
					const { data: response } = await instance.get('/authentication/user');
					this.setAuthenticationData(response);
					this.$router.push('/module/dashboard');
				} catch (error) {
					console.error('Session recovery failed');
					Cookies.remove('auth_token');
				} finally {
					this.ui.isLoading = false;
				}
			},

			// ====== TOKEN & STATE MANAGEMENT ======
			/**
			 * Centralized method to handle token storage and Pinia store updates
			 * Saves token to secure cookie and updates authentication store
			 *
			 * @param {Object} data - Authentication response data containing token and user info
			 * @param {Object} data.token - Token object with expires_at timestamp
			 * @param {Object} data.user - Authenticated user object
			 */
			setAuthenticationData(data) {
				this.authenticationStore.setData(data);

				if (data.token) {
					Cookies.set('auth_token', data.token.token, {
						expires: new Date(data.token.expires_at.replace(' ', 'T')),
						secure: true,
						sameSite: 'lax'
					});
				}
			}
		}
	};
</script>
