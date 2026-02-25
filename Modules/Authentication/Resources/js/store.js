import { defineStore } from 'pinia';

export const useAuthenticationStore = defineStore('authentication', {
	state: () => ({
		user: null,
		roles: [],
		permissions: [],
		token: null
	}),
	getters: {
		can: (state) => {
			return (permission) => state.permissions?.includes(permission) || false;
		},
		hasRole: (state) => {
			return (role) => state.roles?.includes(role) || false;
		},
		hasAnyRole: (state) => {
			return (rolesArray) => state.roles?.some((r) => rolesArray.includes(r)) || false;
		}
	},
	actions: {
		setData(payload) {
			this.user = payload.user;
			this.roles = payload.roles;
			this.permissions = payload.permissions;
			this.token = payload.token.token;
		},
		updateData(value) {
			this.user = { ...this.user, ...value };
		},
		deleteData() {
			this.user = null;
			this.roles = [];
			this.permissions = [];
			this.token = null;
		}
	},
	persist: true
});
