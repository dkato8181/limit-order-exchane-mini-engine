import { defineStore } from 'pinia';
import { reactive } from 'vue';
import api from '@/api/axios'

export const useProfileStore = defineStore('profile', () => {
  const profile = reactive({
    id: "",
    name: "",
    email: "",
    balance: "",
    assets: []
  });

  async function loadProfile() {
    const response = await api.get('/api/profile');
    console.log("Profile response:",response);
    
    if (response.status === 200) {
      profile.id = response.data.data.id;
      profile.name = response.data.data.name;
      profile.email = response.data.data.email;
      profile.balance = response.data.data.balance;
      profile.assets = response.data.data.assets;
    }
  }

  return {
    profile,
    loadProfile,
  };
});