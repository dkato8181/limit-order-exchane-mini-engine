<template>
  <div class="w-screen h-screen bg-gray-800 ">
    <div class="flex flex-col items-center justify-center min-h-full px-4 sm:px-6 lg:px-8">
  <h1 class="text-5xl font-bold text-white mb-6">Login</h1>
  <form @submit.prevent="handleLogin" class="p-6">
    <div class="my-5">
      <label for="email"  class="text-white">Email:</label>
      <input id="email" class="outline-2 outline-gray-600 ml-10.5 p-1 rounded text-white" v-model="email" type="email" required />
    </div>
    <div class="my-5">
      <label for="password" class="text-white">Password:</label>
      <input id="password" class="outline-2 outline-gray-600 ml-3 p-1 rounded text-white" v-model="password" type="password" required />
    </div>
    <div class="my-5">
      <label class="text-white">
        <input type="checkbox" v-model="remember" class="mr-2" />
        Remember Me
      </label>
    </div>
    <div v-if="error" class="text-red-500 mb-4">{{ error }}</div>
    <button type="submit" :disabled="loading" class="bg-blue-500 text-white px-4 py-2 rounded">
      {{ loading ? 'Logging in...' : 'Login' }}
    </button>
  </form>
  </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const remember = ref(false)

const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
//TODO use papasswordInputType to toggle input type
const passwordInputType = computed(()=>showPassword.value?'text':'password')

async function handleLogin() {
  try {
    authStore.credentials.email = email.value
    authStore.credentials.password = password.value
    authStore.credentials.remember = remember.value
    error.value = ''
    loading.value = true
    
    await authStore.login()
  } catch (err) {
    console.log(err);
    
    error.value = err.errors?.email?.[0] || err.message || 'Login failed. Please check your credentials.'
  } finally {
    loading.value = false
  }

  if (authStore.isAuthenticated()) {
    router.push('/dashboard')
  }
}
</script>