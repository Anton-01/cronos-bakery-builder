import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'

const baseURL = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api'

const http: AxiosInstance = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

http.interceptors.request.use((config) => {
  const isAdminRoute = (config.url ?? '').startsWith('/admin')
  const token = localStorage.getItem(isAdminRoute ? 'admin_token' : 'auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
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
