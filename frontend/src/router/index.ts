import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DashboardPage from '@/pages/DashboardPage.vue'
import AgendaPage from '@/pages/AgendaPage.vue'
import FinancePage from '@/pages/FinancePage.vue'
import LoginPage from '@/pages/LoginPage.vue'
import NotificationsPage from '@/pages/NotificationsPage.vue'
import PatientsPage from '@/pages/PatientsPage.vue'
import ClinicsPage from '@/pages/SuperAdminClinicsPage.vue'
import SettingsPage from '@/pages/SettingsPage.vue'

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
      name: 'agenda',
      component: AgendaPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/financeiro',
      name: 'financeiro',
      component: FinancePage,
      meta: { requiresAuth: true },
    },
    {
      path: '/configuracoes',
      name: 'configuracoes',
      component: SettingsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/notificacoes',
      name: 'notificacoes',
      component: NotificationsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/super-admin/clinicas',
      name: 'super-admin-clinicas',
      component: ClinicsPage,
      meta: { requiresAuth: true, superAdmin: true },
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
    try {
      await auth.fetchMe()
    } catch {
      await auth.logout()

      return { name: 'login', query: { redirect: to.fullPath } }
    }
  }

  if (to.meta.superAdmin && auth.isAuthenticated && !auth.isSuperAdmin) {
    return { name: 'dashboard' }
  }
})

export default router
