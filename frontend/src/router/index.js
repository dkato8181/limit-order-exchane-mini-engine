import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import Dashboard from '@/views/Dashboard.vue'
import OrderForm from '@/views/OrderForm.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/dashboard'
    },
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: Register,
      meta: { guest: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: Dashboard,
      meta: { requiresAuth: true }
    },
    {
      path: '/order-form',
      name: 'orderform',
      component: OrderForm,
      meta: { requiresAuth: true }
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated();

  console.log("Navigating to:", to.path, "Auth:", isAuthenticated)

  if ((to.meta.guest || to.path === '/login' || to.path === '/register') && isAuthenticated) {
    return next('/dashboard')
  }

  if (to.meta.requiresAuth && !isAuthenticated) {
    return next('/login')
  }

  if (to.path === '/logout') {
    await authStore.logout();
    return next('/login');
  }

  next()
})

export default router