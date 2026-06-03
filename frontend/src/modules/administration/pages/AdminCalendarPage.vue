<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import {
  adminPanelService,
  type CalendarSchedule,
  type DeliverySlot,
  type Holiday,
} from '../services/adminPanelService'

// ---------------------------------------------------------------------------
// Tabs
// ---------------------------------------------------------------------------
const activeTab = ref<'schedule' | 'slots' | 'holidays'>('schedule')

// ---------------------------------------------------------------------------
// Day names
// ---------------------------------------------------------------------------
const DAY_NAMES = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado']

// ---------------------------------------------------------------------------
// Horario Semanal
// ---------------------------------------------------------------------------
const schedule = ref<CalendarSchedule[]>([])
const scheduleLoading = ref(true)
const scheduleSaving = ref(false)

async function loadSchedule(): Promise<void> {
  scheduleLoading.value = true
  try {
    schedule.value = await adminPanelService.schedule()
  } finally {
    scheduleLoading.value = false
  }
}

async function saveSchedule(): Promise<void> {
  scheduleSaving.value = true
  try {
    schedule.value = await adminPanelService.updateSchedule(schedule.value)
  } finally {
    scheduleSaving.value = false
  }
}

// ---------------------------------------------------------------------------
// Slots de Entrega
// ---------------------------------------------------------------------------
const slots = ref<DeliverySlot[]>([])
const slotsLoading = ref(true)

const newSlot = reactive({ label: '', starts_at: '', ends_at: '', max_orders: 10 })

async function loadSlots(): Promise<void> {
  slotsLoading.value = true
  try {
    slots.value = await adminPanelService.deliverySlots()
  } finally {
    slotsLoading.value = false
  }
}

async function createSlot(): Promise<void> {
  if (!newSlot.label || !newSlot.starts_at || !newSlot.ends_at) return
  const created = await adminPanelService.createSlot({ ...newSlot })
  slots.value.push(created)
  newSlot.label = ''
  newSlot.starts_at = ''
  newSlot.ends_at = ''
  newSlot.max_orders = 10
}

async function deleteSlot(id: string): Promise<void> {
  await adminPanelService.deleteSlot(id)
  slots.value = slots.value.filter((s) => s.id !== id)
}

// ---------------------------------------------------------------------------
// Dias Feriados
// ---------------------------------------------------------------------------
const holidays = ref<Holiday[]>([])
const holidaysLoading = ref(true)

const newHoliday = reactive({ date: '', name: '' })

async function loadHolidays(): Promise<void> {
  holidaysLoading.value = true
  try {
    holidays.value = await adminPanelService.holidays()
  } finally {
    holidaysLoading.value = false
  }
}

async function createHoliday(): Promise<void> {
  if (!newHoliday.date || !newHoliday.name) return
  const created = await adminPanelService.createHoliday({ ...newHoliday })
  holidays.value.push(created)
  newHoliday.date = ''
  newHoliday.name = ''
}

async function deleteHoliday(id: string): Promise<void> {
  await adminPanelService.deleteHoliday(id)
  holidays.value = holidays.value.filter((h) => h.id !== id)
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------
onMounted(() => {
  loadSchedule()
  loadSlots()
  loadHolidays()
})
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Calendario</h1>
        <div class="admin-page-header__breadcrumb">Inicio / Calendario</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
      <button
        class="admin-tab"
        :class="{ 'admin-tab--active': activeTab === 'schedule' }"
        @click="activeTab = 'schedule'"
      >
        Horario Semanal
      </button>
      <button
        class="admin-tab"
        :class="{ 'admin-tab--active': activeTab === 'slots' }"
        @click="activeTab = 'slots'"
      >
        Slots de Entrega
      </button>
      <button
        class="admin-tab"
        :class="{ 'admin-tab--active': activeTab === 'holidays' }"
        @click="activeTab = 'holidays'"
      >
        Dias Feriados
      </button>
    </div>

    <!-- ------------------------------------------------------------------ -->
    <!-- Tab: Horario Semanal                                                -->
    <!-- ------------------------------------------------------------------ -->
    <div v-if="activeTab === 'schedule'" class="admin-content-card">
      <p v-if="scheduleLoading" style="text-align:center; padding: 2rem; color: var(--admin-text-muted);">
        Cargando horario...
      </p>

      <template v-else>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Dia</th>
              <th>Apertura</th>
              <th>Cierre</th>
              <th>Activo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in schedule" :key="entry.id">
              <td>{{ DAY_NAMES[entry.day_of_week] }}</td>
              <td>
                <input
                  v-model="entry.opens_at"
                  type="time"
                  style="border: 1px solid var(--admin-border); border-radius: 4px; padding: 0.25rem 0.4rem; font-size: 0.85rem;"
                />
              </td>
              <td>
                <input
                  v-model="entry.closes_at"
                  type="time"
                  style="border: 1px solid var(--admin-border); border-radius: 4px; padding: 0.25rem 0.4rem; font-size: 0.85rem;"
                />
              </td>
              <td>
                <span
                  class="admin-badge"
                  :class="entry.is_active ? 'admin-badge--success' : 'admin-badge--muted'"
                  style="cursor: pointer; user-select: none;"
                  @click="entry.is_active = !entry.is_active"
                >
                  {{ entry.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
            </tr>
            <tr v-if="schedule.length === 0">
              <td colspan="4">Sin datos.</td>
            </tr>
          </tbody>
        </table>

        <div style="margin-top: 1rem;">
          <button class="admin-btn admin-btn--primary" :disabled="scheduleSaving" @click="saveSchedule">
            {{ scheduleSaving ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </template>
    </div>

    <!-- ------------------------------------------------------------------ -->
    <!-- Tab: Slots de Entrega                                               -->
    <!-- ------------------------------------------------------------------ -->
    <div v-if="activeTab === 'slots'" class="admin-content-card">
      <!-- Add form -->
      <div class="admin-add-form">
        <label>
          Label
          <input v-model="newSlot.label" type="text" placeholder="Ej. Mañana" />
        </label>
        <label>
          Inicio
          <input v-model="newSlot.starts_at" type="time" />
        </label>
        <label>
          Fin
          <input v-model="newSlot.ends_at" type="time" />
        </label>
        <label>
          Max Pedidos
          <input v-model.number="newSlot.max_orders" type="number" min="1" style="width: 6rem;" />
        </label>
        <button class="admin-btn admin-btn--primary" @click="createSlot">Agregar</button>
      </div>

      <p v-if="slotsLoading" style="text-align:center; padding: 2rem; color: var(--admin-text-muted);">
        Cargando slots...
      </p>

      <table v-else class="admin-table">
        <thead>
          <tr>
            <th>Label</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Max Pedidos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="slot in slots" :key="slot.id">
            <td>{{ slot.label }}</td>
            <td>{{ slot.starts_at }}</td>
            <td>{{ slot.ends_at }}</td>
            <td>{{ slot.max_orders }}</td>
            <td>
              <button class="admin-btn admin-btn--sm" @click="deleteSlot(slot.id)">Eliminar</button>
            </td>
          </tr>
          <tr v-if="slots.length === 0">
            <td colspan="5">Sin slots registrados.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ------------------------------------------------------------------ -->
    <!-- Tab: Dias Feriados                                                  -->
    <!-- ------------------------------------------------------------------ -->
    <div v-if="activeTab === 'holidays'" class="admin-content-card">
      <!-- Add form -->
      <div class="admin-add-form">
        <label>
          Fecha
          <input v-model="newHoliday.date" type="date" />
        </label>
        <label>
          Nombre
          <input v-model="newHoliday.name" type="text" placeholder="Ej. Navidad" />
        </label>
        <button class="admin-btn admin-btn--primary" @click="createHoliday">Agregar</button>
      </div>

      <p v-if="holidaysLoading" style="text-align:center; padding: 2rem; color: var(--admin-text-muted);">
        Cargando feriados...
      </p>

      <table v-else class="admin-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Nombre</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="holiday in holidays" :key="holiday.id">
            <td>{{ holiday.date }}</td>
            <td>{{ holiday.name }}</td>
            <td>
              <button class="admin-btn admin-btn--sm" @click="deleteHoliday(holiday.id)">Eliminar</button>
            </td>
          </tr>
          <tr v-if="holidays.length === 0">
            <td colspan="3">Sin feriados registrados.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.admin-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.admin-tab {
  padding: 0.5rem 1rem;
  border: 1px solid var(--admin-border);
  border-radius: 6px;
  background: white;
  cursor: pointer;
  font-size: 0.875rem;
}

.admin-tab--active {
  background: var(--admin-primary);
  color: white;
  border-color: var(--admin-primary);
}

.admin-add-form {
  display: flex;
  gap: 0.75rem;
  align-items: end;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.admin-add-form label {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.8rem;
  font-weight: 500;
}

.admin-add-form input {
  padding: 0.4rem 0.6rem;
  border: 1px solid var(--admin-border);
  border-radius: 6px;
  font-size: 0.85rem;
}
</style>
