<template>
	<!-- Main dashboard layout wrapper -->
	<DashboardLayout>
		<!-- Toolbar section with breadcrumb navigation -->
		<template #toolbar>
			<UDashboardToolbar>
				<template #left>
					<UBreadcrumb :items="breadcrumbItems" />
				</template>
			</UDashboardToolbar>
		</template>

		<!-- Dashboard body content -->
		<template #body>
			<div class="space-y-6 p-6">
				<!-- Page header with title and uptime badge -->
				<div class="flex items-center justify-between">
					<h1 class="text-2xl font-bold tracking-tight">{{ $t('System Overview') }}</h1>
					<UptimeBadge />
				</div>

				<!-- System resources statistics (CPU, RAM, Disk) -->
				<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
					<StatsCpu />
					<StatsRam />
					<StatsDisk />
				</div>

				<!-- Services status and latest logs -->
				<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
					<StatsServices />
					<StatsLogs class="lg:col-span-2" />
				</div>
			</div>
		</template>
	</DashboardLayout>
</template>

<script>
	/**
	 * ============================================================================
	 * System Dashboard Overview Page (Options API)
	 * ============================================================================
	 * Displays comprehensive system health and performance statistics with real-time monitoring.
	 *
	 * Features:
	 * - System uptime badge with formatted time display
	 * - CPU usage display with visual progress indicator
	 * - RAM/Memory usage with available memory metrics
	 * - Disk space usage showing total and available storage
	 * - Active services status list with health indicators
	 * - Latest system logs with filtering and pagination
	 * - Real-time data updates via periodic polling
	 * - Responsive grid layout for desktop and mobile
	 * - Dark mode support for all components
	 *
	 * Data Flow:
	 * 1. Component mounts → StatsComponents init and fetch data independently
	 * 2. Each stats component handles its own API requests and state
	 * 3. Components poll their endpoints periodically for real-time updates
	 * 4. Dashboard aggregates displayed data in card grid layout
	 * 5. Breadcrumb updates on navigation
	 *
	 * Components Used:
	 * - DashboardLayout: Main dashboard shell with navigation
	 * - UptimeBadge: System uptime display component
	 * - StatsCpu: CPU usage statistics component
	 * - StatsRam: Memory/RAM usage component
	 * - StatsDisk: Disk space usage component
	 * - StatsServices: Active services status list component
	 * - StatsLogs: Latest system logs component
	 * ============================================================================
	 */

	// Layout component for the dashboard shell
	import DashboardLayout from '../../../../../resources/js/src/components/DashboardLayout.vue';

	// Dashboard statistic and helper components
	import StatsCpu from '../components/StatsCpu.vue';
	import StatsDisk from '../components/StatsDisk.vue';
	import StatsLogs from '../components/StatsLogs.vue';
	import StatsRam from '../components/StatsRam.vue';
	import StatsServices from '../components/StatsServices.vue';
	import UptimeBadge from '../components/UptimeBadge.vue';

	export default {
		/**
		 * --- COMPONENT REGISTRATION ---
		 * All components used on this page
		 */
		components: {
			DashboardLayout,
			StatsCpu,
			StatsDisk,
			StatsLogs,
			StatsRam,
			StatsServices,
			UptimeBadge
		},

		/**
		 * --- DATA STATE ---
		 * Local reactive state properties
		 */
		data: () => ({
			// Navigation
			breadcrumbItems: [
				{ icon: 'i-lucide-box', label: 'Module', to: '/module' },
				{ icon: 'i-lucide-layout-dashboard', label: 'Dashboard', to: '/module/dashboard' }
			]
		})
	};
</script>
