<script setup lang="ts">
import { EditorContent } from '@tiptap/vue-3'
import type { Editor } from '@tiptap/vue-3'

import type { ProductFormState } from '../composables/useProductForm'
const form = defineModel<ProductFormState>({ required: true })
defineProps<{
  editor: Editor | undefined
}>()
const emit = defineEmits<{
  'name-input': []
}>()
</script>

<template>
  <div class="admin-content-card" style="margin-bottom: 1.5rem;">
    <div class="admin-content-card__header">
      <h3 class="admin-content-card__title">General</h3>
    </div>
    <div class="admin-content-card__body">
      <div class="admin-product-form__field">
        <label class="admin-product-form__label" for="pf-name">Nombre del producto</label>
        <input
            id="pf-name"
            v-model="form.name"
            type="text"
            class="admin-product-form__input"
            required
            placeholder="Ej: Pastel de Chocolate"
            @input="emit('name-input')"
        />
        <span v-if="form.slug" class="product-slug-display">
          /{{ form.slug }}
        </span>
      </div>
      <div class="admin-product-form__field">
        <label class="admin-product-form__label">Descripción</label>
        <div class="tiptap-editor-wrapper">
          <div v-if="editor" class="tiptap-toolbar">
            <button type="button" :class="{ 'is-active': editor.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()">
              <strong>B</strong>
            </button>
            <button type="button" :class="{ 'is-active': editor.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()">
              <em>I</em>
            </button>
            <button type="button" :class="{ 'is-active': editor.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()">
              <u>U</u>
            </button>
            <span class="tiptap-toolbar__sep"></span>
            <button type="button" :class="{ 'is-active': editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
            </button>
            <button type="button" :class="{ 'is-active': editor.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="2" y="8" fill="currentColor" stroke="none" font-size="7" font-weight="600">1</text><text x="2" y="14" fill="currentColor" stroke="none" font-size="7" font-weight="600">2</text><text x="2" y="20" fill="currentColor" stroke="none" font-size="7" font-weight="600">3</text></svg>
            </button>
            <span class="tiptap-toolbar__sep"></span>
            <button type="button" :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
              H2
            </button>
            <button type="button" :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">
              H3
            </button>
          </div>
          <EditorContent :editor="editor" class="tiptap-content" />
        </div>
      </div>
    </div>
  </div>
</template>