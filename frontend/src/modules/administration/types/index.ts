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
}

export interface AdminSession {
  admin: Admin
  token: string
}
