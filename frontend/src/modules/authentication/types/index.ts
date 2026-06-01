export interface AuthUser {
  id: number
  name: string
  email: string
  roles: string[]
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthSession {
  user: AuthUser
  token: string
}
