import { request } from '@/services/http'
import type { Availability } from '../types'

interface Wrapped<T> {
  data: T
}

/**
 * Public availability lookup for the scheduling engine.
 */
export const calendarService = {
  availability(productSlug?: string, days = 30): Promise<Availability> {
    const params: Record<string, unknown> = { days }
    if (productSlug) params.product = productSlug
    return request<Wrapped<Availability>>({
      url: '/calendar/availability',
      method: 'GET',
      params,
    }).then((r) => r.data)
  },
}
