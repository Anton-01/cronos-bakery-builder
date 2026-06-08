import { useEditor, type Editor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Placeholder from '@tiptap/extension-placeholder'
import type { ShallowRef } from 'vue'

interface RichTextEditorOptions {
    placeholder?: string
    content?: string
    onUpdate?: (html: string) => void
}

export function useRichTextEditor(options: RichTextEditorOptions = {}): ShallowRef<Editor | undefined> {
    return useEditor({
        extensions: [
            StarterKit,
            Underline,
            Placeholder.configure({
                placeholder: options.placeholder ?? '',
            }),
        ],
        content: options.content ?? '',
        onUpdate: options.onUpdate
            ? ({ editor: e }) => options.onUpdate!(e.getHTML())
            : undefined,
    })
}