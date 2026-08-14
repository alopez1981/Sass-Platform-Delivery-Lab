import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../auth'
import { api, ensureCsrfCookie } from '@/lib/api'

vi.mock('@/lib/api', () => ({
  api: { post: vi.fn<(...args: unknown[]) => unknown>(), get: vi.fn<(...args: unknown[]) => unknown>() },
  ensureCsrfCookie: vi.fn<(...args: unknown[]) => unknown>(),
}))

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts unauthenticated', () => {
    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(false)
  })

  it('stores the user after a successful login', async () => {
    const fakeUser = { id: 1, name: 'Ada', email: 'ada@example.com', role: 'administrator' }
    vi.mocked(ensureCsrfCookie).mockResolvedValue({} as never)
    vi.mocked(api.post).mockResolvedValue({ data: { user: fakeUser } } as never)

    const auth = useAuthStore()
    await auth.login('ada@example.com', 'secret')

    expect(ensureCsrfCookie).toHaveBeenCalled()
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.user?.email).toBe('ada@example.com')
  })

  it('clears the user on logout', async () => {
    const auth = useAuthStore()
    auth.user = { id: 1, name: 'Ada', email: 'ada@example.com', role: 'administrator' } as never
    vi.mocked(api.post).mockResolvedValue({} as never)

    await auth.logout()

    expect(auth.isAuthenticated).toBe(false)
  })
})
