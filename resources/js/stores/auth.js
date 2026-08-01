import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import axios, { initCsrf } from '@/bootstrap';
import { setLocale, storedLocale } from '@/i18n';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const initialised = ref(false);

    const isAuthenticated = computed(() => user.value !== null);
    const isVerified = computed(() => user.value?.email_verified === true);
    const isAdmin = computed(() => user.value?.roles?.includes('admin') === true);

    /**
     * Mirrors the server-side check so the UI can hide what the API would
     * refuse anyway. This is presentation only — the API is the real gate.
     */
    function can(permission) {
        return isAdmin.value || user.value?.permissions?.includes(permission) === true;
    }

    /**
     * Resolve the session once on boot. A 401 here is the expected answer for a
     * guest, not an error, so it is swallowed rather than propagated.
     */
    async function initialise() {
        if (initialised.value) {
            return;
        }

        try {
            const { data } = await axios.get('/api/user');
            user.value = data.data;
            await adoptAccountLocale();
        } catch {
            user.value = null;
        } finally {
            initialised.value = true;
        }
    }

    /**
     * Reconcile the language stored on the account with the one in this browser.
     *
     * A saved preference wins, because it was chosen deliberately and should
     * follow the account to any device. If the account has none but this browser
     * does, push that up — so a language picked before signing in is not lost at
     * the moment of signing in.
     */
    async function adoptAccountLocale() {
        if (user.value?.locale) {
            setLocale(user.value.locale);

            return;
        }

        const local = storedLocale();

        if (local) {
            await persistLocale(local);
        }
    }

    async function persistLocale(next) {
        try {
            await axios.put('/api/user/locale', { locale: next });

            if (user.value) {
                user.value.locale = next;
            }
        } catch {
            // A failed sync is not worth interrupting the user for; the choice
            // still applies locally and will be retried on the next sign-in.
        }
    }

    async function changeLocale(next) {
        setLocale(next);

        if (isAuthenticated.value) {
            await persistLocale(next);
        }
    }

    async function login(credentials) {
        await initCsrf();
        const { data } = await axios.post('/api/login', credentials);
        user.value = data.data;
        await adoptAccountLocale();
    }

    async function register(details) {
        await initCsrf();
        const { data } = await axios.post('/api/register', details);
        user.value = data.data;
        await adoptAccountLocale();
    }

    async function logout() {
        try {
            await axios.post('/api/logout');
        } finally {
            // Drop the local session even if the request failed: whatever the
            // server thinks, the user asked to be logged out of this tab.
            user.value = null;
        }
    }

    async function forgotPassword(email) {
        await initCsrf();
        const { data } = await axios.post('/api/forgot-password', { email });

        return data.message;
    }

    async function resetPassword(payload) {
        await initCsrf();
        const { data } = await axios.post('/api/reset-password', payload);

        return data.message;
    }

    async function resendVerificationEmail() {
        const { data } = await axios.post('/api/email/verification-notification');

        return data.message;
    }

    async function refresh() {
        const { data } = await axios.get('/api/user');
        user.value = data.data;
    }

    return {
        user,
        initialised,
        isAuthenticated,
        isVerified,
        isAdmin,
        can,
        changeLocale,
        initialise,
        login,
        register,
        logout,
        forgotPassword,
        resetPassword,
        resendVerificationEmail,
        refresh,
    };
});
