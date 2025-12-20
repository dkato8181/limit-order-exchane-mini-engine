<template>
  <div class="flex flex-col min-h-dvh bg-red-100 border-4 border-solid border-red-300">
    <header class="w-full flex justify-between px-5 py-3 bg-purple-300">
      <span class="text-white font-bold text-3xl mx-2 my-2">
        LOEME
      </span>
      <button class="text-white rounded-2xl px-5 bg-blue-800">
        Logout
      </button>
    </header>
    <div class="grid grid-cols-2 gap-4 w-8/10 mx-auto">
      <div class="bg-red-300 border-2 border-solid border-red-500 p-10 text-2xl text-green-600 text-center">
        Assets: {{ profileStore.profile.assets?.length }}
      </div>
      <div class="bg-red-300 border-2 border-solid border-red-500 p-10 text-2xl text-green-600 text-center">
        Balance: {{ profileStore.profile.balance }}
      </div>
      <div class="bg-red-200 col-span-2 border-dashed border border-red-500 pl-5">
        <h1 class="font-bold text-2xl">
          My Assets
        </h1>
        <ol>
          <li v-for="asset in profileStore.profile.assets" :key="asset.id">{{ asset.symbol }}: {{ asset.amount  }}</li>
        </ol>
      </div>
      <div class="bg-red-200 col-span-2 border-dashed border border-red-500 pl-5">
        <h1 class="font-bold text-2xl">
          Orders
        </h1>
        <ol>
          <li v-for="order in ordersStore.orders" :key="order.id">{{ order.symbol}} | {{ order.side }} | Amount: {{ order.amount }} | Price: {{ order.price }}</li>
        </ol>
      </div>
      <div class="bg-red-200 col-span-2 border-dashed border border-red-500 pl-5">
        <h1 class="font-bold text-2xl">
          Order Book
        </h1>
        <ol>
          <li>A</li>
          <li>B</li>
          <li>C</li>
        </ol>
      </div>
    </div>
  </div>
</template>
<script setup>
import { onMounted } from 'vue';
import { useProfileStore } from '@/stores/profile';
import { useOrdersStore } from '@/stores/orders';

const profileStore = useProfileStore();
const ordersStore = useOrdersStore();
onMounted(async () => {
  await profileStore.loadProfile();
  await ordersStore.loadOrders();
});

</script>