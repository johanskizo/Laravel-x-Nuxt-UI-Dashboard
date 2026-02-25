<template>
	<!-- Disk storage card showing used/total and percentage indicator -->
	<UCard>
		<template #header>
			<div class="flex items-center gap-2 font-semibold">
				<UIcon name="i-lucide-hard-drive" class="text-info" />
				{{ $t('Disk Storage') }}
			</div>
		</template>

		<div class="space-y-2">
			<!-- Current disk usage in gigabytes -->
			<div class="flex justify-between text-sm">
				<span>{{ disk.used }} GB / {{ disk.total }} GB</span>
				<span :class="disk.percentage > 90 ? 'font-bold text-error' : ''"> {{ disk.percentage }}% </span>
			</div>

			<!-- Visual indicator for disk usage with threshold coloring -->
			<UProgress :color="disk.percentage > 90 ? 'error' : 'success'" :model-value="disk.percentage" />
		</div>
	</UCard>
</template>

<script>
	/**
	 * Dashboard disk storage card.
	 * Fetches `/dashboard/disk` once on mount and shows
	 * used/total disk space with a percentage bar.
	 */

	// Shared HTTP client instance
	import instance from '../../../../../resources/js/src/network/instance';

	export default {
		// Local reactive state
		data() {
			return {
				// Normalized disk statistics from the API
				stats: {
					usedGb: 0,
					totalGb: 0,
					percentage: 0
				}
			};
		},

		// Derived values consumed by the template
		computed: {
			/**
			 * Convenience object for template bindings.
			 */
			disk() {
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
			 * Fetches latest disk usage data from the backend
			 * and updates the internal `stats` object.
			 */
			async fetchDiskStats() {
				try {
					const { data: response } = await instance.get('/dashboard/disk');
					if (response && response.success && response.data) {
						this.stats.usedGb = response.data.used;
						this.stats.totalGb = response.data.total;
						this.stats.percentage = response.data.percentage;
					}
				} catch (err) {
					// Keep last known state if request fails
					// eslint-disable-next-line no-console
					console.error('StatsDisk.fetchDiskStats error', err);
				}
			}
		},

		// Lifecycle hooks
		mounted() {
			// Single fetch on mount; disk usage does not need high-frequency polling
			this.fetchDiskStats();
		}
	};
</script>
