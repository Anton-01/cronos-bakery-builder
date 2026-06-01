export interface AuthUser {
  id: number
  first_name: string
  last_name: string
  name: string
  email: string
  phone: string | null
  avatar: string | null
  email_verified: boolean
  roles: string[]
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterPayload {
  first_name: string
  last_name: string
  email: string
  phone?: string
  password: string
  password_confirmation: string
}

export interface ResetPasswordPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface UpdateProfilePayload {
  first_name: string
  last_name: string
  phone?: string
}

export interface ChangePasswordPayload {
  current_password: string
  password: string
  password_confirmation: string
}

export interface AuthSession {
  user: AuthUser
  token: string
}

export type SocialProvider = 'google' | 'facebook' | 'apple'
