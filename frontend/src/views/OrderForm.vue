<template>
  <div class="flex flex-col min-h-dvh bg-red-100 border-4 border-solid border-red-300">
    <header class="w-full flex justify-between px-5 py-3 bg-purple-300">
      <span class="text-white font-bold text-3xl mx-2 my-2">
        LOEME
      </span>
      <div>
        <span class="text-2xl font-bold mr-2">{{ profileStore.profile.name }}</span>
        <button class="h-full text-white rounded-2xl px-5 bg-blue-700">
          Logout
        </button>
      </div>
    </header>
    <div class="grid grid-cols-2 gap-4 min-w-5/10 mx-auto">
      <div class="bg-red-200 col-span-2 border-dashed border border-red-500 pl-5">
        <h1 class="font-bold text-2xl">
          New Order
        </h1>
        <p v-show="ordersStore.error" class="text-red-700">{{ ordersStore.error }}</p>
        <form @submit.prevent="placeOrder" class="p-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="text-right">
              <label for="symbol">Symbol </label>
            </div>
            <div class="">
              <select v-model="order.symbol" id="symbol" class="w-2/5 outline-2 outline-red-500 ml-3 p-1 rounded" required>
                <option value="">-- Select --</option>
                <option v-if="order.side === 'buy'" v-for="symbol in ordersStore.availableAssets" :key="symbol" :value="symbol">{{ symbol }}</option>
                <option v-if="order.side === 'sell'" v-for="symbol in sellSymbols" :key="symbol" :value="symbol">{{ symbol }}</option>
              </select>
              <div v-if="ordersStore.fieldErrors.symbol" class="text-red-600 text-sm mt-1">
                {{ ordersStore.fieldErrors.symbol[0] }}
              </div>
            </div>

            <div class="text-right">
              <label for="side">Side </label>
            </div>

            <div class="">
              <select @change="sideChanged" v-model="order.side" id="side" class="w-2/5 outline-2 outline-red-500 ml-3 p-1 rounded" required>
                <option value="">-- Select --</option>
                <option value="buy">BUY</option>
                <option value="sell">SELL</option>
              </select>
              <div v-if="ordersStore.fieldErrors.side" class="text-red-600 text-sm mt-1">
                {{ ordersStore.fieldErrors.side[0] }}
              </div>
            </div>

            <div class="text-right">
              <label for="price">Price </label>
            </div>
            <div>
              <input v-model="order.price" min="0" type="number" id="price" placeholder="price" class="w-2/5 outline-2 outline-red-500 ml-3 p-1 rounded" required>
              <div v-if="ordersStore.fieldErrors.price" class="text-red-600 text-sm mt-1">
                {{ ordersStore.fieldErrors.price[0] }}
              </div>
            </div>

            <div class="text-right">
              <label for="amount">Amount </label>
            </div>
            <div>
              <input v-model="order.amount" min="0" type="number" id="amount" placeholder="amount" class="w-2/5 outline-2 outline-red-500 ml-3 p-1 rounded" required>
              <div v-if="ordersStore.fieldErrors.amount" class="text-red-600 text-sm mt-1">
                {{ ordersStore.fieldErrors.amount[0] }}
              </div>
            </div>

            <div class="col-span-2 text-center">
              <button type="submit" class="w-1/2 bg-blue-700 text-white rounded-2xl px-5 py-2 my-2 disabled:opacity-50" :disabled="ordersStore.isLoading">
                {{ ordersStore.isLoading ? 'Placing...' : 'Place Order' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, reactive, computed } from 'vue';
import { useProfileStore } from '@/stores/profile';
import { useOrdersStore } from '@/stores/orders';

const profileStore = useProfileStore();
const ordersStore = useOrdersStore();

onMounted(async () => {
  await profileStore.loadProfile();
  await ordersStore.loadAvailableAssets();
});

const sellSymbols = computed(() => profileStore.profile?.assets?.map(asset => asset.symbol) || []);


const order = reactive({
  side: 'buy',
  symbol: '',
  price: null,
  amount: null,
});

async function placeOrder() {
    console.log('Placing order:', order);
    await ordersStore.placeOrder(order);
    console.log(ordersStore.fieldErrors);
}

</script>