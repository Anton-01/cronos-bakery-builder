import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'

/**
 * Centralised Axios instance used by every module service.
 *
 * Base URL is driven by `VITE_API_URL` so the same build can target local,
 * staging and production APIs without code changes.
 */
const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Attach the relevant bearer token to every outgoing request. Admin endpoints
// use an independent token so the two sessions never collide.
http.interceptors.request.use((config) => {
  const isAdminRoute = (config.url ?? '').startsWith('/admin')
  const token = localStorage.getItem(isAdminRoute ? 'admin_token' : 'auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Surface auth failures globally so the UI can react (e.g. redirect to login).
http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
    }
    return Promise.reject(error)
  },
)

export function request<T>(config: AxiosRequestConfig): Promise<T> {
  return http.request<T>(config).then((res) => res.data)
}

export default http
