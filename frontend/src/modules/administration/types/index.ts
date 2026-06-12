export interface Admin {
  id: number
  name: string
  email: string
  is_active: boolean
  roles: string[]
  permissions: string[]
}

export interface AdminCredentials {
  email: string
  password: string
  code?: string
}

export interface AdminSession {
  admin: Admin
  token: string
}

export interface ManagedUser {
  id: number
  name: string
  first_name: string
  last_name: string
  email: string
  phone: string | null
  avatar: string | null
  roles: string[]
  is_suspended: boolean
  suspended_at: string | null
  suspended_until: string | null
  suspension_reason: string | null
  email_verified: boolean
  created_at: string
}

export interface CreateUserPayload {
  first_name: string
  last_name: string
  email: string
  phone?: string
  password: string
  role: string
}

export interface UpdateUserPayload {
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
  role?: string
}

export interface SuspendUserPayload {
  reason: string
  suspended_until?: string
}

export interface ImpersonationResult {
  token: string
  user: ManagedUser
}
