import type { ConfigurableProduct, OptionRule, Selections } from './types'

/** Normalise a selections map to `key => string[]`. */
function asArray(value: string | string[] | undefined): string[] {
  if (value === undefined || value === '') return []
  return Array.isArray(value) ? value : [value]
}

function operatorMatches(rule: OptionRule, selected: string[]): boolean {
  switch (rule.operator) {
    case 'equals':
      return selected.includes(rule.value)
    case 'not_equals':
      return !selected.includes(rule.value)
    case 'in':
      return rule.value
        .split(',')
        .map((v) => v.trim())
        .some((v) => selected.includes(v))
    default:
      return false
  }
}

/**
 * Mirror of the backend DependencyResolver: returns the set of visible option
 * keys for the current selections, resolved to a fixed point so chained
 * dependencies settle. Hidden options contribute no selection.
 */
export function resolveVisibleKeys(
  product: ConfigurableProduct,
  selections: Selections,
): string[] {
  const keyById = new Map(product.options.map((o) => [o.id, o.key]))
  const visible: Record<string, boolean> = {}
  product.options.forEach((o) => (visible[o.key] = true))

  const matches = (rule: OptionRule): boolean => {
    const sourceKey = keyById.get(rule.depends_on_option_id)
    if (!sourceKey || !visible[sourceKey]) return false
    return operatorMatches(rule, asArray(selections[sourceKey]))
  }

  const maxPasses = product.options.length + 1
  for (let pass = 0; pass <= maxPasses; pass++) {
    let changed = false

    for (const option of product.options) {
      const rules = product.rules.filter((r) => r.option_id === option.id)
      if (rules.length === 0) continue

      const showRules = rules.filter((r) => r.action === 'show')
      const hideRules = rules.filter((r) => r.action === 'hide')

      const showSatisfied = showRules.length === 0 || showRules.some(matches)
      const hidden = hideRules.some(matches)
      const next = showSatisfied && !hidden

      if (visible[option.key] !== next) {
        visible[option.key] = next
        changed = true
      }
    }

    if (!changed) break
  }

  return product.options.filter((o) => visible[o.key]).map((o) => o.key)
}
