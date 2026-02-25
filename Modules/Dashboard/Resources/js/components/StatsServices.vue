<template>
	<!-- List of monitored services and their current status -->
	<UCard>
		<template #header>
			<div class="font-semibold">
				{{ $t('Services Status') }}
			</div>
		</template>

		<div class="space-y-3">
			<div v-for="service in services" class="flex items-center justify-between" :key="service.name">
				<span class="text-sm">{{ service.name }}</span>

				<!-- Status badge indicating whether service is running or stopped -->
				<UBadge :color="service.status ? 'success' : 'error'" variant="subtle">
					{{ service.status ? $t('Running') : $t('Stopped') }}
				</UBadge>
			</div>
		</div>
	</UCard>
</template>

<script>
	/**
	 * Dashboard services status card.
	 * Periodically polls `/dashboard/services` and renders a list
	 * of services with their current running/stopped state.
	 */

	// Shared HTTP client instance
	import instance from '../../../../../resources/js/src/network/instance';

	export default {
		// Local reactive state
		data() {
			return {
				// List of service objects returned by the API
				services: [],
				// Interval ID for periodic refresh
				timerId: null
			};
		},

		// Methods organized by responsibility
		methods: {
			/**
			 * Loads the latest services state from the backend
			 * and replaces the local `services` array.
			 */
			async fetchServices() {
				try {
					const { data: response } = await instance.get('/dashboard/services');
					if (response && response.success && Array.isArray(response.data)) {
						this.services = response.data;
					}
				} catch (err) {
					// Keep last known state if request fails
					// eslint-disable-next-line no-console
					console.error('StatsServices.fetchServices error', err);
				}
			}
		},

		// Lifecycle hooks
		mounted() {
			// Initial fetch and then refresh every 30 seconds
			this.fetchServices();
			this.timerId = setInterval(() => this.fetchServices(), 30000);
		},

		beforeUnmount() {
			// Clear polling interval to avoid memory leaks
			if (this.timerId) clearInterval(this.timerId);
		}
	};
</script>
