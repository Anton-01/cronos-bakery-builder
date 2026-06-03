export type GatewayType = 'mercadopago' | 'stripe' | 'openpay'

export interface GatewayOption {
  gateway: GatewayType
  label: string
  mode: 'sandbox' | 'production'
}

export interface PaymentCheckout {
  type: 'redirect' | 'client_secret'
  redirect_url?: string
  client_secret?: string
  publishable_key?: string
}

export interface Payment {
  id: string
  order_id: string
  gateway: GatewayType
  mode: string
  status: string
  status_label: string
  amount: number
  currency: string
  reference: string | null
  attempts: number
  checkout: PaymentCheckout | null
  paid_at: string | null
}
