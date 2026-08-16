<script setup lang="ts">
import { onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useFeatureFlagsStore } from '@/stores/featureFlags'
import { STATUS_LABELS } from '@/lib/statusFlow'
import type { RequestStatus } from '@/types'

const dashboard = useDashboardStore()
const featureFlags = useFeatureFlagsStore()

onMounted(() => {
  dashboard.fetch()
  featureFlags.fetchAll()
})

const statusOrder: RequestStatus[] = ['draft', 'open', 'in_progress', 'resolved', 'closed']
</script>

<template>
  <div>
    <h1>Dashboard</h1>
    <p class="muted">Administrator-only operational view.</p>

    <p v-if="dashboard.error" class="error">{{ dashboard.error }}</p>
    <p v-else-if="dashboard.loading" class="muted">Loading…</p>

    <template v-else-if="dashboard.data">
      <div class="card">
        <h2>Requests by status</h2>
        <div class="list-item" style="flex-wrap: wrap; gap: 1.5rem">
          <div v-for="status in statusOrder" :key="status">
            <div class="muted">{{ STATUS_LABELS[status] }}</div>
            <strong style="font-size: 1.5rem">{{ dashboard.data.requests_by_status[status] }}</strong>
          </div>
          <div>
            <div class="muted">Total</div>
            <strong style="font-size: 1.5rem">{{ dashboard.data.requests_by_status.total }}</strong>
          </div>
        </div>
      </div>

      <div class="card">
        <h2>Average resolution time</h2>
        <strong style="font-size: 1.5rem">
          {{ dashboard.data.avg_resolution_hours !== null ? `${dashboard.data.avg_resolution_hours}h` : '—' }}
        </strong>
      </div>

      <div class="card">
        <h2>Pending queue jobs</h2>
        <strong style="font-size: 1.5rem">{{ dashboard.data.pending_queue_jobs ?? '—' }}</strong>
      </div>

      <div class="card">
        <h2>Recent errors</h2>
        <p v-if="!dashboard.data.recent_errors.length" class="muted">No failed jobs recently. Good.</p>
        <ul v-else>
          <li v-for="(err, i) in dashboard.data.recent_errors" :key="i">
            <span class="muted">{{ err.failed_at }}</span> — {{ err.exception }}
          </li>
        </ul>
      </div>
    </template>

    <div class="card">
      <h2>Feature flags</h2>
      <p class="muted">Only affects your own organization.</p>
      <div v-for="flag in featureFlags.flags" :key="flag.key" class="list-item">
        <span>{{ flag.key }}</span>
        <button class="btn" @click="featureFlags.toggle(flag.key, !flag.active)">
          {{ flag.active ? 'Deactivate' : 'Activate' }}
        </button>
      </div>
    </div>
  </div>
</template>
