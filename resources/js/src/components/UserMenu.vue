<template>
	<UDropdownMenu :items="items" :content="{ align: 'center', collisionPadding: 12 }">
		<UButton
			color="neutral"
			variant="ghost"
			:ui="{ trailingIcon: 'text-dimmed' }"
			class="data-[state=open]:bg-elevated"
			v-bind="{
				...user,
				label: collapsed ? undefined : user?.name,
				trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down'
			}"
			block
			:square="collapsed"
		/>

		<template #chip-leading="{ item }">
			<div class="inline-flex size-5 shrink-0 items-center justify-center">
				<span
					class="size-2 rounded-full bg-(--chip-light) ring ring-bg dark:bg-(--chip-dark)"
					:style="{
						'--chip-light': `var(--color-${item.chip}-500)`,
						'--chip-dark': `var(--color-${item.chip}-400)`
					}"
				/>
			</div>
		</template>
	</UDropdownMenu>
</template>

<script>
	import debounce from 'lodash/debounce';
	import Cookies from 'js-cookie';
	import { useI18n } from 'vue-i18n';
	import { useColorMode } from '@vueuse/core';
	import instance from '../network/instance';

	// Store Imports
	import { useAuthenticationStore } from '../../../../Modules/Authentication/Resources/js/store';
	import { useLangStore } from '../stores/lang';
	import { useThemeStore } from '../stores/theme';

	export default {
		props: {
			/**
			 * Determines if the sidebar is in a collapsed state
			 */
			collapsed: {
				type: Boolean,
				default: false
			}
		},

		setup() {
			const { locale, setLocale } = useI18n();
			const appConfig = useAppConfig();
			const colorMode = useColorMode();
			const toast = useToast();

			// Initialize Stores
			const authenticationStore = useAuthenticationStore();
			const themeStore = useThemeStore();
			const langStore = useLangStore();

			return {
				locale,
				setLocale,
				appConfig,
				colorMode,
				toast,
				authenticationStore,
				themeStore,
				langStore
			};
		},

		data: () => ({
			user_id: null,
			ui: {
				isLoading: false
			},
			// Theme palette options
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
		}),

		computed: {
			/**
			 * Formats user data from the authentication store
			 */
			user() {
				const authUser = this.authenticationStore.user;
				return {
					name: authUser?.name || 'Guest',
					avatar: {
						src: authUser?.profile?.photo_url || null,
						alt: authUser?.name || 'Guest'
					}
				};
			},

			/**
			 * Generates the multi-level dropdown menu items
			 */
			items() {
				return [
					// Section 1: Navigation
					[
						{ label: this.$t('Profile'), icon: 'i-lucide-user', to: '/module/profile' },
						{ label: 'Settings', icon: 'i-lucide-settings', to: '/module/profile/settings' }
					],
					// Section 2: Localization
					[
						{
							label: this.$t('Language'),
							icon: 'i-lucide-languages',
							children: [
								{
									label: 'English',
									type: 'checkbox',
									checked: this.langStore.locale === 'en',
									onSelect: (e) => {
										e.preventDefault();
										this.updateLocale('en');
									}
								},
								{
									label: 'Indonesia',
									type: 'checkbox',
									checked: this.langStore.locale === 'id',
									onSelect: (e) => {
										e.preventDefault();
										this.updateLocale('id');
									}
								}
							]
						}
					],
					// Section 3: Personalization (Theme & Appearance)
					[
						{
							label: this.$t('Theme'),
							icon: 'i-lucide-palette',
							children: [
								{
									label: this.$t('Primary'),
									slot: 'chip',
									chip: this.themeStore.primary,
									children: this.colors.map((color) => ({
										label: color,
										chip: color,
										slot: 'chip',
										type: 'checkbox',
										checked: this.themeStore.primary === color,
										onSelect: (e) => {
											e.preventDefault();
											this.updatePrimary(color);
										}
									}))
								},
								{
									label: this.$t('Neutral'),
									slot: 'chip',
									chip: this.themeStore.neutral === 'neutral' ? 'old-neutral' : this.themeStore.neutral,
									children: this.neutrals.map((color) => ({
										label: color,
										chip: color === 'neutral' ? 'old-neutral' : color,
										slot: 'chip',
										type: 'checkbox',
										checked: this.themeStore.neutral === color,
										onSelect: (e) => {
											e.preventDefault();
											this.updateNeutral(color);
										}
									}))
								}
							]
						},
						{
							label: this.$t('Appearance'),
							icon: 'i-lucide-sun-moon',
							children: [
								{
									label: this.$t('Light'),
									icon: 'i-lucide-sun',
									type: 'checkbox',
									checked: this.colorMode === 'light',
									onSelect: (e) => {
										e.preventDefault();
										this.updateAppearance('light');
									}
								},
								{
									label: this.$t('Dark'),
									icon: 'i-lucide-moon',
									type: 'checkbox',
									checked: this.colorMode === 'dark',
									onSelect: (e) => {
										e.preventDefault();
										this.updateAppearance('dark');
									}
								}
							]
						}
					],
					// Section 4: Authentication
					[
						{
							label: this.$t('Log out'),
							icon: 'i-lucide-log-out',
							onSelect: (e) => {
								e.preventDefault();
								this.handleLogout();
							}
						}
					]
				];
			}
		},

		created() {
			// Initialize debounced function for auto-saving settings
			this.debouncedSave = debounce(this.saveSettings, 500);
		},

		mounted() {
			this.fetchUser();
		},

		beforeUnmount() {
			// Clean up debounce listeners to prevent memory leaks
			this.debouncedSave.cancel();
		},

		methods: {
			/**
			 * Fetches current authenticated user data
			 */
			async fetchUser() {
				this.ui.isLoading = true;
				try {
					const { data: response } = await instance.get('/authentication/user');
					this.authenticationStore.setData(response);
					this.user_id = response.user.id;
				} catch (error) {
					console.error('Fetch user failed:', error);
				} finally {
					this.ui.isLoading = false;
				}
			},

			/**
			 * Persists UI settings to the backend
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
						description: error.response?.data?.message || 'Failed to save settings',
						icon: 'i-lucide-ban',
						color: 'error'
					});
				}
			},

			/**
			 * Updates application language
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

			/**
			 * Updates primary color theme
			 */
			updatePrimary(color) {
				this.themeStore.setPrimary(color);
				this.appConfig.ui.colors.primary = color;
				this.debouncedSave();
			},

			/**
			 * Updates neutral color theme
			 */
			updateNeutral(color) {
				this.themeStore.setNeutral(color);
				this.appConfig.ui.colors.neutral = color;
				this.debouncedSave();
			},

			/**
			 * Toggles light/dark mode
			 */
			updateAppearance(mode) {
				this.colorMode = mode;
				this.themeStore.setDarkMode(mode === 'dark');
				this.debouncedSave();
			},

			/**
			 * Handles user logout process
			 */
			async handleLogout() {
				try {
					const response = await instance.post('/authentication/logout');
					this.toast.add({
						title: this.$t('Success'),
						description: response.data.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});
				} catch (error) {
					console.error('Logout error:', error);
				} finally {
					// Clear local storage and tokens regardless of API success
					this.authenticationStore.deleteData();
					Cookies.remove('auth_token');
					this.$router.push('/');
				}
			}
		}
	};
</script>
