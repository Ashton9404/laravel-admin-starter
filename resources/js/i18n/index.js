import { createI18n } from 'vue-i18n';
import axios from '@/bootstrap';
import en from './locales/en.json';
import zhTW from './locales/zh-TW.json';

export const SUPPORTED_LOCALES = {
    en: 'English',
    'zh-TW': '繁體中文',
};

export const DEFAULT_LOCALE = 'en';

const STORAGE_KEY = 'locale';

export function isSupported(locale) {
    return Object.prototype.hasOwnProperty.call(SUPPORTED_LOCALES, locale);
}

export function storedLocale() {
    const stored = localStorage.getItem(STORAGE_KEY);

    return isSupported(stored) ? stored : null;
}

/**
 * Best guess for someone who has never chosen: an exact tag first ("zh-TW"),
 * then the primary subtag, so a browser set to plain "zh" still lands somewhere
 * sensible instead of falling back to English.
 */
function browserLocale() {
    for (const tag of navigator.languages ?? [navigator.language]) {
        if (isSupported(tag)) {
            return tag;
        }

        const match = Object.keys(SUPPORTED_LOCALES).find(
            (locale) => locale.split('-')[0] === tag.split('-')[0],
        );

        if (match) {
            return match;
        }
    }

    return null;
}

export function resolveInitialLocale() {
    return storedLocale() ?? browserLocale() ?? DEFAULT_LOCALE;
}

const i18n = createI18n({
    // Composition API mode: `legacy: false` is what makes useI18n() available
    // inside <script setup>.
    legacy: false,
    locale: resolveInitialLocale(),
    fallbackLocale: DEFAULT_LOCALE,
    messages: { en, 'zh-TW': zhTW },
});

/**
 * Switch the UI language and remember it.
 *
 * Also sets <html lang>, which is what screen readers and the browser's own
 * translation prompt read — easy to forget, and wrong without it.
 */
export function setLocale(locale) {
    if (!isSupported(locale)) {
        return;
    }

    i18n.global.locale.value = locale;
    localStorage.setItem(STORAGE_KEY, locale);
    document.documentElement.setAttribute('lang', locale);

    // Server-side messages — validation errors above all — have to follow the
    // UI, or a Chinese form starts answering in English the moment it fails.
    axios.defaults.headers.common['Accept-Language'] = locale;
}

setLocale(i18n.global.locale.value);

export default i18n;
