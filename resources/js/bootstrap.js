import axios from 'axios';

window.axios = axios;

axios.defaults.baseURL = '/';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Sanctum's SPA mode authenticates with the Laravel session cookie, so every
// request must carry cookies and echo back the XSRF-TOKEN cookie as a header.
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

/**
 * Ask Laravel to set the XSRF-TOKEN cookie. Required before the first
 * state-changing request of a session.
 */
export async function initCsrf() {
    await axios.get('/sanctum/csrf-cookie');
}

/**
 * Turn a Laravel error response into a flat { field: message } object so form
 * components never have to know about the 422 envelope.
 */
export function validationErrors(error) {
    const errors = error.response?.data?.errors ?? {};

    return Object.fromEntries(
        Object.entries(errors).map(([field, messages]) => [field, messages[0]]),
    );
}

export function errorMessage(error, fallback = 'Something went wrong. Please try again.') {
    return error.response?.data?.message ?? fallback;
}

export default axios;
