<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

async function handleSubmit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch {
    error.value = 'Invalid email or password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="card" style="max-width: 360px; margin: 3rem auto">
    <h1>Sign in</h1>
    <p class="muted">SaaS Platform Delivery Lab</p>

    <form @submit.prevent="handleSubmit">
      <div class="field">
        <label for="email">Email</label>
        <input id="email" v-model="email" type="email" required autocomplete="username" />
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          required
          autocomplete="current-password"
        />
      </div>
      <p v-if="error" class="error">{{ error }}</p>
      <button class="btn btn-primary" type="submit" :disabled="loading">
        {{ loading ? 'Signing in…' : 'Sign in' }}
      </button>
    </form>

    <p class="muted" style="margin-top: 1.5rem">
      Demo users (password: <code>password</code>):<br />
      admin@northwind.test · manager@northwind.test · member@northwind.test
    </p>
  </div>
</template>
