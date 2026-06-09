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
    const editorOptions: Record<string, unknown> = {
        extensions: [
            StarterKit,
            Underline,
            Placeholder.configure({
                placeholder: options.placeholder ?? '',
            }),
        ],
        content: options.content ?? '',
    }
    if (options.onUpdate) {
        editorOptions.onUpdate = ({ editor: e }: { editor: Editor }) => options.onUpdate!(e.getHTML())
    }
    return useEditor(editorOptions as Parameters<typeof useEditor>[0])
}