import { ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/lib/api'
import type { FeatureFlag } from '@/types'

export const useFeatureFlagsStore = defineStore('featureFlags', () => {
  const flags = ref<FeatureFlag[]>([])

  async function fetchAll() {
    const { data } = await api.get<FeatureFlag[]>('/api/feature-flags')
    flags.value = data
  }

  async function toggle(key: string, active: boolean) {
    await api.patch(`/api/feature-flags/${key}`, { active })
    const flag = flags.value.find((f) => f.key === key)
    if (flag) flag.active = active
  }

  return { flags, fetchAll, toggle }
})
