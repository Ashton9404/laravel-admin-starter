import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import axios, { initCsrf } from '@/bootstrap';

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
        } catch {
            user.value = null;
        } finally {
            initialised.value = true;
        }
    }

    async function login(credentials) {
        await initCsrf();
        const { data } = await axios.post('/api/login', credentials);
        user.value = data.data;
    }

    async function register(details) {
        await initCsrf();
        const { data } = await axios.post('/api/register', details);
        user.value = data.data;
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
