import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import Sortable from 'sortablejs';
import Gantt from 'frappe-gantt';
import '../css/gantt.css';

window.Sortable = Sortable;
window.Gantt = Gantt;

window.Alpine = Alpine;
Alpine.plugin(focus);

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY ?? document.querySelector('meta[name="reverb-key"]')?.content,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
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

Alpine.directive('shortcut', (el, { expression }, { cleanup }) => {
    const handler = (event) => {
        const target = event.target;
        if (target instanceof HTMLElement && target.closest('input, textarea, select, [contenteditable="true"]')) return;
        if (event.key.toLowerCase() === expression.toLowerCase() && !event.metaKey && !event.ctrlKey && !event.altKey) {
            event.preventDefault();
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

Alpine.start();
