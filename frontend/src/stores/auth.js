import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const isAuthenticated = ref(false)

  async function register(credentials) {
    try {
      const response = await api.post('/api/register', credentials)
      return response.data
    } catch (error) {
      throw error.response.data
    }
  }

  async function login(credentials) {
    try {
      const response = await api.post('/api/login', credentials)
      user.value = response.data.user
      isAuthenticated.value = true
      return response.data
    } catch (error) {
      throw error.response.data
    }
  }

  async function logout() {
    try {
      await api.post('/api/logout')
      user.value = null
      isAuthenticated.value = false
    } catch (error) {
      console.error('Logout error:', error)
    }
  }

  async function fetchUser() {
    try {
      const response = await api.get('/api/user')
      user.value = response.data
      isAuthenticated.value = true
    } catch (error) {
      user.value = null
      isAuthenticated.value = false
    }
  }

  return {
    user,
    isAuthenticated,
    register,
    login,
    logout,
    fetchUser
  }
})