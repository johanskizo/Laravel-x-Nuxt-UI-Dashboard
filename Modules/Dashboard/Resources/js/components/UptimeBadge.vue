<template>
	<!-- Simple badge showing current system uptime -->
	<UBadge icon="i-lucide-clock" color="neutral" variant="subtle" size="lg"> {{ $t('Uptime') }}: {{ uptime }} </UBadge>
</template>

<script>
	/**
	 * Small dashboard badge that periodically fetches and displays
	 * system uptime from the backend `/dashboard/uptime` endpoint.
	 */

	// HTTP client instance shared across the app
	import instance from '../../../../../resources/js/src/network/instance';

	export default {
		// Local reactive state
		data() {
			return {
				// Human‑readable uptime string returned by the API
				uptimeText: 'Loading...',
				// Interval ID used for periodic refresh
				timerId: null
			};
		},

		// Derived values for template binding
		computed: {
			/**
			 * Exposes uptime text for the template.
			 */
			uptime() {
				return this.uptimeText;
			}
		},

		// Methods grouped by responsibility
		methods: {
			/**
			 * Fetches latest uptime information from the dashboard API
			 * and updates local state when a valid response is received.
			 */
			async fetchUptime() {
				try {
					const { data: response } = await instance.get('/dashboard/uptime');
					if (response && response.success && response.data && response.data.uptime) {
						this.uptimeText = response.data.uptime;
					}
				} catch (err) {
					// Logging only – UI stays in last known state
					// eslint-disable-next-line no-console
					console.error('UptimeBadge.fetchUptime error', err);
				}
			}
		},

		// Lifecycle hooks
		mounted() {
			// Initial fetch plus periodic refresh every 60 seconds
			this.fetchUptime();
			this.timerId = setInterval(() => this.fetchUptime(), 60000);
		},

		beforeUnmount() {
			// Clean up interval when component is destroyed
			if (this.timerId) clearInterval(this.timerId);
		}
	};
</script>
