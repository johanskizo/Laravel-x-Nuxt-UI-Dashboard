<template>
	<!-- Root application shell that wires Nuxt UI theme and locale -->
	<Suspense>
		<UApp :locale="locales[locale]">
			<RouterView />
		</UApp>
	</Suspense>
</template>

<script>
	/**
	 * Root Vue application component.
	 * Binds Nuxt UI's `UApp` to the current theme and language
	 * persisted in Pinia stores, and renders the active `RouterView`.
	 */

	// External composables
	import { useColorMode } from '@vueuse/core';
	import { useI18n } from 'vue-i18n';

	// Nuxt UI locales
	import * as locales from '@nuxt/ui/locale';

	// Application stores
	import { useLangStore } from './stores/lang';
	import { useThemeStore } from './stores/theme';

	export default {
		// Composition API setup for global theme and locale
		setup() {
			const { locale } = useI18n();
			const colorMode = useColorMode();
			const appConfig = useAppConfig();
			const themeStore = useThemeStore();
			const langStore = useLangStore();

			// Initialize color mode and UI palette from persisted theme store
			colorMode.value = themeStore.darkMode ? 'dark' : 'light';
			appConfig.ui.colors.primary = themeStore.primary;
			appConfig.ui.colors.neutral = themeStore.neutral;

			// Initialize app locale from language store
			locale.value = langStore.locale;

			return {
				locales,
				locale
			};
		}
	};
</script>
