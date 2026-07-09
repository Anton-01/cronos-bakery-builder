export interface Money {
  amount: number
  currency: string
}

export interface CartItem {
  id: string
  product_id: number
  product_name: string
  product_slug: string
  configuration: { selections: Record<string, string | string[]>; price: { items: unknown[] } }
  unit_price: Money
  quantity: number
  line_total: Money
}

export interface Cart {
  id: string
  items: CartItem[]
  item_count: number
  summary: { subtotal: Money; total: Money }
}

export type AddressLabel = 'home' | 'work' | 'other'

export interface Address {
  id: string
  label: AddressLabel
  label_text: string
  recipient_name: string
  phone: string | null
  line1: string
  line2: string | null
  city: string
  state: string | null
  country: string
  notes: string | null
  is_default: boolean
}

export interface Branch {
  id: string
  name: string
  address: string | null
  phone: string | null
}

export interface OrderItem {
  id: string
  product_name: string
  product_slug: string
  configuration: Record<string, unknown>
  unit_price: number
  quantity: number
  line_total: number
}

export interface Order {
  id: string
  number: string
  status: string
  status_label: string
  fulfillment: {
    type: 'delivery' | 'pickup'
    type_label: string
    shipping_address: Record<string, unknown> | null
    branch: { data?: Branch } | Branch | null
    pickup_date: string | null
    pickup_time: string | null
  }
  items: OrderItem[]
  totals: { subtotal: number; total: number; currency: string }
  notes: string | null
  placed_at: string | null
}

export interface CheckoutPayload {
  fulfillment_type: 'delivery' | 'pickup'
  address_id?: string
  branch_id?: string
  pickup_date?: string
  pickup_time?: string
  notes?: string
}
