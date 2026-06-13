import axios, { type AxiosInstance, type AxiosRequestConfig, type AxiosError } from 'axios'

const baseURL = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api'

const http: AxiosInstance = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let sudoPassword: string | null = null

export function setSudoPassword(password: string) {
  sudoPassword = password
}

export function clearSudoPassword() {
  sudoPassword = null
}

http.interceptors.request.use((config) => {
  const isAdminRoute = (config.url ?? '').startsWith('/admin')
  const token = localStorage.getItem(isAdminRoute ? 'admin_token' : 'auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  if (sudoPassword) {
    config.headers['X-Sudo-Password'] = sudoPassword
  }

  return config
})

type ValidationErrorCallback = (errors: Record<string, string[]>) => void
let onValidationError: ValidationErrorCallback | null = null

export function setValidationErrorHandler(handler: ValidationErrorCallback) {
  onValidationError = handler
}

type AuthErrorCallback = (status: number) => void
let onAuthError: AuthErrorCallback | null = null

export function setAuthErrorHandler(handler: AuthErrorCallback) {
  onAuthError = handler
}

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]>; sudo_required?: boolean }>) => {
    const status = error.response?.status
    const data = error.response?.data

    if (status === 422 && data?.errors && onValidationError) {
      onValidationError(data.errors)
    }

    if (status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('admin_token')
      onAuthError?.(401)
    }

    if (status === 403) {
      onAuthError?.(403)
    }

    return Promise.reject(error)
  },
)

let csrfReady: Promise<void> | null = null

function ensureCsrf(): Promise<void> {
  if (csrfReady) return csrfReady

  const cookieOrigin = baseURL.replace(/\/api\/?$/, '')
  csrfReady = axios
    .get(`${cookieOrigin}/sanctum/csrf-cookie`, { withCredentials: true })
    .then(() => {})
    .catch(() => {
      csrfReady = null
    })
  return csrfReady
}

export async function request<T>(config: AxiosRequestConfig): Promise<T> {
  const method = (config.method ?? 'GET').toUpperCase()
  if (method !== 'GET' && method !== 'HEAD') {
    await ensureCsrf()
  }
  return http.request<T>(config).then((res) => res.data)
}

export default http
