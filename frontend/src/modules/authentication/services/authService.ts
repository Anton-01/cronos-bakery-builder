import { request } from '@/services/http'
import type {
  AuthSession,
  AuthUser,
  ChangePasswordPayload,
  LoginCredentials,
  RegisterPayload,
  ResetPasswordPayload,
  SocialProvider,
  UpdateProfilePayload,
} from '../types'

interface MessageResponse {
  message: string
}

interface Wrapped<T> {
  data: T
}

/**
 * Transport layer for the customer Authentication module. Components and stores
 * depend on this contract rather than on Axios directly.
 */
export const authService = {
  register(payload: RegisterPayload): Promise<AuthSession> {
    return request<AuthSession>({ url: '/auth/register', method: 'POST', data: payload })
  },

  login(credentials: LoginCredentials): Promise<AuthSession> {
    return request<AuthSession>({ url: '/auth/login', method: 'POST', data: credentials })
  },

  me(): Promise<AuthUser> {
    return request<Wrapped<AuthUser>>({ url: '/auth/me', method: 'GET' }).then((r) => r.data)
  },

  logout(): Promise<void> {
    return request<void>({ url: '/auth/logout', method: 'POST' })
  },

  forgotPassword(email: string): Promise<MessageResponse> {
    return request<MessageResponse>({ url: '/auth/password/forgot', method: 'POST', data: { email } })
  },

  resetPassword(payload: ResetPasswordPayload): Promise<MessageResponse> {
    return request<MessageResponse>({ url: '/auth/password/reset', method: 'POST', data: payload })
  },

  resendVerification(): Promise<MessageResponse> {
    return request<MessageResponse>({ url: '/auth/email/verification-notification', method: 'POST' })
  },

  // --- Profile -------------------------------------------------------------
  profile(): Promise<AuthUser> {
    return request<Wrapped<AuthUser>>({ url: '/auth/profile', method: 'GET' }).then((r) => r.data)
  },

  updateProfile(payload: UpdateProfilePayload): Promise<AuthUser> {
    return request<Wrapped<AuthUser>>({ url: '/auth/profile', method: 'PUT', data: payload }).then(
      (r) => r.data,
    )
  },

  changePassword(payload: ChangePasswordPayload): Promise<MessageResponse> {
    return request<MessageResponse>({ url: '/auth/profile/password', method: 'PUT', data: payload })
  },

  // --- Social login --------------------------------------------------------
  socialRedirectUrl(provider: SocialProvider): Promise<string> {
    return request<{ redirect_url: string }>({
      url: `/auth/social/${provider}/redirect`,
      method: 'GET',
    }).then((r) => r.redirect_url)
  },

  socialCallback(provider: SocialProvider, query: string): Promise<AuthSession> {
    return request<AuthSession>({
      url: `/auth/social/${provider}/callback${query}`,
      method: 'GET',
    })
  },
}
