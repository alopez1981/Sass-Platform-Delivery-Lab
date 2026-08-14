import type { RequestStatus } from '@/types'

export const STATUS_LABELS: Record<RequestStatus, string> = {
  draft: 'Draft',
  open: 'Open',
  in_progress: 'In progress',
  resolved: 'Resolved',
  closed: 'Closed',
}

/**
 * Mirrors the transition rules in the backend (App\Enums\RequestStatus).
 * This copy only drives which buttons are shown — the backend is the
 * actual authority and re-validates every transition server-side.
 */
export const ALLOWED_TRANSITIONS: Record<RequestStatus, RequestStatus[]> = {
  draft: ['open'],
  open: ['in_progress', 'closed'],
  in_progress: ['resolved', 'open'],
  resolved: ['closed', 'in_progress'],
  closed: [],
}
