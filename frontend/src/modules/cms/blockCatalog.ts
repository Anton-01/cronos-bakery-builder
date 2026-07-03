import type { BlockType } from './types'

/**
 * Declarative description of every block type: what it is, the default
 * payload a new instance starts with, and the fields the inspector renders.
 * Adding a new block type = one entry here + backend BlockRules + a renderer.
 */

export type FieldKind =
  | 'text'
  | 'textarea'
  | 'richtext'
  | 'number'
  | 'toggle'
  | 'select'
  | 'image'
  | 'items'

export interface SelectOption {
  label: string
  value: string
}

export interface FieldDef {
  key: string
  label: string
  kind: FieldKind
  required?: boolean
  help?: string
  options?: SelectOption[]
  /** Sub-fields when kind === 'items' (repeatable rows). */
  itemFields?: FieldDef[]
  /** Only show the field when another key has a given value. */
  visibleWhen?: { key: string; equals: unknown }
  min?: number
  max?: number
}

export interface BlockDefinition {
  type: BlockType
  label: string
  description: string
  defaults: Record<string, unknown>
  fields: FieldDef[]
}

export const blockCatalog: BlockDefinition[] = [
  {
    type: 'hero',
    label: 'Hero',
    description: 'Encabezado principal con titular, imagen de fondo y llamada a la acción.',
    defaults: { heading: '', subheading: '', image: '', cta_label: '', cta_url: '' },
    fields: [
      { key: 'heading', label: 'Titular', kind: 'text', required: true },
      { key: 'subheading', label: 'Subtítulo', kind: 'textarea' },
      { key: 'image', label: 'Imagen de fondo', kind: 'image' },
      { key: 'cta_label', label: 'Texto del botón', kind: 'text' },
      { key: 'cta_url', label: 'Enlace del botón', kind: 'text' },
    ],
  },
  {
    type: 'text',
    label: 'Texto enriquecido',
    description: 'Bloque de contenido libre con formato (negritas, listas, enlaces).',
    defaults: { body: '' },
    fields: [{ key: 'body', label: 'Contenido', kind: 'richtext', required: true }],
  },
  {
    type: 'gallery',
    label: 'Galería',
    description: 'Cuadrícula de imágenes con pie de foto opcional.',
    defaults: { title: '', images: [] },
    fields: [
      { key: 'title', label: 'Título de la sección', kind: 'text' },
      {
        key: 'images',
        label: 'Imágenes',
        kind: 'items',
        required: true,
        itemFields: [
          { key: 'url', label: 'Imagen', kind: 'image', required: true },
          { key: 'caption', label: 'Pie de foto', kind: 'text' },
        ],
      },
    ],
  },
  {
    type: 'products',
    label: 'Productos de repostería',
    description: 'Muestra productos del catálogo de forma dinámica (novedades, categoría o selección manual).',
    defaults: { title: '', source: 'latest', category_slug: '', limit: 8, show_price: true },
    fields: [
      { key: 'title', label: 'Título de la sección', kind: 'text' },
      {
        key: 'source',
        label: 'Origen de productos',
        kind: 'select',
        required: true,
        options: [
          { label: 'Más recientes', value: 'latest' },
          { label: 'Destacados', value: 'featured' },
          { label: 'Por categoría', value: 'category' },
          { label: 'Selección manual', value: 'manual' },
        ],
      },
      {
        key: 'category_slug',
        label: 'Slug de la categoría',
        kind: 'text',
        visibleWhen: { key: 'source', equals: 'category' },
        help: 'Identificador de la categoría del catálogo, ej. "pasteles".',
      },
      { key: 'limit', label: 'Cantidad máxima', kind: 'number', min: 1, max: 24 },
      { key: 'show_price', label: 'Mostrar precios', kind: 'toggle' },
    ],
  },
  {
    type: 'cards',
    label: 'Cards',
    description: 'Conjunto de tarjetas con título, texto e imagen opcional.',
    defaults: { title: '', items: [] },
    fields: [
      { key: 'title', label: 'Título de la sección', kind: 'text' },
      {
        key: 'items',
        label: 'Tarjetas',
        kind: 'items',
        required: true,
        itemFields: [
          { key: 'title', label: 'Título', kind: 'text', required: true },
          { key: 'text', label: 'Texto', kind: 'textarea' },
          { key: 'image', label: 'Imagen', kind: 'image' },
        ],
      },
    ],
  },
  {
    type: 'banner',
    label: 'Banner',
    description: 'Imagen promocional de ancho completo con enlace opcional.',
    defaults: { image: '', link: '', alt: '' },
    fields: [
      { key: 'image', label: 'Imagen', kind: 'image', required: true },
      { key: 'link', label: 'Enlace', kind: 'text' },
      { key: 'alt', label: 'Texto alternativo', kind: 'text' },
    ],
  },
  {
    type: 'video',
    label: 'Video',
    description: 'Video embebido (YouTube, Vimeo o archivo directo).',
    defaults: { url: '', title: '', autoplay: false },
    fields: [
      { key: 'url', label: 'URL del video', kind: 'text', required: true },
      { key: 'title', label: 'Título', kind: 'text' },
      { key: 'autoplay', label: 'Reproducción automática', kind: 'toggle' },
    ],
  },
  {
    type: 'cta',
    label: 'Llamada a la acción',
    description: 'Franja destacada con mensaje y botón.',
    defaults: { heading: '', text: '', cta_label: '', cta_url: '' },
    fields: [
      { key: 'heading', label: 'Titular', kind: 'text', required: true },
      { key: 'text', label: 'Texto de apoyo', kind: 'textarea' },
      { key: 'cta_label', label: 'Texto del botón', kind: 'text', required: true },
      { key: 'cta_url', label: 'Enlace del botón', kind: 'text', required: true },
    ],
  },
  {
    type: 'faq',
    label: 'Preguntas frecuentes',
    description: 'Lista de preguntas y respuestas en acordeón.',
    defaults: { title: '', items: [] },
    fields: [
      { key: 'title', label: 'Título de la sección', kind: 'text' },
      {
        key: 'items',
        label: 'Preguntas',
        kind: 'items',
        required: true,
        itemFields: [
          { key: 'question', label: 'Pregunta', kind: 'text', required: true },
          { key: 'answer', label: 'Respuesta', kind: 'textarea', required: true },
        ],
      },
    ],
  },
  {
    type: 'testimonials',
    label: 'Testimonios',
    description: 'Opiniones de clientes con autor.',
    defaults: { title: '', items: [] },
    fields: [
      { key: 'title', label: 'Título de la sección', kind: 'text' },
      {
        key: 'items',
        label: 'Testimonios',
        kind: 'items',
        required: true,
        itemFields: [
          { key: 'author', label: 'Autor', kind: 'text', required: true },
          { key: 'quote', label: 'Testimonio', kind: 'textarea', required: true },
        ],
      },
    ],
  },
]

export function blockDefinition(type: BlockType): BlockDefinition | undefined {
  return blockCatalog.find((definition) => definition.type === type)
}

export function blockLabel(type: BlockType): string {
  return blockDefinition(type)?.label ?? type
}
