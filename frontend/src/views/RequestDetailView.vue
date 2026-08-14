<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRequestsStore } from '@/stores/requests'
import { ALLOWED_TRANSITIONS, STATUS_LABELS } from '@/lib/statusFlow'
import type { RequestStatus } from '@/types'

const props = defineProps<{ id: number }>()

const store = useRequestsStore()
const commentBody = ref('')
const statusError = ref('')
const changingStatus = ref(false)

const nextStatuses = computed<RequestStatus[]>(() =>
  store.current ? ALLOWED_TRANSITIONS[store.current.status] : [],
)

onMounted(() => store.fetchOne(props.id))

async function submitComment() {
  if (!commentBody.value.trim()) return
  await store.addComment(props.id, commentBody.value)
  commentBody.value = ''
}

async function transitionTo(status: RequestStatus) {
  statusError.value = ''
  changingStatus.value = true
  try {
    await store.changeStatus(props.id, status)
    await store.fetchOne(props.id)
  } catch {
    statusError.value = 'You are not allowed to make that transition.'
  } finally {
    changingStatus.value = false
  }
}
</script>

<template>
  <div v-if="store.current">
    <RouterLink to="/" class="muted">&larr; Back to requests</RouterLink>

    <div class="card">
      <div class="app-header" style="border: none; margin-bottom: 0.5rem">
        <h1>{{ store.current.title }}</h1>
        <span :class="`badge badge-${store.current.status}`">
          {{ STATUS_LABELS[store.current.status] }}
        </span>
      </div>
      <p class="muted">
        Created by {{ store.current.creator.name }}
        <template v-if="store.current.assignee"> · assigned to {{ store.current.assignee.name }}</template>
      </p>
      <p v-if="store.current.description">{{ store.current.description }}</p>

      <div v-if="nextStatuses.length" style="margin-top: 1rem">
        <button
          v-for="status in nextStatuses"
          :key="status"
          class="btn"
          :disabled="changingStatus"
          @click="transitionTo(status)"
        >
          Move to {{ STATUS_LABELS[status] }}
        </button>
      </div>
      <p v-if="statusError" class="error">{{ statusError }}</p>
    </div>

    <div class="card">
      <h2>History</h2>
      <p v-if="!store.current.status_histories?.length" class="muted">No status changes yet.</p>
      <ul v-else>
        <li v-for="entry in store.current.status_histories" :key="entry.id">
          {{ entry.changed_by.name }} moved it from
          <strong>{{ entry.from_status ? STATUS_LABELS[entry.from_status] : '—' }}</strong>
          to <strong>{{ STATUS_LABELS[entry.to_status] }}</strong>
        </li>
      </ul>
    </div>

    <div class="card">
      <h2>Comments</h2>
      <div v-if="!store.current.comments?.length" class="muted">No comments yet.</div>
      <div v-else>
        <div v-for="comment in store.current.comments" :key="comment.id" class="comment">
          <strong>{{ comment.user.name }}</strong>
          <p>{{ comment.body }}</p>
        </div>
      </div>

      <form @submit.prevent="submitComment" style="margin-top: 1rem">
        <div class="field">
          <label for="comment">Add a comment</label>
          <textarea id="comment" v-model="commentBody" rows="3" required></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Comment</button>
      </form>
    </div>
  </div>
  <p v-else class="muted">Loading…</p>
</template>
