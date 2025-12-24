import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/axios'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([]);
  const orderBook = ref([]);
  const isLoading = ref(false);
  const error = ref('');
  const fieldErrors = ref({});
  const availableAssets = ref([]);

  async function loadAvailableAssets() {
    const response = await api.get('/api/available-assets');
    console.log("Available assests response:",response.data.assets);
    availableAssets.value = response.data.assets;
  }

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

  async function placeOrder(orderData) {
    try{
      isLoading.value = true
      error.value = '';
      fieldErrors.value = {};
      const response = await api.post('/api/orders', orderData);
      console.log("Place order response:", response);
      if (response.status === 200) {
        console.log("Order placed successfully");
      }
    }
    catch (err) {
      if(err.response?.status === 422){
        fieldErrors.value = err.response.data.errors || {};
        error.value = 'Please fix the validation errors below';
      }
      else {
        error.value = err.response?.data?.message || 'Failed to place order';
      }
      console.log('Place order error', err)
    }
    finally{
      isLoading.value = false
    }
  }

  return {
    orders,
    orderBook,
    isLoading,
    error,
    fieldErrors,
    availableAssets,
    loadAvailableAssets,
    loadOrders,
    loadOrderBook,
    placeOrder,
  };
});
