import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

// OPTIONAL: request interceptor (no auth headers!)
api.interceptors.request.use(
  async (config) => {
    if (config.url?.includes('/login') || config.url?.includes('/register')) {
      await axios.get('http://localhost:8000/sanctum/csrf-cookie', {
        withCredentials: true
      });
    }

    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => Promise.reject(error)
);

// ✅ Response interceptor (419 handler)
api.interceptors.response.use(
  response => response,
  async error => {
    const config = error.config;

    if (error.response?.status === 419 && !config._retry) {
      config._retry = true;

      // Refresh CSRF cookie (WITH credentials)
      await api.get('/sanctum/csrf-cookie');

      // Retry original request
      return api(config);
    }

    return Promise.reject(error);
  }
);

export default api;
