<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import { useI18n } from 'vue-i18n';

const model = defineModel({ type: String, default: '' });

defineProps({
    placeholder: { type: String, default: '' },
});

const { t } = useI18n();

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure({
            // Kept in lockstep with the server-side allow-list in RichText.php:
            // anything the toolbar can produce must survive sanitising, or the
            // user watches their formatting silently disappear on save.
            heading: { levels: [2, 3, 4] },
            underline: false,
            link: {
                openOnClick: false,
                autolink: true,
                HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' },
            },
        }),
        Image.configure({ inline: false }),
        Placeholder.configure({ placeholder: () => '' }),
    ],
    editorProps: {
        attributes: {
            class: 'prose-editor min-h-48 px-3 py-2 focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        model.value = editor.getHTML();
    },
});

// Only push external changes in — writing back what the editor just emitted
// would move the caret to the end on every keystroke.
watch(model, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value || '', { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

function toggleLink() {
    const previous = editor.value.getAttributes('link').href ?? '';
    const url = window.prompt(t('products.editor.linkPrompt'), previous);

    if (url === null) {
        return;
    }

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();

        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

function addImage() {
    const url = window.prompt(t('products.editor.imagePrompt'), '');

    if (url) {
        editor.value.chain().focus().setImage({ src: url }).run();
    }
}

const buttonClass =
    'rounded px-2 py-1 text-sm transition hover:bg-neutral-100 dark:hover:bg-neutral-800';
const activeClass = 'bg-neutral-200 dark:bg-neutral-700';
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border border-neutral-300 bg-white dark:border-neutral-700 dark:bg-neutral-900"
    >
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-0.5 border-b border-neutral-200 px-1.5 py-1 dark:border-neutral-800"
        >
            <button
                type="button"
                :class="[buttonClass, editor.isActive('bold') && activeClass]"
                :title="t('products.editor.bold')"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <strong>B</strong>
            </button>
            <button
                type="button"
                :class="[buttonClass, editor.isActive('italic') && activeClass]"
                :title="t('products.editor.italic')"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <em>I</em>
            </button>
            <button
                type="button"
                :class="[buttonClass, editor.isActive('strike') && activeClass]"
                :title="t('products.editor.strike')"
                @click="editor.chain().focus().toggleStrike().run()"
            >
                <s>S</s>
            </button>

            <span class="mx-1 h-4 w-px bg-neutral-200 dark:bg-neutral-800" />

            <button
                v-for="level in [2, 3, 4]"
                :key="level"
                type="button"
                :class="[buttonClass, editor.isActive('heading', { level }) && activeClass]"
                @click="editor.chain().focus().toggleHeading({ level }).run()"
            >
                H{{ level }}
            </button>

            <span class="mx-1 h-4 w-px bg-neutral-200 dark:bg-neutral-800" />

            <button
                type="button"
                :class="[buttonClass, editor.isActive('bulletList') && activeClass]"
                :title="t('products.editor.bulletList')"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                •
            </button>
            <button
                type="button"
                :class="[buttonClass, editor.isActive('orderedList') && activeClass]"
                :title="t('products.editor.orderedList')"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                1.
            </button>
            <button
                type="button"
                :class="[buttonClass, editor.isActive('blockquote') && activeClass]"
                :title="t('products.editor.quote')"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                &rdquo;
            </button>
            <button
                type="button"
                :class="[buttonClass, editor.isActive('codeBlock') && activeClass]"
                :title="t('products.editor.code')"
                @click="editor.chain().focus().toggleCodeBlock().run()"
            >
                &lt;/&gt;
            </button>

            <span class="mx-1 h-4 w-px bg-neutral-200 dark:bg-neutral-800" />

            <button
                type="button"
                :class="[buttonClass, editor.isActive('link') && activeClass]"
                :title="t('products.editor.link')"
                @click="toggleLink"
            >
                🔗
            </button>
            <button type="button" :class="buttonClass" :title="t('products.editor.image')" @click="addImage">
                🖼
            </button>

            <span class="mx-1 h-4 w-px bg-neutral-200 dark:bg-neutral-800" />

            <button
                type="button"
                :class="buttonClass"
                :disabled="!editor.can().undo()"
                :title="t('products.editor.undo')"
                @click="editor.chain().focus().undo().run()"
            >
                ↺
            </button>
            <button
                type="button"
                :class="buttonClass"
                :disabled="!editor.can().redo()"
                :title="t('products.editor.redo')"
                @click="editor.chain().focus().redo().run()"
            >
                ↻
            </button>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>

<style>
/* Scoped styles cannot reach TipTap's generated nodes, so these are global but
   namespaced under the editor's own class. */
.prose-editor p {
    margin: 0 0 0.75rem;
}
.prose-editor p:last-child {
    margin-bottom: 0;
}
.prose-editor h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1rem 0 0.5rem;
}
.prose-editor h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0.875rem 0 0.5rem;
}
.prose-editor h4 {
    font-weight: 600;
    margin: 0.75rem 0 0.5rem;
}
.prose-editor ul,
.prose-editor ol {
    padding-left: 1.5rem;
    margin: 0 0 0.75rem;
}
.prose-editor ul {
    list-style: disc;
}
.prose-editor ol {
    list-style: decimal;
}
.prose-editor blockquote {
    border-left: 3px solid currentColor;
    opacity: 0.75;
    padding-left: 0.75rem;
    margin: 0 0 0.75rem;
}
.prose-editor pre {
    background: rgb(0 0 0 / 0.05);
    border-radius: 0.375rem;
    padding: 0.75rem;
    overflow-x: auto;
    margin: 0 0 0.75rem;
}
@media (prefers-color-scheme: dark) {
    .prose-editor pre {
        background: rgb(255 255 255 / 0.06);
    }
}
.prose-editor a {
    color: #2a78d6;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.prose-editor img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}
.prose-editor hr {
    border: 0;
    border-top: 1px solid currentColor;
    opacity: 0.2;
    margin: 1rem 0;
}
</style>
