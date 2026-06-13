<script setup lang="ts" generic="T extends { id: string | number }">
import { computed, ref } from 'vue'

export interface Column<T> {
  key: string
  label: string
  sortable?: boolean
  width?: string
  render?: (row: T) => string
}

export interface RowAction<T> {
  key: string
  label: string
  icon?: string
  danger?: boolean
  hidden?: (row: T) => boolean
}

const props = withDefaults(defineProps<{
  columns: Column<T>[]
  data: T[]
  loading?: boolean
  meta?: { current_page: number; last_page: number; total: number } | null
  perPage?: number
  skeletonRows?: number
  actions?: RowAction<T>[]
  emptyTitle?: string
  emptyMessage?: string
}>(), {
  loading: false,
  perPage: 15,
  skeletonRows: 5,
  emptyTitle: 'Sin resultados',
  emptyMessage: 'No se encontraron registros.',
})

const emit = defineEmits<{
  pageChange: [page: number]
  perPageChange: [value: number]
  action: [action: string, row: T]
}>()

const openDropdown = ref<string | number | null>(null)

function toggleDropdown(id: string | number) {
  openDropdown.value = openDropdown.value === id ? null : id
}

function closeDropdowns() {
  openDropdown.value = null
}

function handleAction(actionKey: string, row: T) {
  closeDropdowns()
  emit('action', actionKey, row)
}

const startItem = computed(() => {
  if (!props.meta) return 0
  return (props.meta.current_page - 1) * props.perPage + 1
})

const endItem = computed(() => {
  if (!props.meta) return 0
  return Math.min(props.meta.current_page * props.perPage, props.meta.total)
})

function getCellValue(row: T, col: Column<T>): unknown {
  if (col.render) return col.render(row)
  return (row as Record<string, unknown>)[col.key] ?? '—'
}

function visibleActions(row: T): RowAction<T>[] {
  return (props.actions ?? []).filter((a) => !a.hidden || !a.hidden(row))
}
</script>

<template>
  <div class="dt-wrapper" @click="closeDropdowns">
    <!-- Skeleton Loading -->
    <table v-if="loading" class="dt-table">
      <thead>
      <tr>
        <th v-for="col in columns" :key="col.key" :style="col.width ? { width: col.width } : {}">
          {{ col.label }}
        </th>
        <th v-if="actions?.length" style="width: 60px"></th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="n in skeletonRows" :key="n" class="dt-row">
        <td v-for="col in columns" :key="col.key">
          <div class="dt-skeleton"></div>
        </td>
        <td v-if="actions?.length">
          <div class="dt-skeleton dt-skeleton--sm"></div>
        </td>
      </tr>
      </tbody>
    </table>

    <!-- Empty State -->
    <Transition name="dt-fade">
      <div v-if="!loading && data.length === 0" class="dt-empty">
        <svg class="dt-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3>{{ emptyTitle }}</h3>
        <p>{{ emptyMessage }}</p>
      </div>
    </Transition>

    <!-- Data Table -->
    <Transition name="dt-fade">
      <table v-if="!loading && data.length > 0" class="dt-table">
        <thead>
        <tr>
          <th v-for="col in columns" :key="col.key" :style="col.width ? { width: col.width } : {}">
            {{ col.label }}
          </th>
          <th v-if="actions?.length" style="width: 60px">Acciones</th>
        </tr>
        </thead>
        <TransitionGroup name="dt-row-anim" tag="tbody">
          <tr v-for="row in data" :key="row.id" class="dt-row">
            <td v-for="col in columns" :key="col.key">
              <slot :name="`cell-${col.key}`" :row="row" :value="getCellValue(row, col)">
                {{ getCellValue(row, col) }}
              </slot>
            </td>
            <td v-if="actions?.length">
              <div class="dt-actions" @click.stop>
                <button class="dt-action-btn" @click="toggleDropdown(row.id)" title="Acciones">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <circle cx="12" cy="5" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="12" cy="19" r="1" />
                  </svg>
                </button>
                <Transition name="dt-dropdown">
                  <div v-if="openDropdown === row.id" class="dt-dropdown">
                    <button
                        v-for="act in visibleActions(row)"
                        :key="act.key"
                        :class="{ 'dt-dropdown__item--danger': act.danger }"
                        class="dt-dropdown__item"
                        @click="handleAction(act.key, row)"
                    >
                      {{ act.label }}
                    </button>
                  </div>
                </Transition>
              </div>
            </td>
          </tr>
        </TransitionGroup>
      </table>
    </Transition>

    <!-- Pagination -->
    <div v-if="meta && meta.total > 0" class="dt-pagination">
      <div class="dt-pagination__perpage">
        <span>Items por página:</span>
        <select :value="perPage" @change="emit('perPageChange', Number(($event.target as HTMLSelectElement).value))">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </div>
      <span class="dt-pagination__info">
        {{ startItem }} &ndash; {{ endItem }} de {{ meta.total }}
      </span>
      <div class="dt-pagination__nav">
        <button :disabled="meta.current_page <= 1" @click="emit('pageChange', 1)" title="Primera">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
        </button>
        <button :disabled="meta.current_page <= 1" @click="emit('pageChange', meta.current_page - 1)" title="Anterior">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M15 19l-7-7 7-7" /></svg>
        </button>
        <button :disabled="meta.current_page >= meta.last_page" @click="emit('pageChange', meta.current_page + 1)" title="Siguiente">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 5l7 7-7 7" /></svg>
        </button>
        <button :disabled="meta.current_page >= meta.last_page" @click="emit('pageChange', meta.last_page)" title="Última">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dt-wrapper {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}
.dt-table {
  width: 100%;
  border-collapse: collapse;
}
.dt-table thead {
  background: #f9fafb;
}
.dt-table th {
  text-align: left;
  padding: 0.875rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}
.dt-table td {
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}
.dt-row:hover {
  background: #f9fafb;
}
.dt-row:last-child td {
  border-bottom: none;
}

/* Skeleton */
.dt-skeleton {
  height: 16px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  border-radius: 4px;
  width: 80%;
}
.dt-skeleton--sm {
  width: 32px;
  height: 32px;
  border-radius: 6px;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Empty */
.dt-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #6b7280;
  text-align: center;
}
.dt-empty__icon {
  width: 56px;
  height: 56px;
  color: #d1d5db;
  margin-bottom: 1rem;
}
.dt-empty h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #374151;
}
.dt-empty p {
  margin: 0;
  font-size: 0.875rem;
}

/* Actions dropdown */
.dt-actions {
  position: relative;
  display: inline-flex;
}
.dt-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: none;
  background: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
}
.dt-action-btn:hover {
  background: #f3f4f6;
  color: #111827;
}
.dt-dropdown {
  position: absolute;
  right: 0;
  top: 100%;
  z-index: 50;
  min-width: 180px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
  padding: 0.375rem;
}
.dt-dropdown__item {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: none;
  background: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.8125rem;
  color: #374151;
  text-align: left;
  transition: background 0.15s;
}
.dt-dropdown__item:hover {
  background: #f3f4f6;
}
.dt-dropdown__item--danger {
  color: #dc2626;
}
.dt-dropdown__item--danger:hover {
  background: #fef2f2;
}

/* Pagination */
.dt-pagination {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 1.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid #e5e7eb;
  font-size: 0.8125rem;
  color: #6b7280;
}
.dt-pagination__perpage {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.dt-pagination__perpage select {
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  background: #fff;
  color: #374151;
  cursor: pointer;
}
.dt-pagination__nav {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.dt-pagination__nav button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
}
.dt-pagination__nav button:hover:not(:disabled) {
  background: #f3f4f6;
  color: #111827;
}
.dt-pagination__nav button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

/* Transitions */
.dt-fade-enter-active,
.dt-fade-leave-active {
  transition: opacity 0.2s ease;
}
.dt-fade-enter-from,
.dt-fade-leave-to {
  opacity: 0;
}
.dt-row-anim-enter-active {
  transition: all 0.3s ease;
}
.dt-row-anim-leave-active {
  transition: all 0.2s ease;
}
.dt-row-anim-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}
.dt-row-anim-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
.dt-row-anim-move {
  transition: transform 0.3s ease;
}
.dt-dropdown-enter-active {
  transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
}
.dt-dropdown-leave-active {
  transition: all 0.1s ease-in;
}
.dt-dropdown-enter-from,
.dt-dropdown-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-4px);
}
</style>