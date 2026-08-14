import { ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/lib/api'
import type { OperationalRequest, Paginated, RequestStatus } from '@/types'

export const useRequestsStore = defineStore('requests', () => {
  const requests = ref<OperationalRequest[]>([])
  const current = ref<OperationalRequest | null>(null)
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const { data } = await api.get<Paginated<OperationalRequest>>('/api/requests')
      requests.value = data.data
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: number) {
    loading.value = true
    try {
      const { data } = await api.get<OperationalRequest>(`/api/requests/${id}`)
      current.value = data
    } finally {
      loading.value = false
    }
  }

  async function create(payload: { title: string; description?: string }) {
    const { data } = await api.post<OperationalRequest>('/api/requests', payload)
    requests.value.unshift(data)
    return data
  }

  async function addComment(requestId: number, body: string) {
    const { data } = await api.post(`/api/requests/${requestId}/comments`, { body })
    current.value?.comments?.push(data)
    return data
  }

  async function changeStatus(requestId: number, status: RequestStatus) {
    const { data } = await api.patch<OperationalRequest>(`/api/requests/${requestId}/status`, {
      status,
    })
    if (current.value?.id === requestId) {
      current.value.status = data.status
    }
    const inList = requests.value.find((r) => r.id === requestId)
    if (inList) inList.status = data.status
    return data
  }

  return { requests, current, loading, fetchAll, fetchOne, create, addComment, changeStatus }
})
