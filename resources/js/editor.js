const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const hasFiles = (event) => Array.from(event.dataTransfer?.types ?? []).includes('Files');

/**
 * Tiny Alpine component registration for the rich-text field. TipTap itself is
 * imported dynamically in init(), so the ~400 kB editor chunk is only fetched
 * on pages where an editor is actually rendered.
 *
 * The TipTap instance is stored on the DOM node, not in Alpine state: Alpine's
 * reactive proxy would wrap ProseMirror and break its transactions.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('richEditor', (config) => ({
        error: '',
        busy: false,
        dragging: false,
        uploadedIds: [],
        syncFromServer: null,
        destroyed: false,

        async init() {
            const surface = this.$refs.editor;
            this.uploadedIds = (this.$refs.attachments?.value ?? '')
                .split(',')
                .map(Number)
                .filter((id) => id > 0);

            this.form = this.$el.closest('form');
            this.form?.addEventListener('submit', () => this.flush(), true);

            const { createEditor } = await import('./editor-core');
            if (this.destroyed || !surface.isConnected) return;

            const editor = createEditor({
                element: surface,
                content: this.$refs.input?.value || config.value || '',
                placeholder: config.placeholder ?? '',
                label: config.label ?? '',
                onUpdate: () => this.sync(),
                onBlur: () => this.flush(),
            });
            surface._tiptap = editor;

            // Canned replies and post-submit resets change the Livewire property.
            this.syncFromServer = () => {
                const html = this.$refs.input?.value ?? '';
                const current = editor.isEmpty ? '' : editor.getHTML();
                if (html !== current) {
                    editor.commands.setContent(html, { emitUpdate: false });
                }
            };
            window.addEventListener('livewire:update', this.syncFromServer);

            surface.addEventListener('dragover', (event) => {
                if (!hasFiles(event)) return;
                event.preventDefault();
                this.dragging = true;
            });
            surface.addEventListener('dragleave', () => { this.dragging = false; });
            surface.addEventListener('drop', (event) => {
                if (!hasFiles(event)) return;
                event.preventDefault();
                this.dragging = false;
                this.uploadFiles(event.dataTransfer.files);
            });
            surface.addEventListener('paste', (event) => {
                const files = Array.from(event.clipboardData?.files ?? []);
                if (files.length === 0) return;
                event.preventDefault();
                this.uploadFiles(files);
            });
        },

        destroy() {
            this.destroyed = true;
            window.removeEventListener('livewire:update', this.syncFromServer);
            this.instance()?.destroy();
        },

        instance() {
            return this.$refs.editor?._tiptap ?? null;
        },

        isActive(name) {
            return this.instance()?.isActive(name) ?? false;
        },

        toggle(name) {
            this.instance()?.chain().focus()[`toggle${name}`]().run();
        },

        sync() {
            const editor = this.instance();
            if (!editor || !this.$refs.input) return;
            // An empty TipTap document is "<p></p>" — keep the field truly empty
            // so the server's `required` validation still rejects blank replies.
            this.$refs.input.value = editor.isEmpty ? '' : editor.getHTML();
        },

        flush() {
            this.sync();
            if (this.$refs.attachments) {
                this.$refs.attachments.value = this.uploadedIds.join(',');
            }
            for (const field of [this.$refs.input, this.$refs.attachments]) {
                if (field?.hasAttribute('wire:model')) {
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        },

        onPick(event) {
            this.uploadFiles(event.target.files);
            event.target.value = '';
        },

        async uploadFiles(fileList) {
            const editor = this.instance();
            const files = Array.from(fileList ?? []);
            if (files.length === 0) return;
            if (!editor) {
                this.error = config.editorLoading;
                return;
            }

            this.busy = true;
            this.error = '';

            for (const file of files) {
                try {
                    const payload = new FormData();
                    payload.append('file', file);

                    const response = await fetch(config.uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            Accept: 'application/json',
                        },
                        body: payload,
                    });

                    if (!response.ok) {
                        const body = await response.json().catch(() => ({}));
                        throw new Error(body.errors?.file?.[0] ?? body.message ?? config.uploadFailed);
                    }

                    const uploaded = await response.json();
                    this.uploadedIds.push(uploaded.id);

                    if (uploaded.mime.startsWith('image/')) {
                        editor.chain().focus().setImage({ src: uploaded.url, alt: uploaded.name }).run();
                    } else {
                        editor.chain().focus().insertContent([
                            { type: 'text', text: `📎 ${uploaded.name}`, marks: [{ type: 'link', attrs: { href: uploaded.url } }] },
                            { type: 'text', text: ' ' },
                        ]).run();
                    }
                    this.sync();
                } catch (error) {
                    this.error = error.message || config.uploadFailed;
                }
            }

            this.busy = false;
            this.flush();
        },

        setLink() {
            const editor = this.instance();
            if (!editor) return;
            const previous = editor.getAttributes('link').href ?? '';
            const url = window.prompt(config.linkPrompt, previous || 'https://');
            if (url === null) return;
            if (url.trim() === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
        },
    }));
});
