import axios, { type AxiosInstance, type AxiosRequestConfig, type AxiosError } from 'axios'

const baseURL = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api'

const http: AxiosInstance = axios.create({ baseURL,
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

/**
 * Doble sesión (§4): las peticiones a /admin/* viajan con `admin_token`;
 * el resto con `auth_token` del cliente. El mismo criterio decide qué
 * sesión se invalida cuando el API rechaza o no responde.
 */
export type SessionScope = 'admin' | 'customer'

function scopeForUrl(url: string | undefined): SessionScope {
  return (url ?? '').startsWith('/admin') ? 'admin' : 'customer'
}

function tokenKeyFor(scope: SessionScope): string {
  return scope === 'admin' ? 'admin_token' : 'auth_token'
}

http.interceptors.request.use((config) => {
  const token = localStorage.getItem(tokenKeyFor(scopeForUrl(config.url)))
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

/**
 * Sesión inválida detectada por el interceptor: 401 (token revocado/expirado),
 * 419 (CSRF expirado) o caída de red con una sesión activa. Cada entry point
 * registra el handler de SU scope y este delega en `forceLogout()` del store
 * correspondiente — la limpieza es 100% local y nunca depende del backend.
 */
export type SessionInvalidReason = 'unauthorized' | 'csrf-expired' | 'network'
type SessionInvalidHandler = (reason: SessionInvalidReason) => void

const sessionInvalidHandlers: Partial<Record<SessionScope, SessionInvalidHandler>> = {}

export function setSessionInvalidHandler(scope: SessionScope, handler: SessionInvalidHandler) {
  sessionInvalidHandlers[scope] = handler
}

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]>; sudo_required?: boolean }>) => {
    const status = error.response?.status
    const data = error.response?.data

    if (status === 422 && data?.errors && onValidationError) {
      onValidationError(data.errors)
    }

    const scope = scopeForUrl(error.config?.url)
    const hadSession = localStorage.getItem(tokenKeyFor(scope)) !== null

    // Errores de red: sin respuesta del servidor (backend caído, DNS, CORS).
    // Se excluyen las cancelaciones deliberadas (AbortController).
    const isNetworkError = !error.response && error.code !== 'ERR_CANCELED'

    const reason: SessionInvalidReason | null =
      status === 401 ? 'unauthorized'
      : status === 419 ? 'csrf-expired'
      : isNetworkError ? 'network'
      : null

    // Solo se invalida si EXISTÍA una sesión para ese scope: un 401 de un
    // intento de login fallido o la navegación anónima jamás redirigen.
    if (reason !== null && hadSession) {
      if (reason !== 'network') {
        // Token rechazado por el backend: se purga aunque no haya handler.
        localStorage.removeItem(tokenKeyFor(scope))
      }
      sessionInvalidHandlers[scope]?.(reason)
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
