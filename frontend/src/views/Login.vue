<template>
  <div class="w-screen h-screen bg-gray-800 ">
    <div class="flex flex-col items-center justify-center min-h-full px-4 sm:px-6 lg:px-8">
  <h1 class="text-5xl font-bold text-white mb-6">Login</h1>
  <form @submit.prevent="handleLogin" class="bg-gray-200 p-6">
    <div class="my-2">
      <label for="email"  class="text-white">Email:</label>
      <input id="email" v-model="form.email" type="email" required />
    </div>
    <div class="my-2">
      <label for="password" class="text-white">Password:</label>
      <input id="password" v-model="form.password" type="password" required />
    </div>
    <div class="my-2">
      <label class="text-white">
        <input type="checkbox" v-model="form.remember" /> Remember me
      </label>
    </div>
    <div v-if="error" class="error-message">{{ error }}</div>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Logging in...' : 'Login' }}
    </button>
  </form>
  </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: '',
  remember: false
})

const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

async function handleLogin() {
  try {
    error.value = ''
    loading.value = true
    
    await authStore.login({
      email: form.value.email,
      password: form.value.password
    })
    
    router.push('/dashboard')
  } catch (err) {
    error.value = err.errors?.email?.[0] || err.message || 'Login failed. Please check your credentials.'
  } finally {
    loading.value = false
  }
}
</script>