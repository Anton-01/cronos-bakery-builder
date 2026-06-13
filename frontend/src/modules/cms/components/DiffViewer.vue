<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  before: Record<string, unknown> | null
  after: Record<string, unknown>
  title?: string
}>()

interface DiffEntry {
  key: string
  before: unknown
  after: unknown
  status: 'added' | 'removed' | 'changed' | 'unchanged'
}

const diff = computed<DiffEntry[]>(() => {
  const before = props.before ?? {}
  const after = props.after
  const allKeys = new Set([...Object.keys(before), ...Object.keys(after)])
  const entries: DiffEntry[] = []

  for (const key of allKeys) {
    const bVal = before[key]
    const aVal = after[key]
    const bStr = serialize(bVal)
    const aStr = serialize(aVal)

    if (!(key in before)) {
      entries.push({ key, before: undefined, after: aVal, status: 'added' })
    } else if (!(key in after)) {
      entries.push({ key, before: bVal, after: undefined, status: 'removed' })
    } else if (bStr !== aStr) {
      entries.push({ key, before: bVal, after: aVal, status: 'changed' })
    } else {
      entries.push({ key, before: bVal, after: aVal, status: 'unchanged' })
    }
  }

  return entries.sort((a, b) => {
    const order = { changed: 0, added: 1, removed: 2, unchanged: 3 }
    return order[a.status] - order[b.status]
  })
})

const changedCount = computed(() => diff.value.filter((d) => d.status !== 'unchanged').length)

function serialize(val: unknown): string {
  if (val === undefined || val === null) return ''
  if (typeof val === 'object') return JSON.stringify(val, null, 2)
  return String(val)
}

function display(val: unknown): string {
  if (val === undefined) return '—'
  if (val === null) return 'null'
  if (typeof val === 'object') return JSON.stringify(val, null, 2)
  return String(val)
}
</script>

<template>
  <div class="diff-viewer">
    <div class="diff-viewer__header">
      <h3 class="diff-viewer__title">{{ title ?? 'Comparación de Versiones' }}</h3>
      <span class="diff-viewer__badge">{{ changedCount }} cambio{{ changedCount !== 1 ? 's' : '' }}</span>
    </div>

    <div class="diff-viewer__table-wrapper">
      <table class="diff-viewer__table">
        <thead>
          <tr>
            <th class="diff-viewer__th--key">Campo</th>
            <th class="diff-viewer__th--val">Anterior</th>
            <th class="diff-viewer__th--val">Nuevo</th>
          </tr>
        </thead>
        <tbody>
          <TransitionGroup name="diff-row">
            <tr
              v-for="entry in diff"
              :key="entry.key"
              class="diff-viewer__row"
              :class="`diff-viewer__row--${entry.status}`"
            >
              <td class="diff-viewer__cell--key">
                <span class="diff-viewer__key">{{ entry.key }}</span>
                <span
                  v-if="entry.status !== 'unchanged'"
                  class="diff-viewer__tag"
                  :class="`diff-viewer__tag--${entry.status}`"
                >
                  {{ entry.status === 'added' ? '+' : entry.status === 'removed' ? '−' : '~' }}
                </span>
              </td>
              <td
                class="diff-viewer__cell--val"
                :class="{ 'diff-viewer__cell--removed': entry.status === 'removed' || entry.status === 'changed' }"
              >
                <pre class="diff-viewer__pre">{{ display(entry.before) }}</pre>
              </td>
              <td
                class="diff-viewer__cell--val"
                :class="{ 'diff-viewer__cell--added': entry.status === 'added' || entry.status === 'changed' }"
              >
                <pre class="diff-viewer__pre">{{ display(entry.after) }}</pre>
              </td>
            </tr>
          </TransitionGroup>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.diff-viewer {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}
.diff-viewer__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}
.diff-viewer__title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #111827;
}
.diff-viewer__badge {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.2rem 0.625rem;
  border-radius: 9999px;
  background: #eef2ff;
  color: #4f46e5;
}
.diff-viewer__table-wrapper {
  overflow-x: auto;
}
.diff-viewer__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}
.diff-viewer__table th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-size: 0.6875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}
.diff-viewer__th--key { width: 180px; }
.diff-viewer__th--val { width: 50%; }

.diff-viewer__row td {
  padding: 0.625rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: top;
}
.diff-viewer__row:last-child td {
  border-bottom: none;
}
.diff-viewer__row--unchanged {
  opacity: 0.5;
}
.diff-viewer__cell--key {
  display: flex;
  align-items: flex-start;
  gap: 0.375rem;
}
.diff-viewer__key {
  font-weight: 500;
  color: #374151;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
}
.diff-viewer__tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 4px;
  font-size: 0.6875rem;
  font-weight: 700;
  flex-shrink: 0;
}
.diff-viewer__tag--added { background: #dcfce7; color: #16a34a; }
.diff-viewer__tag--removed { background: #fef2f2; color: #dc2626; }
.diff-viewer__tag--changed { background: #fef3c7; color: #d97706; }

.diff-viewer__cell--val {
  max-width: 0;
}
.diff-viewer__cell--removed {
  background: #fef2f2;
}
.diff-viewer__cell--added {
  background: #f0fdf4;
}
.diff-viewer__pre {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
  color: #374151;
  line-height: 1.5;
}

/* Transition */
.diff-row-enter-active {
  transition: all 0.3s ease;
}
.diff-row-leave-active {
  transition: all 0.2s ease;
}
.diff-row-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.diff-row-leave-to {
  opacity: 0;
}
.diff-row-move {
  transition: transform 0.3s ease;
}
</style>
