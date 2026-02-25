import { createI18n } from 'vue-i18n';
import idMain from '../../../lang/id.json';
import enMain from '../../../lang/en.json';

const moduleFiles = import.meta.glob('../../../../Modules/*/Resources/lang/*.json', {
	eager: true
});

const messages = {
	id: { ...idMain },
	en: { ...enMain }
};

Object.keys(moduleFiles).forEach((path) => {
	const matched = path.match(/\/lang\/(.*)\.json$/);

	if (matched) {
		const locale = matched[1];

		const content = moduleFiles[path].default;

		if (!messages[locale]) {
			messages[locale] = {};
		}

		messages[locale] = {
			...messages[locale],
			...content
		};
	}
});

const i18n = createI18n({
	legacy: false,
	locale: import.meta.env.VITE_APP_LOCALE || 'en',
	fallbackLocale: import.meta.env.VITE_APP_LOCALE || 'en',
	messages
});

export default i18n;
