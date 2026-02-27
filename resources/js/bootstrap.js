import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Request interceptor to add guest token
window.axios.interceptors.request.use(config => {
    const token = localStorage.getItem('guest_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Response interceptor to save guest token
window.axios.interceptors.response.use(response => {
    const token = response.headers['x-guest-token'];
    if (token) {
        localStorage.setItem('guest_token', token);
    }
    return response;
}, error => {
    return Promise.reject(error);
});
