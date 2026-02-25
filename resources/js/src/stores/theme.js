import { defineStore } from 'pinia';

export const useThemeStore = defineStore('theme', {
	state: () => ({
		darkMode: false,
		primary: 'green',
		neutral: 'neutral'
	}),
	actions: {
		setData(payload) {
			this.darkMode = payload.darkMode;
			this.primary = payload.primary;
			this.neutral = payload.neutral;
		},
		setPrimary(color) {
			this.primary = color;
		},
		setNeutral(color) {
			this.neutral = color;
		},
		setDarkMode(value) {
			this.darkMode = value;
		},
		toggleDarkMode() {
			this.darkMode = !this.darkMode;
		}
	},
	persist: true
});
