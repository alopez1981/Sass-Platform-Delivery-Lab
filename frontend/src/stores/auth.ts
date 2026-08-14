import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api, ensureCsrfCookie } from '@/lib/api'
import type { AuthUser } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const initialized = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  async function login(email: string, password: string) {
    await ensureCsrfCookie()
    const { data } = await api.post('/api/login', { email, password })
    user.value = data.user
  }

  async function logout() {
    await api.post('/api/logout')
    user.value = null
  }

  /** Restores the session on page load/refresh by asking the API who we are. */
  async function fetchCurrentUser() {
    try {
      const { data } = await api.get('/api/me')
      user.value = data.user
    } catch {
      user.value = null
    } finally {
      initialized.value = true
    }
  }

  return { user, initialized, isAuthenticated, login, logout, fetchCurrentUser }
})
