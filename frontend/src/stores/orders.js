import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/axios'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([]);
  const orderBook = ref([]);
  const isLoading = ref(false);
  const error = ref('');
  const fieldErrors = ref({});

  async function loadOrders() {
    const response = await api.get('/api/orders');
    console.log("Orders response:", response);
    if (response.status === 200) {
      orders.value = response.data.orders;
    }
  }

  async function loadOrderBook(symbol, status=1) {
    const response = await api.get('/api/orders', { params: { symbol:symbol, status: status } });
    console.log("Order book response:", response);
    if (response.status === 200) {
      orderBook.value = response.data.orders;
    }
  }

  return {
    orders,
    orderBook,
    isLoading,
    error,
    fieldErrors,
    loadOrders,
    loadOrderBook,
  };
});
