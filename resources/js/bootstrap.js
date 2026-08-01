import axios from 'axios';

window.axios = axios;

axios.defaults.baseURL = '/';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Sanctum's SPA mode authenticates with the Laravel session cookie, so every
// request must carry cookies and echo back the XSRF-TOKEN cookie as a header.
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

export default axios;
