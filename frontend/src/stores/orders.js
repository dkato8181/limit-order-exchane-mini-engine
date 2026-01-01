import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/api/axios'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([]);
  const orderBook = ref([]);
  const isLoading = ref(false);
  const error = ref('');
  const fieldErrors = ref({});
  const availableAssets = ref([]);

  const order = reactive({
    side: 'buy',
    symbol: '',
    price: null,
    amount: null,
  });

  function resetOrder() {
    order.side = 'buy';
    order.symbol = '';
    order.price = null;
    order.amount = null;
  }

  async function loadAvailableAssets() {
    const response = await api.get('/api/available-assets');
    console.log("Available assests response:",response.data.assets);
    availableAssets.value = response.data.assets;
  }

  async function loadOrders() {
    const response = await api.get('/api/orders');
    console.log("Orders response:", response);
    if (response.data.success) {
      orders.value = response.data.data;
    }
  }

  async function loadOrderBook(symbol, status=1, userId) {
    const response = await api.get('/api/orders', { params: { symbol:symbol, status: status, user_id: userId } });
    console.log("Order book response:", response);
    if (response.data.success) {
      orderBook.value = response.data.data;
    }
  }

  async function placeOrder(orderData) {
    try{
      isLoading.value = true
      error.value = '';
      fieldErrors.value = {};
      const response = await api.post('/api/orders', orderData);
      console.log("Place order response:", response);
      if (response.data.success) {
        console.log("Order placed successfully");
        resetOrder();
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
    order,
    resetOrder,
    loadAvailableAssets,
    loadOrders,
    loadOrderBook,
    placeOrder,
  };
});
