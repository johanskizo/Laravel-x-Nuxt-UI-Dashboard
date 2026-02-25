import { defineStore } from 'pinia';

export const useLangStore = defineStore('lang', {
	state: () => ({
		locale: import.meta.env.VITE_APP_LOCALE || 'en'
	}),
	actions: {
		setData(payload) {
			this.locale = payload;
		}
	},
	persist: true
});
