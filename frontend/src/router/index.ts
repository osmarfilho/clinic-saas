import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DashboardPage from '@/pages/DashboardPage.vue'
import LoginPage from '@/pages/LoginPage.vue'
import PatientsPage from '@/pages/PatientsPage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: { guest: true },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/patients',
      name: 'patients',
      component: PatientsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/agenda',
      redirect: '/dashboard',
      meta: { requiresAuth: true },
    },
    {
      path: '/financeiro',
      redirect: '/dashboard',
      meta: { requiresAuth: true },
    },
    {
      path: '/configuracoes',
      redirect: '/dashboard',
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAuth && auth.isAuthenticated && !auth.user) {
    await auth.fetchMe().catch(() => auth.logout())
  }
})

export default router
