<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

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
const showDayDialog = ref(false)
const showOrderDialog = ref(false)

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
  for (const h of holidays.value) holidayMap.set(h.date, h.name)

  const orderMap = new Map<string, AdminOrder[]>()
  for (const o of orders.value) {
    const d = o.fulfillment?.pickup_date
    if (d) {
      if (!orderMap.has(d)) orderMap.set(d, [])
      orderMap.get(d)!.push(o)
    }
  }

  const days: CalendarDay[] = []

  const prevLast = new Date(year, month, 0)
  for (let i = startDow - 1; i >= 0; i--) {
    const d = prevLast.getDate() - i
    const date = new Date(year, month - 1, d)
    const dk = mkDateKey(date)
    days.push({ date, day: d, inMonth: false, isToday: false, isHoliday: holidayMap.has(dk), holidayName: holidayMap.get(dk) ?? null, dateKey: dk, orders: orderMap.get(dk) ?? [] })
  }

  for (let d = 1; d <= lastDay.getDate(); d++) {
    const date = new Date(year, month, d)
    const dk = mkDateKey(date)
    days.push({ date, day: d, inMonth: true, isToday: dk === todayKey, isHoliday: holidayMap.has(dk), holidayName: holidayMap.get(dk) ?? null, dateKey: dk, orders: orderMap.get(dk) ?? [] })
  }

  const remaining = 7 - (days.length % 7)
  if (remaining < 7) {
    for (let d = 1; d <= remaining; d++) {
      const date = new Date(year, month + 1, d)
      const dk = mkDateKey(date)
      days.push({ date, day: d, inMonth: false, isToday: false, isHoliday: holidayMap.has(dk), holidayName: holidayMap.get(dk) ?? null, dateKey: dk, orders: orderMap.get(dk) ?? [] })
    }
  }

  return days
})

function mkDateKey(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function prevMonth(): void {
  if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value-- }
  else currentMonth.value--
}

function nextMonth(): void {
  if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++ }
  else currentMonth.value++
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
  showDayDialog.value = true
}

function openOrderDetail(order: AdminOrder): void {
  selectedOrder.value = order
  showDayDialog.value = false
  showOrderDialog.value = true
}

function backToDay(): void {
  showOrderDialog.value = false
  showDayDialog.value = true
}

function money(amount: number, currency = 'CRC'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'long' }).format(new Date(dateStr))
}

function statusSeverity(status: string): 'warn' | 'info' | 'success' | 'danger' | 'secondary' {
  if (status === 'pending') return 'warn'
  if (status === 'confirmed' || status === 'in_production') return 'info'
  if (status === 'ready' || status === 'completed') return 'success'
  if (status === 'cancelled') return 'danger'
  return 'secondary'
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
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Calendario</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="cal-tabs">
      <Button
        label="Vista Calendario"
        icon="pi pi-calendar"
        :severity="activeTab === 'calendar' ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeTab = 'calendar'"
      />
      <Button
        label="Horario"
        icon="pi pi-clock"
        :severity="activeTab === 'schedule' ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeTab = 'schedule'"
      />
      <Button
        label="Slots"
        icon="pi pi-box"
        :severity="activeTab === 'slots' ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeTab = 'slots'"
      />
      <Button
        label="Feriados"
        icon="pi pi-star"
        :severity="activeTab === 'holidays' ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeTab = 'holidays'"
      />
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- CALENDAR VIEW                                                      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <template v-if="activeTab === 'calendar'">
      <div v-if="calendarLoading" style="display:flex; justify-content:center; padding:3rem;">
        <ProgressSpinner />
      </div>

      <template v-else>
        <div class="cal-nav">
          <div class="cal-nav__left">
            <button class="cal-nav__btn" @click="prevMonth">
              <i class="pi pi-chevron-left" />
            </button>
            <button class="cal-nav__btn" @click="nextMonth">
              <i class="pi pi-chevron-right" />
            </button>
            <h2 class="cal-nav__title">{{ calendarLabel }}</h2>
          </div>
          <Button label="Hoy" size="small" severity="secondary" outlined @click="goToday" />
        </div>

        <div class="cal-grid">
          <div v-for="d in DAY_NAMES_SHORT" :key="d" class="cal-grid__dow">{{ d }}</div>

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
                <i class="pi pi-star-fill" style="font-size:0.6rem;" />
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
              <div v-if="day.orders.length > 3" class="cal-cell__more">+{{ day.orders.length - 3 }} mas</div>
            </div>
          </div>
        </div>

        <div class="cal-legend">
          <div class="cal-legend__item"><span class="cal-legend__dot" style="background:var(--admin-warning);" /> Pendiente</div>
          <div class="cal-legend__item"><span class="cal-legend__dot" style="background:var(--admin-info);" /> Confirmado / Produccion</div>
          <div class="cal-legend__item"><span class="cal-legend__dot" style="background:var(--admin-success);" /> Listo / Completado</div>
          <div class="cal-legend__item"><span class="cal-legend__dot" style="background:var(--admin-error);" /> Cancelado</div>
        </div>
      </template>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- HORARIO SEMANAL                                                    -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <Card v-if="activeTab === 'schedule'">
      <template #title>Horario Semanal</template>
      <template #content>
        <div v-if="scheduleLoading" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>
        <template v-else>
          <DataTable :value="schedule" class="p-datatable-sm">
            <template #empty><div style="text-align:center; padding:1rem; color:var(--admin-text-muted);">Sin datos.</div></template>
            <Column header="Día" style="width:140px.">
              <template #body="{ data }">{{ DAY_NAMES[data.day_of_week] }}</template>
            </Column>
            <Column header="Apertura" style="width:160px.">
              <template #body="{ data }">
                <input v-model="data.opens_at" type="time" class="time-input" />
              </template>
            </Column>
            <Column header="Cierre" style="width:160px.">
              <template #body="{ data }">
                <input v-model="data.closes_at" type="time" class="time-input" />
              </template>
            </Column>
            <Column header="Activo" style="width:100px.">
              <template #body="{ data }">
                <Tag
                  :value="data.is_active ? 'Activo' : 'Inactivo'"
                  :severity="data.is_active ? 'success' : 'secondary'"
                  style="cursor:pointer;"
                  @click="data.is_active = !data.is_active"
                />
              </template>
            </Column>
          </DataTable>
          <div style="margin-top:1rem;">
            <Button
              :label="scheduleSaving ? 'Guardando...' : 'Guardar cambios'"
              :loading="scheduleSaving"
              @click="saveSchedule"
            />
          </div>
        </template>
      </template>
    </Card>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SLOTS DE ENTREGA                                                   -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <Card v-if="activeTab === 'slots'">
      <template #title>Slots de Entrega</template>
      <template #content>
        <div class="add-form">
          <div class="add-form__field">
            <label>Label</label>
            <InputText v-model="newSlot.label" placeholder="Ej. Mañana" size="small" />
          </div>
          <div class="add-form__field">
            <label>Inicio</label>
            <input v-model="newSlot.starts_at" type="time" class="time-input" />
          </div>
          <div class="add-form__field">
            <label>Fin</label>
            <input v-model="newSlot.ends_at" type="time" class="time-input" />
          </div>
          <div class="add-form__field">
            <label>Max</label>
            <InputNumber v-model="newSlot.max_orders" :min="1" style="width:80px;" inputStyle="width:80px;" size="small" />
          </div>
          <Button label="Agregar" icon="pi pi-plus" size="small" style="align-self:flex-end;" @click="createSlot" />
        </div>

        <div v-if="slotsLoading" style="display:flex; justify-content:center; padding:3rem.">
          <ProgressSpinner />
        </div>
        <DataTable v-else :value="slots" class="p-datatable-sm">
          <template #empty><div style="text-align:center; padding:1rem; color:var(--admin-text-muted);">Sin slots registrados.</div></template>
          <Column header="Label" field="label" />
          <Column header="Inicio" field="starts_at" style="width:110px." />
          <Column header="Fin" field="ends_at" style="width:110px." />
          <Column header="Max Pedidos" field="max_orders" style="width:120px." />
          <Column header="Acciones" style="width:90px.">
            <template #body="{ data }">
              <Button icon="pi pi-trash" size="small" severity="danger" text rounded @click="deleteSlot(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- DIAS FERIADOS                                                      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <Card v-if="activeTab === 'holidays'">
      <template #title>Días Feriados</template>
      <template #content>
        <div class="add-form">
          <div class="add-form__field">
            <label>Fecha</label>
            <input v-model="newHoliday.date" type="date" class="time-input" />
          </div>
          <div class="add-form__field">
            <label>Nombre</label>
            <InputText v-model="newHoliday.name" placeholder="Ej. Navidad" size="small" />
          </div>
          <Button label="Agregar" icon="pi pi-plus" size="small" style="align-self:flex-end;" @click="createHoliday" />
        </div>

        <div v-if="holidaysLoading" style="display:flex; justify-content:center; padding:3rem.">
          <ProgressSpinner />
        </div>
        <DataTable v-else :value="holidays" class="p-datatable-sm">
          <template #empty><div style="text-align:center; padding:1rem; color:var(--admin-text-muted);">Sin feriados registrados.</div></template>
          <Column header="Fecha" field="date" style="width:140px." />
          <Column header="Nombre" field="name" />
          <Column header="Acciones" style="width:90px.">
            <template #body="{ data }">
              <Button icon="pi pi-trash" size="small" severity="danger" text rounded @click="deleteHoliday(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Day orders dialog -->
    <Dialog
      v-model:visible="showDayDialog"
      modal
      :header="`Entregas del ${formatDate(selectedDate)}`"
      :style="{ width: '560px' }"
    >
      <div class="day-orders-list">
        <div
          v-for="order in selectedDayOrders"
          :key="order.id"
          class="cal-order-row"
          @click="openOrderDetail(order)"
        >
          <div class="cal-order-row__left">
            <span class="cal-order-row__dot" :style="{ background: statusDotColor(order.status) }" />
            <div>
              <strong>#{{ order.number }}</strong>
              <span v-if="order.user_name" class="cal-order-row__customer">{{ order.user_name }}</span>
            </div>
          </div>
          <div class="cal-order-row__right">
            <Tag :value="order.status_label" :severity="statusSeverity(order.status)" />
            <span class="cal-order-row__time">{{ order.fulfillment?.pickup_time ?? '' }}</span>
            <span class="cal-order-row__total">{{ money(order.totals.total, order.totals.currency) }}</span>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Order detail dialog -->
    <Dialog
      v-model:visible="showOrderDialog"
      modal
      :style="{ width: '600px' }"
      @hide="selectedOrder = null"
    >
      <template #header>
        <div style="display:flex; align-items:center; gap:0.75rem;">
          <Button icon="pi pi-arrow-left" severity="secondary" text rounded size="small" @click="backToDay" />
          <span style="font-weight:700; font-size:1.1rem;">Pedido #{{ selectedOrder?.number }}</span>
          <Tag v-if="selectedOrder" :value="selectedOrder.status_label" :severity="statusSeverity(selectedOrder.status)" />
        </div>
      </template>

      <template v-if="selectedOrder">
        <div class="detail-section">
          <h4>Cliente</h4>
          <div class="detail-grid">
            <div><span class="detail-label">Nombre</span><span>{{ selectedOrder.user_name ?? '—' }}</span></div>
            <div><span class="detail-label">Email</span><span>{{ selectedOrder.user_email ?? '—' }}</span></div>
          </div>
        </div>

        <div class="detail-section">
          <h4>Entrega</h4>
          <div class="detail-grid">
            <div><span class="detail-label">Tipo</span><span>{{ selectedOrder.fulfillment?.type ?? '—' }}</span></div>
            <div v-if="selectedOrder.fulfillment?.pickup_date">
              <span class="detail-label">Fecha</span><span>{{ formatDate(selectedOrder.fulfillment.pickup_date) }}</span>
            </div>
            <div v-if="selectedOrder.fulfillment?.pickup_time">
              <span class="detail-label">Hora</span><span>{{ selectedOrder.fulfillment.pickup_time }}</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <h4>Productos</h4>
          <DataTable :value="selectedOrder.items" class="p-datatable-sm">
            <Column header="Producto" field="product_name" />
            <Column header="Cant." field="quantity" style="width:70px." />
            <Column header="Subtotal" style="width:110px.">
              <template #body="{ data }">{{ money(data.line_total, selectedOrder!.totals.currency) }}</template>
            </Column>
          </DataTable>
        </div>

        <div class="detail-totals">
          <div class="detail-totals__row">
            <span>Subtotal</span><span>{{ money(selectedOrder.totals.subtotal, selectedOrder.totals.currency) }}</span>
          </div>
          <div class="detail-totals__row detail-totals__row--grand">
            <span>Total</span><span>{{ money(selectedOrder.totals.total, selectedOrder.totals.currency) }}</span>
          </div>
        </div>
      </template>
    </Dialog>
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
}
.cal-cell:nth-child(7n + 7) { border-right: none; }
.cal-cell--outside { background: #fafbfc; }
.cal-cell--outside .cal-cell__day { color: var(--admin-text-muted); }
.cal-cell--today { background: var(--admin-primary-light); }
.cal-cell--holiday { background: var(--admin-warning-light); }
.cal-cell--has-orders { cursor: pointer; }
.cal-cell--has-orders:hover { background: #f0f4ff; }
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
.cal-cell__day--today { background: var(--admin-primary); color: white; }
.cal-cell__holiday-tag { color: var(--admin-warning); font-size: 0.65rem; }
.cal-cell__events { display: flex; flex-direction: column; gap: 2px; }
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
.cal-event__number { font-weight: 600; color: var(--admin-text); }
.cal-event__time { color: var(--admin-text-muted); font-size: 0.65rem; }
.cal-cell__more { font-size: 0.65rem; color: var(--admin-primary); font-weight: 600; padding: 1px 6px; }

/* ===== Legend ===== */
.cal-legend { display: flex; gap: 1.5rem; padding: 1rem 0; flex-wrap: wrap; }
.cal-legend__item { display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; color: var(--admin-text-secondary); }
.cal-legend__dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

/* ===== Add form ===== */
.add-form {
  display: flex;
  gap: 0.75rem;
  align-items: flex-end;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.add-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
}
.time-input {
  padding: 0.4rem 0.6rem;
  border: 1px solid var(--admin-border);
  border-radius: 6px;
  font-size: 0.85rem;
  font-family: var(--admin-font);
  color: var(--admin-text);
  background: var(--admin-surface);
}

/* ===== Day orders dialog list ===== */
.day-orders-list { display: flex; flex-direction: column; }
.cal-order-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 0.5rem;
  border-bottom: 1px solid var(--admin-border);
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.15s;
}
.cal-order-row:hover { background: var(--admin-bg); }
.cal-order-row:last-child { border-bottom: none; }
.cal-order-row__left { display: flex; align-items: center; gap: 0.75rem; }
.cal-order-row__dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; display: inline-block; }
.cal-order-row__customer { display: block; font-size: 0.8rem; color: var(--admin-text-muted); }
.cal-order-row__right { display: flex; align-items: center; gap: 0.75rem; }
.cal-order-row__time { font-size: 0.8rem; color: var(--admin-text-secondary); }
.cal-order-row__total { font-weight: 700; font-size: 0.85rem; }

/* ===== Order detail ===== */
.detail-section { margin-bottom: 1.25rem; }
.detail-section h4 {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 0.6rem;
}
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 0.6rem;
}
.detail-grid > div { display: flex; flex-direction: column; gap: 0.1rem; }
.detail-label { font-size: 0.7rem; color: var(--admin-text-muted); font-weight: 500; }
.detail-totals { background: var(--admin-bg); border-radius: 8px; padding: 0.85rem; margin-top: 1rem; }
.detail-totals__row { display: flex; justify-content: space-between; padding: 0.3rem 0; font-size: 0.9rem; }
.detail-totals__row--grand {
  border-top: 1px solid var(--admin-border);
  margin-top: 0.3rem;
  padding-top: 0.6rem;
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--admin-primary);
}

@media (max-width: 768px) {
  .cal-cell { min-height: 70px; }
  .cal-event { font-size: 0.6rem; }
}
</style>
