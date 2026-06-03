<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'

import { useToast } from '@/composables/useToast'
import {
  adminPanelService,
  type AdminOrder,
  type CalendarSchedule,
  type DeliverySlot,
  type Holiday,
} from '../services/adminPanelService'

const { success, error } = useToast()

const activeTab = ref<'calendar' | 'schedule' | 'slots' | 'holidays'>('calendar')

const DAY_NAMES = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado']
const DAY_NAMES_SHORT = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab']
const MONTH_NAMES = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
]

// ── Calendar View ──────────────────────────────────────────────────────────
const calendarLoading = ref(true)
const orders = ref<AdminOrder[]>([])
const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())
const selectedDayOrders = ref<AdminOrder[]>([])
const selectedDate = ref<string | null>(null)
const selectedOrder = ref<AdminOrder | null>(null)

const calendarLabel = computed(() => `${MONTH_NAMES[currentMonth.value]} ${currentYear.value}`)

interface CalendarDay {
  date: Date
  day: number
  inMonth: boolean
  isToday: boolean
  isHoliday: boolean
  holidayName: string | null
  dateKey: string
  orders: AdminOrder[]
}

const calendarDays = computed<CalendarDay[]>(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDow = firstDay.getDay()

  const today = new Date()
  const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  const holidayMap = new Map<string, string>()
  for (const h of holidays.value) {
    holidayMap.set(h.date, h.name)
  }

  const orderMap = new Map<string, AdminOrder[]>()
  for (const o of orders.value) {
    const d = o.fulfillment?.pickup_date
    if (d) {
      if (!orderMap.has(d)) orderMap.set(d, [])
      orderMap.get(d)!.push(o)
    }
  }

  const days: CalendarDay[] = []

  // Previous month padding
  const prevLast = new Date(year, month, 0)
  for (let i = startDow - 1; i >= 0; i--) {
    const d = prevLast.getDate() - i
    const date = new Date(year, month - 1, d)
    const dk = dateKey(date)
    days.push({
      date, day: d, inMonth: false, isToday: false,
      isHoliday: holidayMap.has(dk), holidayName: holidayMap.get(dk) ?? null,
      dateKey: dk, orders: orderMap.get(dk) ?? [],
    })
  }

  // Current month
  for (let d = 1; d <= lastDay.getDate(); d++) {
    const date = new Date(year, month, d)
    const dk = dateKey(date)
    days.push({
      date, day: d, inMonth: true, isToday: dk === todayKey,
      isHoliday: holidayMap.has(dk), holidayName: holidayMap.get(dk) ?? null,
      dateKey: dk, orders: orderMap.get(dk) ?? [],
    })
  }

  // Next month padding
  const remaining = 7 - (days.length % 7)
  if (remaining < 7) {
    for (let d = 1; d <= remaining; d++) {
      const date = new Date(year, month + 1, d)
      const dk = dateKey(date)
      days.push({
        date, day: d, inMonth: false, isToday: false,
        isHoliday: holidayMap.has(dk), holidayName: holidayMap.get(dk) ?? null,
        dateKey: dk, orders: orderMap.get(dk) ?? [],
      })
    }
  }

  return days
})

function dateKey(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function prevMonth(): void {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth(): void {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

function goToday(): void {
  const now = new Date()
  currentMonth.value = now.getMonth()
  currentYear.value = now.getFullYear()
}

function openDayDetail(day: CalendarDay): void {
  if (day.orders.length === 0) return
  selectedDayOrders.value = day.orders
  selectedDate.value = day.dateKey
  selectedOrder.value = null
}

function openOrderDetail(order: AdminOrder): void {
  selectedOrder.value = order
}

function closeModal(): void {
  if (selectedOrder.value) {
    selectedOrder.value = null
  } else {
    selectedDayOrders.value = []
    selectedDate.value = null
  }
}

function money(amount: number, currency = 'CRC'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'long' }).format(new Date(dateStr))
}

function statusBadgeClass(status: string): string {
  if (status === 'pending') return 'admin-badge--warning'
  if (status === 'confirmed' || status === 'in_production') return 'admin-badge--info'
  if (status === 'ready' || status === 'completed') return 'admin-badge--success'
  if (status === 'cancelled') return 'admin-badge--error'
  return 'admin-badge--default'
}

function statusDotColor(status: string): string {
  if (status === 'pending') return 'var(--admin-warning)'
  if (status === 'confirmed' || status === 'in_production') return 'var(--admin-info)'
  if (status === 'ready' || status === 'completed') return 'var(--admin-success)'
  if (status === 'cancelled') return 'var(--admin-error)'
  return 'var(--admin-text-muted)'
}

async function loadOrders(): Promise<void> {
  calendarLoading.value = true
  try {
    const resp = await adminPanelService.adminOrders()
    orders.value = resp.data
  } finally {
    calendarLoading.value = false
  }
}

// ── Horario Semanal ────────────────────────────────────────────────────────
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
    success('Horario guardado exitosamente')
  } catch {
    error('Error al guardar el horario')
  } finally {
    scheduleSaving.value = false
  }
}

// ── Slots de Entrega ───────────────────────────────────────────────────────
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
  try {
    const created = await adminPanelService.createSlot({ ...newSlot })
    slots.value.push(created)
    newSlot.label = ''
    newSlot.starts_at = ''
    newSlot.ends_at = ''
    newSlot.max_orders = 10
    success('Slot de entrega creado')
  } catch {
    error('Error al crear el slot')
  }
}

async function deleteSlot(id: string): Promise<void> {
  try {
    await adminPanelService.deleteSlot(id)
    slots.value = slots.value.filter((s) => s.id !== id)
    success('Slot eliminado')
  } catch {
    error('Error al eliminar el slot')
  }
}

// ── Dias Feriados ──────────────────────────────────────────────────────────
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
  try {
    const created = await adminPanelService.createHoliday({ ...newHoliday })
    holidays.value.push(created)
    newHoliday.date = ''
    newHoliday.name = ''
    success('Feriado agregado')
  } catch {
    error('Error al agregar el feriado')
  }
}

async function deleteHoliday(id: string): Promise<void> {
  try {
    await adminPanelService.deleteHoliday(id)
    holidays.value = holidays.value.filter((h) => h.id !== id)
    success('Feriado eliminado')
  } catch {
    error('Error al eliminar el feriado')
  }
}

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(() => {
  void loadOrders()
  void loadSchedule()
  void loadSlots()
  void loadHolidays()
})
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Calendario</h1>
        <div class="admin-page-header__breadcrumb">Inicio / Calendario</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="cal-tabs">
      <button class="cal-tab" :class="{ 'cal-tab--active': activeTab === 'calendar' }" @click="activeTab = 'calendar'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Vista Calendario
      </button>
      <button class="cal-tab" :class="{ 'cal-tab--active': activeTab === 'schedule' }" @click="activeTab = 'schedule'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Horario
      </button>
      <button class="cal-tab" :class="{ 'cal-tab--active': activeTab === 'slots' }" @click="activeTab = 'slots'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
        Slots
      </button>
      <button class="cal-tab" :class="{ 'cal-tab--active': activeTab === 'holidays' }" @click="activeTab = 'holidays'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Feriados
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- CALENDAR VIEW                                                      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <template v-if="activeTab === 'calendar'">
      <p v-if="calendarLoading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
        Cargando calendario...
      </p>

      <template v-else>
        <!-- Navigation bar -->
        <div class="cal-nav">
          <div class="cal-nav__left">
            <button class="cal-nav__btn" @click="prevMonth">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="cal-nav__btn" @click="nextMonth">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <h2 class="cal-nav__title">{{ calendarLabel }}</h2>
          </div>
          <button class="admin-btn admin-btn--sm admin-btn--outline" @click="goToday">Hoy</button>
        </div>

        <!-- Calendar grid -->
        <div class="cal-grid">
          <!-- Day of week headers -->
          <div v-for="d in DAY_NAMES_SHORT" :key="d" class="cal-grid__dow">{{ d }}</div>

          <!-- Day cells -->
          <div
            v-for="(day, idx) in calendarDays"
            :key="idx"
            class="cal-cell"
            :class="{
              'cal-cell--outside': !day.inMonth,
              'cal-cell--today': day.isToday,
              'cal-cell--holiday': day.isHoliday,
              'cal-cell--has-orders': day.orders.length > 0,
            }"
            @click="openDayDetail(day)"
          >
            <div class="cal-cell__header">
              <span class="cal-cell__day" :class="{ 'cal-cell__day--today': day.isToday }">{{ day.day }}</span>
              <span v-if="day.isHoliday" class="cal-cell__holiday-tag" :title="day.holidayName ?? ''">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              </span>
            </div>
            <div class="cal-cell__events">
              <div
                v-for="order in day.orders.slice(0, 3)"
                :key="order.id"
                class="cal-event"
                :style="{ borderLeftColor: statusDotColor(order.status) }"
              >
                <span class="cal-event__number">#{{ order.number }}</span>
                <span class="cal-event__time">{{ order.fulfillment?.pickup_time ?? '' }}</span>
              </div>
              <div v-if="day.orders.length > 3" class="cal-cell__more">
                +{{ day.orders.length - 3 }} mas
              </div>
            </div>
          </div>
        </div>

        <!-- Legend -->
        <div class="cal-legend">
          <div class="cal-legend__item">
            <span class="cal-legend__dot" style="background: var(--admin-warning);"></span> Pendiente
          </div>
          <div class="cal-legend__item">
            <span class="cal-legend__dot" style="background: var(--admin-info);"></span> Confirmado / Produccion
          </div>
          <div class="cal-legend__item">
            <span class="cal-legend__dot" style="background: var(--admin-success);"></span> Listo / Completado
          </div>
          <div class="cal-legend__item">
            <span class="cal-legend__dot" style="background: var(--admin-error);"></span> Cancelado
          </div>
        </div>
      </template>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- HORARIO SEMANAL                                                    -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'schedule'" class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Horario Semanal</h3>
      </div>
      <div class="admin-content-card__body">
        <p v-if="scheduleLoading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
          Cargando horario...
        </p>
        <template v-else>
          <table class="admin-table">
            <thead>
              <tr><th>Dia</th><th>Apertura</th><th>Cierre</th><th>Activo</th></tr>
            </thead>
            <tbody>
              <tr v-for="entry in schedule" :key="entry.id">
                <td>{{ DAY_NAMES[entry.day_of_week] }}</td>
                <td><input v-model="entry.opens_at" type="time" class="cal-input" /></td>
                <td><input v-model="entry.closes_at" type="time" class="cal-input" /></td>
                <td>
                  <span
                    class="admin-badge"
                    :class="entry.is_active ? 'admin-badge--success' : 'admin-badge--default'"
                    style="cursor: pointer; user-select: none;"
                    @click="entry.is_active = !entry.is_active"
                  >
                    {{ entry.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
              </tr>
              <tr v-if="schedule.length === 0"><td colspan="4">Sin datos.</td></tr>
            </tbody>
          </table>
          <div style="margin-top: 1rem;">
            <button class="admin-btn admin-btn--primary" :disabled="scheduleSaving" @click="saveSchedule">
              {{ scheduleSaving ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SLOTS DE ENTREGA                                                   -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'slots'" class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Slots de Entrega</h3>
      </div>
      <div class="admin-content-card__body">
        <div class="cal-add-form">
          <label>Label <input v-model="newSlot.label" type="text" placeholder="Ej. Manana" /></label>
          <label>Inicio <input v-model="newSlot.starts_at" type="time" /></label>
          <label>Fin <input v-model="newSlot.ends_at" type="time" /></label>
          <label>Max <input v-model.number="newSlot.max_orders" type="number" min="1" style="width: 5rem;" /></label>
          <button class="admin-btn admin-btn--primary admin-btn--sm" @click="createSlot">Agregar</button>
        </div>
        <p v-if="slotsLoading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">Cargando slots...</p>
        <table v-else class="admin-table">
          <thead><tr><th>Label</th><th>Inicio</th><th>Fin</th><th>Max Pedidos</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr v-for="slot in slots" :key="slot.id">
              <td>{{ slot.label }}</td><td>{{ slot.starts_at }}</td><td>{{ slot.ends_at }}</td><td>{{ slot.max_orders }}</td>
              <td><button class="admin-btn admin-btn--sm" style="color: var(--admin-error);" @click="deleteSlot(slot.id)">Eliminar</button></td>
            </tr>
            <tr v-if="slots.length === 0"><td colspan="5">Sin slots registrados.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- DIAS FERIADOS                                                      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'holidays'" class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Dias Feriados</h3>
      </div>
      <div class="admin-content-card__body">
        <div class="cal-add-form">
          <label>Fecha <input v-model="newHoliday.date" type="date" /></label>
          <label>Nombre <input v-model="newHoliday.name" type="text" placeholder="Ej. Navidad" /></label>
          <button class="admin-btn admin-btn--primary admin-btn--sm" @click="createHoliday">Agregar</button>
        </div>
        <p v-if="holidaysLoading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">Cargando feriados...</p>
        <table v-else class="admin-table">
          <thead><tr><th>Fecha</th><th>Nombre</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr v-for="holiday in holidays" :key="holiday.id">
              <td>{{ holiday.date }}</td><td>{{ holiday.name }}</td>
              <td><button class="admin-btn admin-btn--sm" style="color: var(--admin-error);" @click="deleteHoliday(holiday.id)">Eliminar</button></td>
            </tr>
            <tr v-if="holidays.length === 0"><td colspan="3">Sin feriados registrados.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- DAY ORDERS MODAL                                                   -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="selectedDate && !selectedOrder" class="cal-modal-overlay" @click.self="closeModal">
        <div class="cal-modal">
          <div class="cal-modal__header">
            <div>
              <h2 class="cal-modal__title">Entregas del {{ formatDate(selectedDate) }}</h2>
              <span class="cal-modal__count">{{ selectedDayOrders.length }} {{ selectedDayOrders.length === 1 ? 'pedido' : 'pedidos' }}</span>
            </div>
            <button class="cal-modal__close" @click="closeModal">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="cal-modal__body">
            <div
              v-for="order in selectedDayOrders"
              :key="order.id"
              class="cal-order-row"
              @click="openOrderDetail(order)"
            >
              <div class="cal-order-row__left">
                <span class="cal-order-row__dot" :style="{ background: statusDotColor(order.status) }"></span>
                <div>
                  <strong>#{{ order.number }}</strong>
                  <span v-if="order.user_name" class="cal-order-row__customer">{{ order.user_name }}</span>
                </div>
              </div>
              <div class="cal-order-row__right">
                <span class="admin-badge" :class="statusBadgeClass(order.status)">{{ order.status_label }}</span>
                <span class="cal-order-row__time">{{ order.fulfillment?.pickup_time ?? '' }}</span>
                <span class="cal-order-row__total">{{ money(order.totals.total, order.totals.currency) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ORDER DETAIL MODAL -->
      <div v-if="selectedOrder" class="cal-modal-overlay" @click.self="closeModal">
        <div class="cal-modal cal-modal--detail">
          <div class="cal-modal__header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <button v-if="selectedDate" class="cal-modal__back" @click="selectedOrder = null">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <div>
                <h2 class="cal-modal__title">Pedido #{{ selectedOrder.number }}</h2>
                <span class="admin-badge" :class="statusBadgeClass(selectedOrder.status)">{{ selectedOrder.status_label }}</span>
              </div>
            </div>
            <button class="cal-modal__close" @click="selectedDate = null; selectedOrder = null; selectedDayOrders = []">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="cal-modal__body">
            <!-- Customer -->
            <div class="cal-detail-section">
              <h4>Cliente</h4>
              <div class="cal-detail-grid">
                <div><span class="cal-detail-label">Nombre</span><span>{{ selectedOrder.user_name ?? '—' }}</span></div>
                <div><span class="cal-detail-label">Email</span><span>{{ selectedOrder.user_email ?? '—' }}</span></div>
              </div>
            </div>

            <!-- Fulfillment -->
            <div class="cal-detail-section">
              <h4>Entrega</h4>
              <div class="cal-detail-grid">
                <div><span class="cal-detail-label">Tipo</span><span>{{ selectedOrder.fulfillment?.type ?? '—' }}</span></div>
                <div v-if="selectedOrder.fulfillment?.pickup_date">
                  <span class="cal-detail-label">Fecha</span><span>{{ formatDate(selectedOrder.fulfillment.pickup_date) }}</span>
                </div>
                <div v-if="selectedOrder.fulfillment?.pickup_time">
                  <span class="cal-detail-label">Hora</span><span>{{ selectedOrder.fulfillment.pickup_time }}</span>
                </div>
              </div>
            </div>

            <!-- Items -->
            <div class="cal-detail-section">
              <h4>Productos</h4>
              <table class="admin-table">
                <thead><tr><th>Producto</th><th>Cant.</th><th>Subtotal</th></tr></thead>
                <tbody>
                  <tr v-for="(item, i) in selectedOrder.items" :key="i">
                    <td>{{ item.product_name }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ money(item.line_total, selectedOrder.totals.currency) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totals -->
            <div class="cal-detail-section">
              <div class="cal-detail-totals">
                <div class="cal-detail-totals__row">
                  <span>Subtotal</span>
                  <span>{{ money(selectedOrder.totals.subtotal, selectedOrder.totals.currency) }}</span>
                </div>
                <div class="cal-detail-totals__row cal-detail-totals__row--grand">
                  <span>Total</span>
                  <span>{{ money(selectedOrder.totals.total, selectedOrder.totals.currency) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* ===== Tabs ===== */
.cal-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.cal-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  background: var(--admin-surface);
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
  font-family: var(--admin-font);
  transition: all 0.15s;
}

.cal-tab:hover { border-color: var(--admin-primary); color: var(--admin-primary); }

.cal-tab--active {
  background: var(--admin-primary);
  color: white;
  border-color: var(--admin-primary);
}

/* ===== Calendar Navigation ===== */
.cal-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.cal-nav__left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cal-nav__btn {
  width: 36px;
  height: 36px;
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  background: var(--admin-surface);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--admin-text-secondary);
  transition: all 0.15s;
}

.cal-nav__btn:hover {
  border-color: var(--admin-primary);
  color: var(--admin-primary);
  background: var(--admin-primary-light);
}

.cal-nav__title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0 0 0 0.5rem;
  color: var(--admin-text);
}

/* ===== Calendar Grid ===== */
.cal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: var(--admin-surface);
  border-radius: var(--admin-radius);
  box-shadow: var(--admin-shadow);
  overflow: hidden;
  border: 1px solid var(--admin-border);
}

.cal-grid__dow {
  padding: 0.75rem 0.5rem;
  text-align: center;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--admin-text-secondary);
  background: var(--admin-bg);
  border-bottom: 1px solid var(--admin-border);
}

.cal-cell {
  min-height: 110px;
  padding: 0.4rem;
  border-right: 1px solid var(--admin-border);
  border-bottom: 1px solid var(--admin-border);
  cursor: default;
  transition: background 0.15s;
  position: relative;
}

.cal-cell:nth-child(7n + 7) { border-right: none; }

.cal-cell--outside {
  background: #fafbfc;
}

.cal-cell--outside .cal-cell__day {
  color: var(--admin-text-muted);
}

.cal-cell--today {
  background: var(--admin-primary-light);
}

.cal-cell--holiday {
  background: var(--admin-warning-light);
}

.cal-cell--has-orders {
  cursor: pointer;
}

.cal-cell--has-orders:hover {
  background: #f0f4ff;
}

.cal-cell__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.cal-cell__day {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text);
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.cal-cell__day--today {
  background: var(--admin-primary);
  color: white;
}

.cal-cell__holiday-tag {
  color: var(--admin-warning);
  font-size: 0.65rem;
}

.cal-cell__events {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.cal-event {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--admin-bg);
  border-left: 3px solid var(--admin-primary);
  font-size: 0.7rem;
  white-space: nowrap;
  overflow: hidden;
}

.cal-event__number {
  font-weight: 600;
  color: var(--admin-text);
}

.cal-event__time {
  color: var(--admin-text-muted);
  font-size: 0.65rem;
}

.cal-cell__more {
  font-size: 0.65rem;
  color: var(--admin-primary);
  font-weight: 600;
  padding: 1px 6px;
  cursor: pointer;
}

/* ===== Legend ===== */
.cal-legend {
  display: flex;
  gap: 1.5rem;
  padding: 1rem 0;
  flex-wrap: wrap;
}

.cal-legend__item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: var(--admin-text-secondary);
}

.cal-legend__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ===== Forms ===== */
.cal-add-form {
  display: flex;
  gap: 0.75rem;
  align-items: end;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.cal-add-form label {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
}

.cal-add-form input, .cal-input {
  padding: 0.4rem 0.6rem;
  border: 1px solid var(--admin-border);
  border-radius: 6px;
  font-size: 0.85rem;
  font-family: var(--admin-font);
  color: var(--admin-text);
}

/* ===== Modals ===== */
.cal-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  animation: cal-fade 0.2s ease;
}

.cal-modal {
  background: var(--admin-surface);
  border-radius: var(--admin-radius);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  width: min(580px, 100%);
  max-height: 85vh;
  overflow-y: auto;
  font-family: var(--admin-font);
  animation: cal-slide 0.25s ease;
}

.cal-modal--detail {
  width: min(620px, 100%);
}

.cal-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid var(--admin-border);
}

.cal-modal__title {
  font-size: 1.15rem;
  font-weight: 700;
  margin: 0;
  color: var(--admin-text);
}

.cal-modal__count {
  font-size: 0.8rem;
  color: var(--admin-text-muted);
}

.cal-modal__close, .cal-modal__back {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
  color: var(--admin-text-muted);
  transition: all 0.15s;
  display: flex;
}

.cal-modal__close:hover { background: var(--admin-error-light); color: var(--admin-error); }
.cal-modal__back:hover { background: var(--admin-primary-light); color: var(--admin-primary); }

.cal-modal__body {
  padding: 1rem 1.5rem 1.5rem;
}

/* ===== Day orders list ===== */
.cal-order-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 0;
  border-bottom: 1px solid var(--admin-border);
  cursor: pointer;
  transition: background 0.15s;
  margin: 0 -0.5rem;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  border-radius: 8px;
}

.cal-order-row:hover { background: var(--admin-bg); }
.cal-order-row:last-child { border-bottom: none; }

.cal-order-row__left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.cal-order-row__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.cal-order-row__customer {
  display: block;
  font-size: 0.8rem;
  color: var(--admin-text-muted);
}

.cal-order-row__right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.cal-order-row__time {
  font-size: 0.8rem;
  color: var(--admin-text-secondary);
}

.cal-order-row__total {
  font-weight: 700;
  font-size: 0.85rem;
}

/* ===== Order detail sections ===== */
.cal-detail-section {
  margin-bottom: 1.25rem;
}

.cal-detail-section h4 {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 0.6rem;
}

.cal-detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 0.6rem;
}

.cal-detail-grid > div {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.cal-detail-label {
  font-size: 0.7rem;
  color: var(--admin-text-muted);
  font-weight: 500;
}

.cal-detail-totals {
  background: var(--admin-bg);
  border-radius: 8px;
  padding: 0.85rem;
}

.cal-detail-totals__row {
  display: flex;
  justify-content: space-between;
  padding: 0.3rem 0;
  font-size: 0.9rem;
}

.cal-detail-totals__row--grand {
  border-top: 1px solid var(--admin-border);
  margin-top: 0.3rem;
  padding-top: 0.6rem;
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--admin-primary);
}

@keyframes cal-fade { from { opacity: 0; } to { opacity: 1; } }
@keyframes cal-slide { from { opacity: 0; transform: translateY(16px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }

@media (max-width: 768px) {
  .cal-cell { min-height: 70px; }
  .cal-event { font-size: 0.6rem; }
  .cal-modal { width: 100%; }
}
</style>
