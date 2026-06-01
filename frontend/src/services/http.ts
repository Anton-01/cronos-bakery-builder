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

// Attach the bearer token (if present) to every outgoing request.
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
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
