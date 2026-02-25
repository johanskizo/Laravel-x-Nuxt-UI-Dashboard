import { defineStore } from 'pinia';

export const useDashboardStore = defineStore('dashboard', {
	state: () => ({}),
	actions: {
		setData(value) {},
		updateData(value) {},
		deleteData(value) {}
	},
	persist: true
});
