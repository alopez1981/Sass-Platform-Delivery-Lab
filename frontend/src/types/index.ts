export type UserRole = 'administrator' | 'manager' | 'member'

export type RequestStatus = 'draft' | 'open' | 'in_progress' | 'resolved' | 'closed'

export interface Organization {
  id: number
  name: string
}

export interface AuthUser {
  id: number
  name: string
  email: string
  role: UserRole
  organization: Organization
}

export interface UserRef {
  id: number
  name: string
}

export interface Comment {
  id: number
  body: string
  user: UserRef
  created_at: string
}

export interface StatusHistoryEntry {
  id: number
  from_status: RequestStatus | null
  to_status: RequestStatus
  changed_by: UserRef
  created_at: string
}

export interface OperationalRequest {
  id: number
  title: string
  description: string | null
  status: RequestStatus
  creator: UserRef
  assignee: UserRef | null
  comments?: Comment[]
  status_histories?: StatusHistoryEntry[]
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export interface AppNotification {
  id: number
  type: string
  data: { request_id: number; title: string }
  read_at: string | null
  created_at: string
}
