<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useRequestsStore } from '@/stores/requests'

const title = ref('')
const description = ref('')
const error = ref('')
const submitting = ref(false)

const store = useRequestsStore()
const router = useRouter()

async function handleSubmit() {
  error.value = ''
  submitting.value = true
  try {
    const request = await store.create({ title: title.value, description: description.value })
    router.push(`/requests/${request.id}`)
  } catch {
    error.value = 'Could not create the request. Check the fields and try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="card" style="max-width: 480px">
    <h1>New request</h1>
    <form @submit.prevent="handleSubmit">
      <div class="field">
        <label for="title">Title</label>
        <input id="title" v-model="title" required maxlength="255" />
      </div>
      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" v-model="description" rows="4"></textarea>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
      <button class="btn btn-primary" type="submit" :disabled="submitting">
        {{ submitting ? 'Creating…' : 'Create request' }}
      </button>
    </form>
    <p class="muted" style="margin-top: 1rem">
      New requests start as <span class="badge badge-draft">draft</span>. You (or a manager) move
      them through the workflow from the request page.
    </p>
  </div>
</template>
