import { describe, expect, it } from 'vitest'
import { ALLOWED_TRANSITIONS, STATUS_LABELS } from '../statusFlow'
import type { RequestStatus } from '@/types'

describe('statusFlow', () => {
  it('has a label for every status referenced in the transition map', () => {
    const statuses = Object.keys(ALLOWED_TRANSITIONS) as RequestStatus[]

    for (const status of statuses) {
      expect(STATUS_LABELS[status]).toBeDefined()
      for (const next of ALLOWED_TRANSITIONS[status]) {
        expect(STATUS_LABELS[next]).toBeDefined()
      }
    }
  })

  it('treats "closed" as a terminal state', () => {
    expect(ALLOWED_TRANSITIONS.closed).toEqual([])
  })

  it('only allows a draft request to move to open', () => {
    expect(ALLOWED_TRANSITIONS.draft).toEqual(['open'])
  })
})
