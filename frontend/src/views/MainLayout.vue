<template>
  <div class="flex flex-col min-h-dvh bg-red-100 border-4 border-solid border-red-300">
    <header class="w-full flex justify-between px-5 py-3 bg-purple-300">
      <span class="text-white font-bold text-3xl mx-2 my-2">
        LOEME
      </span>
      <div>
        <RouterLink to="/dashboard" class="mx-3 hover:underline" active-class="font-bold text-purple-600">
            Dashboard
        </RouterLink>
        <RouterLink to="/order-form"  class="mx-3 hover:underline"
          active-class="font-bold text-purple-600">
            New Order
        </RouterLink>
      </div>
      <div>
        <span class="text-2xl font-bold mr-2">{{ profileStore.profile.name }}</span>
        <button @click="hanleLogout" class="h-full text-white rounded-2xl px-5 bg-blue-700">
          Logout
        </button>
      </div>
    </header>
    <router-view />
  </div>
</template>
<script setup>
import { onMounted } from 'vue';
import { useProfileStore } from '@/stores/profile';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const profileStore = useProfileStore();
onMounted(async () => {
  await profileStore.loadProfile();
});

const hanleLogout = async () => {
  await authStore.logout();
  if (!authStore.isAuthenticated()) {
    router.push('/login')
  }
};

</script>