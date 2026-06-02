import { request } from '@/services/http'
import type { GatewayOption, GatewayType, Payment, PaymentCheckout } from '../types'

interface Wrapped<T> {
  data: T
}

interface InitiateResponse {
  data: Payment
  checkout: PaymentCheckout
}

export const paymentService = {
  gateways(): Promise<GatewayOption[]> {
    return request<Wrapped<GatewayOption[]>>({ url: '/payments/gateways', method: 'GET' }).then(
      (r) => r.data,
    )
  },

  initiate(orderId: string, gateway: GatewayType): Promise<InitiateResponse> {
    return request<InitiateResponse>({
      url: '/payments/initiate',
      method: 'POST',
      data: { order_id: orderId, gateway },
    })
  },

  payment(id: string): Promise<Payment> {
    return request<Wrapped<Payment>>({ url: `/payments/${id}`, method: 'GET' }).then((r) => r.data)
  },
}
