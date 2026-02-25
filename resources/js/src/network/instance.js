import axios from 'axios';
import Cookies from 'js-cookie';
import { useLangStore } from '../stores/lang';
import { useAuthenticationStore } from '../../../../Modules/Authentication/Resources/js/store';
import { router } from '../router';

const instance = axios.create({
	baseURL: import.meta.env.VITE_APP_URL ? import.meta.env.VITE_APP_URL + '/api' : window.Laravel.apiUrl,
	timeout: 10000,
	headers: {
		Accept: 'application/json',
		'Content-Type': 'application/json'
	}
});

instance.interceptors.request.use(
	(config) => {
		const token = Cookies.get('auth_token');
		if (token) {
			config.headers.Authorization = `Bearer ${token}`;
		}
		config.headers['Accept-Language'] = useLangStore().locale;
		return config;
	},
	(error) => Promise.reject(error)
);

instance.interceptors.response.use(
	(response) => response,
	(error) => {
		if (error.response.status === 401) {
			useAuthenticationStore().deleteData();
			Cookies.remove('auth_token');
			if (router.currentRoute.value.path !== '/') {
				router.push('/401');
			}
			useToast().add({
				title: 'Error',
				description: error.response.data.message,
				icon: 'i-lucide-ban',
				color: 'error'
			});
		} else if (error.response.status === 403) {
			router.push('/403');
		}
		return Promise.reject(error);
	}
);

export default instance;
