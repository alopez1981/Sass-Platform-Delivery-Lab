import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/lib/api'
import type { AppNotification } from '@/types'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<AppNotification[]>([])

  const unreadCount = computed(() => notifications.value.filter((n) => !n.read_at).length)

  async function fetchAll() {
    const { data } = await api.get<AppNotification[]>('/api/notifications')
    notifications.value = data
  }

  async function markAsRead(id: number) {
    const { data } = await api.patch<AppNotification>(`/api/notifications/${id}/read`)
    const notification = notifications.value.find((n) => n.id === id)
    if (notification) notification.read_at = data.read_at
  }

  return { notifications, unreadCount, fetchAll, markAsRead }
})
