<template>
	<!-- CPU usage card with current load and progress bar -->
	<UCard>
		<template #header>
			<div class="flex items-center gap-2 font-semibold">
				<UIcon name="i-lucide-cpu" class="text-info" />
				{{ $t('CPU Usage') }}
			</div>
		</template>

		<div class="space-y-2">
			<!-- Current CPU load in percentage -->
			<div class="flex justify-between text-sm">
				<span>{{ $t('Load Percentage') }}</span>
				<span class="font-mono">{{ cpu }}%</span>
			</div>

			<!-- Visual indicator for CPU load with threshold coloring -->
			<UProgress :color="cpu > 90 ? 'error' : 'success'" :model-value="cpu" />
		</div>
	</UCard>
</template>

<script>
	/**
	 * Dashboard CPU statistics card.
	 * Periodically fetches CPU load from `/dashboard/cpu`
	 * and displays it as both text and a progress bar.
	 */

	// Shared HTTP client instance
	import instance from '../../../../../resources/js/src/network/instance';

	export default {
		// Local reactive state
		data() {
			return {
				// Normalized CPU statistics returned by the API
				stats: {
					cpuPercentage: 0
				},
				// Interval ID for periodic refresh
				timerId: null
			};
		},

		// Derived values consumed by the template
		computed: {
			/**
			 * Current CPU percentage value used by the UI.
			 */
			cpu() {
				return this.stats.cpuPercentage;
			}
		},

		// Methods organized by responsibility
		methods: {
			/**
			 * Fetches latest CPU usage data from the backend and
			 * updates the internal `stats` object.
			 */
			async fetchCpuStats() {
				try {
					const { data: response } = await instance.get('/dashboard/cpu');
					if (response && response.success && response.data && response.data.cpu !== undefined) {
						this.stats.cpuPercentage = response.data.cpu;
					}
				} catch (err) {
					// Keep last known state if request fails
					// eslint-disable-next-line no-console
					console.error('StatsCpu.fetchCpuStats error', err);
				}
			}
		},

		// Lifecycle hooks
		mounted() {
			// Initial fetch and then refresh every 5 seconds
			this.fetchCpuStats();
			this.timerId = setInterval(() => this.fetchCpuStats(), 5000);
		},

		beforeUnmount() {
			// Clear polling interval to avoid memory leaks
			if (this.timerId) clearInterval(this.timerId);
		}
	};
</script>
