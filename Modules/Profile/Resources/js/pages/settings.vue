<template>
	<DashboardLayout>
		<template #toolbar>
			<UDashboardToolbar>
				<NavigationMenu />
			</UDashboardToolbar>
		</template>

		<template #body>
			<div class="flex flex-col gap-6">
				<UPageCard
					:title="$t('Appearance')"
					:description="$t('Customize how the application looks on your device.')"
					variant="subtle"
				>
					<div class="space-y-6">
						<UFormField :label="$t('Color Mode')" :help="$t('Switch between light and dark mode.')">
							<USelect
								v-model="colorMode"
								class="w-64"
								:items="appearanceOptions"
								@update:model-value="updateAppearance"
							>
								<template #leading>
									<UIcon :name="colorMode === 'dark' ? 'i-lucide-moon' : 'i-lucide-sun'" />
								</template>
							</USelect>
						</UFormField>

						<USeparator />

						<UFormField :label="$t('Primary Color')" :help="$t('Choose your primary accent color.')">
							<div class="flex flex-wrap gap-2">
								<UButton
									v-for="color in colors"
									class="flex size-5 items-center justify-center rounded-full p-0 transition-transform hover:scale-110"
									:key="color"
									:variant="themeStore.primary === color ? 'solid' : 'soft'"
									:style="{
										backgroundColor:
											themeStore.primary === color ? `var(--color-${color}-500)` : `var(--color-${color}-100)`,
										color: themeStore.primary === color ? 'white' : `var(--color-${color}-700)`
									}"
									@click="updatePrimary(color)"
								>
									<UIcon v-if="themeStore.primary === color" name="i-lucide-check" class="size-4" />
								</UButton>
							</div>
						</UFormField>

						<USeparator />

						<UFormField :label="$t('Neutral Color')" :help="$t('Choose the grayscale tone for the interface.')">
							<div class="flex flex-wrap gap-2 pt-2">
								<UButton
									v-for="color in neutrals"
									class="rounded-md px-3 py-1 text-xs capitalize"
									:key="color"
									color="neutral"
									:variant="themeStore.neutral === color ? 'solid' : 'outline'"
									:style="{
										backgroundColor: themeStore.neutral === color ? undefined : `var(--color-${color}-100)`
									}"
									@click="updateNeutral(color)"
								>
									{{ color }}
								</UButton>
							</div>
						</UFormField>
					</div>
				</UPageCard>

				<UPageCard
					:title="$t('Language')"
					:description="$t('Select your preferred language for the interface.')"
					variant="subtle"
				>
					<UFormField :label="$t('Display Language')">
						<USelect
							v-model="selectedLanguage"
							class="w-64"
							:items="languageOptions"
							@update:model-value="updateLocale"
						>
							<template #leading>
								<UIcon name="i-lucide-languages" />
							</template>
						</USelect>
					</UFormField>
				</UPageCard>
			</div>
		</template>
	</DashboardLayout>
</template>

<script>
	/**
	 * ============================================================================
	 * User Interface Settings & Preferences (Options API)
	 * ============================================================================
	 * Appearance customization and language preference management with debounced saving.
	 *
	 * Features:
	 * - Color mode toggle (Light/Dark) with system color mode support
	 * - Primary accent color picker (17 colors) with visual selection buttons
	 * - Neutral/grayscale tone selector (slate, gray, zinc, neutral, stone)
	 * - Language selection dropdown (English, Indonesian)
	 * - Debounced API persistence (500ms delay) during rapid adjustments
	 * - Real-time UI updates via Pinia stores
	 * - Toast notifications for save success/failure
	 * - Automatic preference sync on login (from Authentication module)
	 * - Navigation menu links to other profile pages
	 * - Responsive color/language picker with visual feedback
	 * - Debounce cleanup on component unmount
	 *
	 * Data Flow:
	 * 1. Component mounted → Initialize user_id from auth store
	 * 2. User clicks color/language/mode option → Update handler called
	 * 3. Handler updates Pinia store (themeStore/langStore) → UI re-renders instantly
	 * 4. Handler calls debouncedSave() → 500ms timer starts
	 * 5. User can make multiple changes without redundant API calls
	 * 6. Debounce timeout triggers → saveSettings() POSTs all settings at once
	 * 7. Backend persists in user settings table
	 * 8. Success toast displayed; errors caught and displayed
	 * 9. Component unmounts → Debounce timer cancelled to prevent memory leak
	 *
	 * Debounce Pattern:
	 * - Avoids hammering API during rapid UI adjustments (clicking multiple colors)
	 * - Single API call consolidates all pending changes
	 * - 500ms delay provides smooth UX without server burden
	 * - Debounce instance created in `created()` hook
	 * - Timeout explicitly cancelled in `beforeUnmount()` hook
	 *
	 * Store Sync:
	 * - themeStore: Primary color, neutral tone, dark mode boolean
	 * - langStore: Current locale/language code
	 * - appConfig: UI color overrides for Nuxt UI components
	 * - Used on login: Settings sync from Authentication module
	 * ============================================================================
	 */

	// 1. External Libraries
	import debounce from 'lodash/debounce';
	import { useI18n } from 'vue-i18n';
	import { useColorMode } from '@vueuse/core';

	// 2. Networking & Stores
	import instance from '../../../../../resources/js/src/network/instance';
	import { useThemeStore } from '../../../../../resources/js/src/stores/theme';
	import { useLangStore } from '../../../../../resources/js/src/stores/lang';
	import { useAuthenticationStore } from '../../../../Authentication/Resources/js/store';

	// 3. Components
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
		 * --- COMPOSABLES & INITIALIZATION ---
		 */
		setup() {
			const { locale, setLocale } = useI18n();
			const appConfig = useAppConfig();
			const colorMode = useColorMode();
			const toast = useToast();

			// Global Stores
			const themeStore = useThemeStore();
			const langStore = useLangStore();
			const authenticationStore = useAuthenticationStore();

			return {
				locale,
				setLocale,
				appConfig,
				colorMode,
				toast,
				themeStore,
				langStore,
				authenticationStore
			};
		},

		/**
		 * --- DATA STATE ---
		 */
		data() {
			return {
				// User Identity
				user_id: null,

				// Navigation Menu Links
				links: [
					[
						{ label: 'Profile', icon: 'i-lucide-user', to: '/module/profile', exact: true },
						{ label: 'Privacy', icon: 'i-lucide-shield', to: '/module/profile/privacy' },
						{ label: 'Security', icon: 'i-lucide-lock', to: '/module/profile/security' },
						{ label: 'Settings', icon: 'i-lucide-settings', to: '/module/profile/settings' }
					]
				],

				// Settings Options
				languageOptions: [
					{ label: 'English', value: 'en' },
					{ label: 'Indonesia', value: 'id' }
				],
				colors: [
					'red',
					'orange',
					'amber',
					'yellow',
					'lime',
					'green',
					'emerald',
					'teal',
					'cyan',
					'sky',
					'blue',
					'indigo',
					'violet',
					'purple',
					'fuchsia',
					'pink',
					'rose'
				],
				neutrals: ['slate', 'gray', 'zinc', 'neutral', 'stone']
			};
		},

		/**
		 * --- COMPUTED PROPERTIES ---
		 */
		computed: {
			/**
			 * Appearance options for color mode selector
			 * Returns array with Light/Dark mode options
			 */
			appearanceOptions() {
				return [
					{ label: this.$t('Light'), value: 'light', icon: 'i-lucide-sun' },
					{ label: this.$t('Dark'), value: 'dark', icon: 'i-lucide-moon' }
				];
			},

			/**
			 * Two-way binding for selected language
			 * Reads from langStore, updates trigger updateLocale()
			 */
			selectedLanguage: {
				get() {
					return this.langStore.locale;
				},
				set(val) {
					this.updateLocale(val);
				}
			}
		},

		/**
		 * --- LIFECYCLE HOOKS ---
		 */
		created() {
			/**
			 * Initialize debounced save method (500ms delay)
			 * Prevents API hammering during rapid UI adjustments
			 * Example: user clicks multiple colors in quick succession
			 */
			this.debouncedSave = debounce(this.saveSettings, 500);
		},

		mounted() {
			if (this.authenticationStore.user?.id) {
				this.user_id = this.authenticationStore.user.id;
			}
		},

		beforeUnmount() {
			// Clean up debounce timer to prevent memory leaks
			this.debouncedSave.cancel();
		},

		/**
		 * --- METHODS ---
		 */
		methods: {
			// ====== API PERSISTENCE ======

			/**
			 * Persists all settings to backend API
			 * Consolidates all pending UI changes into single API call
			 * Called by debouncedSave after 500ms delay
			 */
			async saveSettings() {
				if (!this.user_id) return;
				try {
					const { data: response } = await instance.post(`/profile/settings/save/${this.user_id}`, {
						ui_primary: this.themeStore.primary,
						ui_neutral: this.themeStore.neutral,
						ui_dark_mode: this.themeStore.darkMode ? 'dark' : 'light',
						language: this.langStore.locale
					});

					this.toast.add({
						title: this.$t('Success'),
						description: response.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});
				} catch (error) {
					this.toast.add({
						title: 'Error',
						description: error.response?.data?.message || this.$t('Something went wrong'),
						icon: 'i-lucide-ban',
						color: 'error'
					});
				}
			},

			// ====== LANGUAGE HANDLERS ======

			/**
			 * Updates language locale and triggers debounced save
			 * Syncs langStore and i18n instance
			 * Updates appConfig if available
			 *
			 * @param {string} value - Language code (e.g., 'en', 'id')
			 */
			updateLocale(value) {
				this.langStore.setData(value);
				if (typeof this.setLocale === 'function') {
					this.setLocale(value);
				} else {
					this.locale = value;
				}
				this.debouncedSave();
			},

			// ====== APPEARANCE/THEME HANDLERS ======

			/**
			 * Updates primary accent color and triggers debounced save
			 * Updates both themeStore and appConfig for consistency
			 * Visual feedback shown by color button highlight
			 *
			 * @param {string} color - Color name (e.g., 'blue', 'red')
			 */
			updatePrimary(color) {
				this.themeStore.setPrimary(color);
				this.appConfig.ui.colors.primary = color;
				this.debouncedSave();
			},

			/**
			 * Updates neutral/grayscale tone and triggers debounced save
			 * Affects gray backgrounds and border colors throughout UI
			 * Updates both themeStore and appConfig
			 *
			 * @param {string} color - Neutral tone name (slate, gray, zinc, neutral, stone)
			 */
			updateNeutral(color) {
				this.themeStore.setNeutral(color);
				this.appConfig.ui.colors.neutral = color;
				this.debouncedSave();
			},

			/**
			 * Updates color mode (light/dark) and triggers debounced save
			 * Syncs system color mode with themeStore dark mode flag
			 * Updates component theme immediately for responsive UI
			 *
			 * @param {string} value - Color mode ('light' or 'dark')
			 */
			updateAppearance(value) {
				this.colorMode = value;
				this.themeStore.setDarkMode(value === 'dark');
				this.debouncedSave();
			}
		}
	};
</script>
