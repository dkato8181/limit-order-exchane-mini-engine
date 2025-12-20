import { defineStore } from 'pinia';
import { reactive } from 'vue';
import api from '@/api/axios'

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
      window.location.href = '/dashboard';
    }
  }

  function isAuthenticated() {
    return !!localStorage.getItem('auth_token');
  }

  async function logout() {
    const response = await api.post('/api/logout')
    localStorage.removeItem('auth_token');
    localStorage.removeItem('token_type');
  }

  return {
    credentials,
    login,
    logout,
    isAuthenticated,
  };
});
  