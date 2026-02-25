<template>
	<!-- Memory usage card with used/total and percentage indicator -->
	<UCard>
		<template #header>
			<div class="flex items-center gap-2 font-semibold">
				<UIcon name="i-lucide-memory-stick" class="text-info" />
				{{ $t('Memory Usage') }}
			</div>
		</template>

		<div class="space-y-2">
			<!-- Current RAM usage in gigabytes -->
			<div class="flex justify-between text-sm">
				<span>{{ ram.used }} GB / {{ ram.total }} GB</span>
				<span class="font-mono">{{ ram.percentage }}%</span>
			</div>

			<!-- Visual indicator for memory load with threshold coloring -->
			<UProgress :color="ram.percentage > 90 ? 'error' : 'success'" :model-value="ram.percentage" />
		</div>
	</UCard>
</template>

<script>
	/**
	 * Dashboard memory (RAM) statistics card.
	 * Polls `/dashboard/ram` for current memory usage and
	 * displays used/total values together with a percentage bar.
	 */

	// Shared HTTP client instance
	import instance from '../../../../../resources/js/src/network/instance';

	export default {
		// Local reactive state
		data() {
			return {
				// Normalized RAM statistics from the API
				stats: {
					usedGb: 0,
					totalGb: 0,
					percentage: 0
				},
				// Interval ID for periodic refresh
				timerId: null
			};
		},

		// Derived values consumed by the template
		computed: {
			/**
			 * Convenience object for template bindings.
			 */
			ram() {
				return {
					used: this.stats.usedGb,
					total: this.stats.totalGb,
					percentage: this.stats.percentage
				};
			}
		},

		// Methods organized by responsibility
		methods: {
			/**
			 * Fetches latest RAM usage data from the backend
			 * and updates the internal `stats` object.
			 */
			async fetchRamStats() {
				try {
					const { data: response } = await instance.get('/dashboard/ram');
					if (response && response.success && response.data) {
						this.stats.usedGb = response.data.used;
						this.stats.totalGb = response.data.total;
						this.stats.percentage = response.data.percentage;
					}
				} catch (err) {
					// Keep last known state if request fails
					// eslint-disable-next-line no-console
					console.error('StatsRam.fetchRamStats error', err);
				}
			}
		},

		// Lifecycle hooks
		mounted() {
			// Initial fetch and then refresh every 10 seconds
			this.fetchRamStats();
			this.timerId = setInterval(() => this.fetchRamStats(), 10000);
		},

		beforeUnmount() {
			// Clear polling interval to avoid memory leaks
			if (this.timerId) clearInterval(this.timerId);
		}
	};
</script>
