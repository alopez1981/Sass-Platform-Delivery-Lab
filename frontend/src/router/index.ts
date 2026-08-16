import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/',
      name: 'requests',
      component: () => import('@/views/RequestsListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/requests/new',
      name: 'requests.new',
      component: () => import('@/views/NewRequestView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/requests/:id',
      name: 'requests.show',
      component: () => import('@/views/RequestDetailView.vue'),
      meta: { requiresAuth: true },
      props: (route) => ({ id: Number(route.params.id) }),
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { requiresAuth: true, requiresAdministrator: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.fetchCurrentUser()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'requests' }
  }

  // A UX nicety only — the API enforces this regardless (see
  // DashboardController), so there is no security relevance here.
  if (to.meta.requiresAdministrator && auth.user?.role !== 'administrator') {
    return { name: 'requests' }
  }
})

export default router
