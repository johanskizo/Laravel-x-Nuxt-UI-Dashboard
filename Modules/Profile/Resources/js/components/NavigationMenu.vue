<template>
	<!-- Profile area navigation menu shown in the dashboard toolbar -->
	<UNavigationMenu class="-mx-1 flex-1" :items="links" highlight />
</template>

<script>
	/**
	 * Simple navigation menu for the Profile module.
	 * Provides quick access to profile, account, security, and settings pages.
	 */

	export default {
		// Local reactive state
		data() {
			return {
				// Navigation groups and items used by `UNavigationMenu`
				links: [
					[
						{ icon: 'i-lucide-user', label: 'Profile', to: '/module/profile', exact: true },
						{ icon: 'i-lucide-at-sign', label: 'User Account', to: '/module/profile/user' },
						{ icon: 'i-lucide-lock', label: 'Security', to: '/module/profile/security' },
						{ icon: 'i-lucide-settings', label: 'Settings', to: '/module/profile/settings' }
					]
				]
			};
		}
	};
</script>
