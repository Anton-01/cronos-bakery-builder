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
  id: string
  label: string
  value: string
  price_modifier: { type: PriceModifierType; amount: number }
  metadata: Record<string, unknown> | null
  is_default: boolean
  position: number
}

export interface ConfigOption {
  id: string
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
  id: string
  option_id: string
  depends_on_option_id: string
  operator: RuleOperator
  value: string
  action: RuleAction
  position: number
}

export interface ConfigurableProduct {
  id: string
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
