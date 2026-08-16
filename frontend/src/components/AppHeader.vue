<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const router = useRouter()

onMounted(() => {
  if (auth.isAuthenticated) notifications.fetchAll()
})

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="app-header" v-if="auth.isAuthenticated && auth.user">
    <div>
      <strong>{{ auth.user.organization.name }}</strong>
      <span class="muted"> — {{ auth.user.name }} ({{ auth.user.role }})</span>
    </div>
    <nav>
      <RouterLink to="/">Requests</RouterLink>
      <RouterLink v-if="auth.user.role === 'administrator'" to="/dashboard">Dashboard</RouterLink>
      <span title="Unread notifications">🔔 {{ notifications.unreadCount }}</span>
      <button class="btn" @click="handleLogout">Log out</button>
    </nav>
  </header>
</template>
