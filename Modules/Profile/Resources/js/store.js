import { defineStore } from 'pinia';

export const useProfileStore = defineStore('profile', {
	state: () => ({}),
	actions: {
		setData(value) {},
		updateData(value) {},
		deleteData(value) {}
	},
	persist: true
});
