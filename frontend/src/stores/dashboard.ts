import { ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/lib/api'
import type { DashboardData } from '@/types'

export const useDashboardStore = defineStore('dashboard', () => {
  const data = ref<DashboardData | null>(null)
  const loading = ref(false)
  const error = ref('')

  async function fetch() {
    loading.value = true
    error.value = ''
    try {
      const { data: response } = await api.get<DashboardData>('/api/dashboard')
      data.value = response
    } catch {
      error.value = 'Could not load the dashboard.'
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, fetch }
})
