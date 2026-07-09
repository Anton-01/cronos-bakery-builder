export interface AvailableSlot {
  id: number
  label: string
  start_time: string
  end_time: string | null
  remaining: number
}

export interface AvailableDay {
  date: string
  weekday: number
  slots: AvailableSlot[]
}

export interface MinimumDate {
  date: string
  slot_id: number
  slot_label: string
  at: string
}

export interface Availability {
  product: string | null
  lead_time_hours: number
  minimum: MinimumDate | null
  days: AvailableDay[]
}

/** A chosen date + slot from the picker. */
export interface SlotSelection {
  date: string
  slot_id: number
  slot_label: string
  start_time: string
}
