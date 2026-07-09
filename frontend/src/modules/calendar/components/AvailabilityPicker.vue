<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { calendarService } from '../services/calendarService'
import type { Availability, AvailableDay, SlotSelection } from '../types'

const props = defineProps<{ productSlug?: string }>()
const emit = defineEmits<{ select: [selection: SlotSelection] }>()

const availability = ref<Availability | null>(null)
const loading = ref(true)
const selectedDate = ref<string | null>(null)
const selectedSlot = ref<number | null>(null)

function pickDay(day: AvailableDay): void {
  selectedDate.value = day.date
  selectedSlot.value = null
}

function pickSlot(day: AvailableDay, slotId: number): void {
  const slot = day.slots.find((s) => s.id === slotId)
  if (!slot) return
  selectedSlot.value = slotId
  emit('select', {
    date: day.date,
    slot_id: slot.id,
    slot_label: slot.label,
    start_time: slot.start_time,
  })
}

function formatDate(date: string): string {
  return new Intl.DateTimeFormat('es-CR', { weekday: 'short', day: 'numeric', month: 'short' }).format(
    new Date(date + 'T00:00:00'),
  )
}

onMounted(async () => {
  try {
    availability.value = await calendarService.availability(props.productSlug)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="availability">
    <p v-if="loading" class="catalog__state">Calculando disponibilidad…</p>

    <template v-else-if="availability">
      <p v-if="availability.minimum" class="availability__min">
        Fecha mínima disponible:
        <strong>{{ formatDate(availability.minimum.date) }} ({{ availability.minimum.slot_label }})</strong>
        — producción {{ availability.lead_time_hours }}h
      </p>
      <p v-else class="catalog__state">No hay fechas disponibles por el momento.</p>

      <div class="availability__days">
        <button
          v-for="day in availability.days"
          :key="day.date"
          type="button"
          class="availability__day"
          :class="{ 'availability__day--active': selectedDate === day.date }"
          @click="pickDay(day)"
        >
          {{ formatDate(day.date) }}
        </button>
      </div>

      <div v-if="selectedDate" class="availability__slots">
        <template v-for="day in availability.days" :key="day.date">
          <template v-if="day.date === selectedDate">
            <button
              v-for="slot in day.slots"
              :key="slot.id"
              type="button"
              class="availability__slot"
              :class="{ 'availability__slot--active': selectedSlot === slot.id }"
              @click="pickSlot(day, slot.id)"
            >
              {{ slot.label }} <small>({{ slot.remaining }} disp.)</small>
            </button>
          </template>
        </template>
      </div>
    </template>
  </div>
</template>
