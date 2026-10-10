/**
 * Rich text editor for classic (non-Livewire) admin forms, used by the custom blog example.
 *
 * Usage: x-data="laraTiptapEditor()" on a wrapper with an x-ref="editor" element and the
 * form's own <textarea x-ref="textarea" hidden>. The textarea stays the form field: the editor
 * starts with its value and writes its HTML back on every change ('' when empty), so
 * validation errors and old() values work as with a plain textarea.
 */
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

function laraTiptapEditor() {
    // the editor stays outside Alpine's reactive state: a reactive proxy breaks ProseMirror
    let editor = null;

    return {
        // bumped on every transaction, so the toolbar's active states re-evaluate
        transactions: 0,

        init() {
            editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [2, 3] },
                        link: { openOnClick: false },
                    }),
                ],
                content: this.$refs.textarea.value,
                onTransaction: () => {
                    this.transactions++;
                },
                onUpdate: ({ editor }) => {
                    this.$refs.textarea.value = editor.isEmpty ? '' : editor.getHTML();
                },
            });
        },

        destroy() {
            editor?.destroy();
            editor = null;
        },

        isActive(name, attributes = {}) {
            this.transactions;

            return editor?.isActive(name, attributes) ?? false;
        },

        run(command, ...args) {
            editor?.chain().focus()[command](...args).run();
        },

        setLink() {
            if (!editor) {
                return;
            }

            const url = window.prompt('URL', editor.getAttributes('link').href ?? '');

            if (url === null) {
                return;
            }

            if (url === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();

                return;
            }

            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },
    };
}

function register() {
    window.Alpine.data('laraTiptapEditor', laraTiptapEditor);
}

if (window.Alpine) {
    register();
} else {
    document.addEventListener('alpine:init', register);
}
