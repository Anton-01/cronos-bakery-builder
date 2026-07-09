export type OptionType =
  | 'select'
  | 'radio'
  | 'checkbox'
  | 'color'
  | 'image'
  | 'text'
  | 'textarea'

export type PriceModifierType = 'none' | 'add' | 'subtract' | 'set'
export type RuleOperator = 'equals' | 'not_equals' | 'in'
export type RuleAction = 'show' | 'hide'

export interface OptionValue {
  id: number
  label: string
  value: string
  price_modifier: { type: PriceModifierType; amount: number }
  metadata: Record<string, unknown> | null
  is_default: boolean
  position: number
}

export interface ConfigOption {
  id: number
  key: string
  label: string
  type: OptionType
  help_text: string | null
  is_required: boolean
  position: number
  config: Record<string, unknown> | null
  values: OptionValue[]
}

export interface OptionRule {
  id: number
  option_id: number
  depends_on_option_id: number
  operator: RuleOperator
  value: string
  action: RuleAction
  position: number
}

export interface ConfigurableProduct {
  id: number
  name: string
  slug: string
  description: string | null
  image: string | null
  base_price: { amount: number; currency: string }
  is_active: boolean
  options: ConfigOption[]
  rules: OptionRule[]
}

export interface PriceLine {
  option: string
  value: string
  label: string
  modifier: PriceModifierType
  amount: number
  delta: number
}

export interface Quote {
  product: string
  visible: string[]
  price: {
    base: number
    total: number
    currency: string
    items: PriceLine[]
  }
}

/** option key => selected value (string) or values (string[] for checkboxes). */
export type Selections = Record<string, string | string[]>
