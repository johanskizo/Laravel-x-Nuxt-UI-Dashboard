import { defineStore } from 'pinia';

export const usePrivilegeStore = defineStore('privilege', {
	state: () => ({}),
	actions: {
		setData(value) {},
		updateData(value) {},
		deleteData(value) {}
	},
	persist: true
});
