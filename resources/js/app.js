import './bootstrap';
import '../css/gantt.css';
import './editor';

// Heavy, page-specific libraries are fetched only where they're used.
window.loadSortable = () => import('sortablejs').then((module) => module.default);
window.loadGantt = () => import('frappe-gantt').then((module) => module.default);

// Livewire 3 bundles Alpine. Importing a second Alpine copy and calling
// Alpine.start() races Livewire's component registration, breaking @entangle.
document.addEventListener('alpine:init', () => {
    window.Alpine.directive('shortcut', (el, { expression }, { cleanup }) => {
        const handler = (event) => {
            const target = event.target;
            if (target instanceof HTMLElement && target.closest('input, textarea, select, [contenteditable="true"]')) {
                return;
            }
            if (event.key.toLowerCase() === expression.toLowerCase() && !event.metaKey && !event.ctrlKey && !event.altKey) {
                event.preventDefault();
                document.documentElement.dataset.keyboard = 'true';
                setTimeout(() => { delete document.documentElement.dataset.keyboard; }, 0);
                if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
                    el.focus();
                } else {
                    el.click();
                }
            }
        };
        window.addEventListener('keydown', handler);
        cleanup(() => window.removeEventListener('keydown', handler));
    });

    // Portal home: debounced live search over request types and help articles.
    // The debounce itself is applied in the template with x-on:input.debounce.300ms.
    window.Alpine.data('portalSearch', (endpoint) => ({
        query: '',
        results: null,
        loading: false,
        open: false,
        controller: null,

        async run() {
            const value = this.query.trim();
            if (value === '') {
                this.clear();
                return;
            }

            this.open = true;
            this.loading = true;
            if (this.controller) this.controller.abort();
            this.controller = new AbortController();

            try {
                const response = await fetch(endpoint + '?q=' + encodeURIComponent(value), {
                    signal: this.controller.signal,
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('suggest failed');
                this.results = await response.json();
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.results = { types: [], articles: [] };
                }
            } finally {
                this.loading = false;
            }
        },

        clear() {
            this.query = '';
            this.results = null;
            this.loading = false;
            this.open = false;
            if (this.controller) this.controller.abort();
        },
    }));
});

if (document.querySelector('meta[name="echo-enabled"]')?.content === 'true') {
    // Broadcasting is optional; only pay for Echo/Pusher when it's enabled.
    Promise.all([import('laravel-echo'), import('pusher-js')]).then(([echoModule, pusherModule]) => {
        const Echo = echoModule.default;
        window.Pusher = pusherModule.default;
        const secure = window.location.protocol === 'https:';
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: document.querySelector('meta[name="reverb-key"]')?.content,
            wsHost: window.location.hostname,
            wsPort: secure ? 443 : Number(window.location.port || 80),
            wssPort: secure ? 443 : Number(window.location.port || 80),
            forceTLS: secure,
            enabledTransports: ['ws', 'wss'],
            authorizer: (channel) => ({
                authorize: async (socketId, callback) => {
                    try {
                        const response = await fetch('/broadcasting/auth', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                Accept: 'application/json',
                            },
                            body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
                        });
                        callback(false, await response.json());
                    } catch (error) {
                        callback(true, error);
                    }
                },
            }),
        });
        window.addEventListener('echo:reconnect', () => window.dispatchEvent(new CustomEvent('refresh-tickets')));
    });
}
