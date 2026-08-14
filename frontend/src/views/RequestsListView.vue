<script setup lang="ts">
import { onMounted } from 'vue'
import { useRequestsStore } from '@/stores/requests'

const store = useRequestsStore()

onMounted(() => store.fetchAll())
</script>

<template>
  <div>
    <div class="app-header" style="border: none; margin-bottom: 1rem">
      <h1>Requests</h1>
      <RouterLink class="btn btn-primary" to="/requests/new">New request</RouterLink>
    </div>

    <p v-if="store.loading" class="muted">Loading…</p>
    <p v-else-if="store.requests.length === 0" class="muted">No requests yet.</p>

    <div class="list" v-else>
      <RouterLink
        v-for="request in store.requests"
        :key="request.id"
        :to="`/requests/${request.id}`"
        class="card list-item"
      >
        <div>
          <div class="list-item-title">{{ request.title }}</div>
          <div class="muted">
            by {{ request.creator.name }}
            <template v-if="request.assignee"> · assigned to {{ request.assignee.name }}</template>
          </div>
        </div>
        <span :class="`badge badge-${request.status}`">{{ request.status.replace('_', ' ') }}</span>
      </RouterLink>
    </div>
  </div>
</template>
