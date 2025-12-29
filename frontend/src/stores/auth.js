import { defineStore } from 'pinia';
import { reactive } from 'vue';
import api from '@/api/axios'
import router from '@/router';

export const useAuthStore = defineStore('auth', () => {
  const credentials = reactive({
    email: "",
    password: "",
    remember: false,
  });

  async function login() {
    console.log("Logging in with", credentials);
    const response = await api.post('/api/login', credentials)
    console.log("Login response:", response)
    if (response.status === 200) {
      localStorage.setItem('auth_token', response.data.access_token);
      localStorage.setItem('token_type', response.data.token_type);
      console.log("Token stored:", localStorage.getItem('auth_token'));
      //window.location.href = '/dashboard';
    }
  }

  function isAuthenticated() {
    return !!localStorage.getItem('auth_token');
  }

  async function logout() {
    try {
      const response = await api.post('/api/logout')
      console.log("Logout response:", response)
    } catch (error) {
      console.error('Logout error:', error);
      if (error.response?.status === 419) {
        // CSRF expired/missing — try once more
        try {
          await api.get('/sanctum/csrf-cookie');
          await api.post('/api/logout');
        } catch (e) {
          console.error('Logout retry failed:', e);
        }
      }
      if (error.response?.status === 401) {
        console.warn('Logout returned 401 — clearing token');
      }
    } finally {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('token_type');
      router.push('/login');
    }
  }

  return {
    credentials,
    login,
    logout,
    isAuthenticated,
  };
});
  