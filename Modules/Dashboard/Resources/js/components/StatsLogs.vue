<template>
	<!-- Recent application log entries with level and message preview -->
	<UCard class="mt-4">
		<template #header>
			<div class="flex items-center justify-between">
				<span class="font-semibold">
					{{ $t('Recent Logs') }}
				</span>

				<!-- Manual refresh button to reload logs on demand -->
				<UButton icon="i-lucide-refresh-cw" variant="ghost" @click="fetchData" />
			</div>
		</template>

		<div class="overflow-x-auto">
			<table class="w-full text-left text-sm">
				<thead>
					<tr class="border-b">
						<th class="p-2">{{ $t('Time') }}</th>
						<th class="p-2">Level</th>
						<th class="p-2">{{ $t('Message') }}</th>
					</tr>
				</thead>

				<tbody>
					<tr v-for="(log, index) in logs" class="border-b last:border-0" :key="index">
						<!-- Timestamp column -->
						<td class="p-2 font-mono text-xs">
							{{ log.time }}
						</td>

						<!-- Log level with color-coded badge -->
						<td class="p-2">
							<UBadge :color="log.level === 'error' ? 'error' : 'primary'" size="xs">
								{{ log.level }}
							</UBadge>
						</td>

						<!-- Truncated message with full text in tooltip -->
						<td class="max-w-xs p-2">
							<UTooltip :delay-duration="0" :text="log.message">
								<div class="truncate">
									{{ log.message }}
								</div>
							</UTooltip>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</UCard>
</template>

<script>
	/**
	 * Dashboard recent logs card.
	 * Fetches `/dashboard/logs` and presents a compact table of recent
	 * log entries with level badges and tooltips for full messages.
	 */

	// Shared HTTP client instance
	import instance from '../../../../../resources/js/src/network/instance';

	export default {
		// Local reactive state
		data() {
			return {
				// Latest logs array returned by the API
				logs: []
			};
		},

		// Methods organized by responsibility
		methods: {
			/**
			 * Fetches recent logs from the backend and replaces
			 * the local `logs` array when data is valid.
			 */
			async fetchLogs() {
				try {
					const { data: response } = await instance.get('/dashboard/logs');
					if (response && response.success && Array.isArray(response.data)) {
						this.logs = response.data;
					}
				} catch (err) {
					// eslint-disable-next-line no-console
					console.error('StatsLogs.fetchLogs error', err);
				}
			},

			/**
			 * Public method used by the refresh button to reload logs.
			 */
			async fetchData() {
				return this.fetchLogs();
			}
		},

		// Lifecycle hooks
		mounted() {
			// Load logs once when the component is mounted
			this.fetchLogs();
		}
	};
</script>
