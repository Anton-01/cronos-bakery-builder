// Rich text content is now managed as plain HTML strings via PrimeVue <Editor>
// This composable is kept as a no-op for backward compatibility during migration.
export function useRichTextEditor(_options: { placeholder?: string; content?: string; onUpdate?: (html: string) => void } = {}) {
  return null
}
