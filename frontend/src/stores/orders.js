import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/axios'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([]);
  async function loadOrders() {
    const response = await api.get('/api/orders');
    console.log("Orders response:", response);
    if (response.status === 200) {
      orders.value = response.data.orders;
    }
  }
  return {
    orders,
    loadOrders,
  };
});
