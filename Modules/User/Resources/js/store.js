import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
	state: () => ({}),
	actions: {
		setData(value) {},
		updateData(value) {},
		deleteData(value) {}
	},
	persist: true
});
