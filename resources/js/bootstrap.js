import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Keep session alive by refreshing the CSRF token every 15 minutes
setInterval(() => {
    axios.get('/csrf-token')
        .then(response => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && response.data.token) {
                meta.setAttribute('content', response.data.token);
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.token;
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = response.data.token;
                });
            }
        })
        .catch(() => {}); // Silently ignore (user may be logged out)
}, 15 * 60 * 1000);

// Intercept 419 (CSRF token expired) responses and reload the page gracefully
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            window.location.reload();
            return new Promise(() => {}); // Prevent further error handling while reloading
        }
        return Promise.reject(error);
    }
);
