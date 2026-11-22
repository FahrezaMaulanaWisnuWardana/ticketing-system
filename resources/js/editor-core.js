import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';

/**
 * TipTap is split out of the main bundle and loaded on demand by editor.js.
 * Only pages that actually render a rich-text field download this chunk.
 */
export function createEditor({ element, content, placeholder, label, onUpdate, onBlur }) {
    return new Editor({
        element,
        content,
        extensions: [
            StarterKit.configure({ heading: { levels: [2, 3] } }),
            Image,
            Placeholder.configure({ placeholder }),
        ],
        editorProps: {
            attributes: {
                class: 'rich-prose focus:outline-none',
                'aria-label': label,
            },
        },
        // TipTap v3 registers top-level onUpdate/onBlur callbacks.
        onUpdate,
        onBlur,
    });
}
