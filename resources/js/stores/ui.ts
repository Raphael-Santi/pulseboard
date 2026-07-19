import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastTone = 'up' | 'down' | 'accent';

export interface Toast {
    id: number;
    message: string;
    tone: ToastTone;
}

export interface ConfirmRequest {
    title: string;
    body: string;
    confirmLabel: string;
}

export const useUiStore = defineStore('ui', () => {
    const toasts = ref<Toast[]>([]);
    let sequence = 0;

    function notify(message: string, tone: ToastTone = 'accent'): void {
        const id = ++sequence;
        toasts.value.push({ id, message, tone });
        setTimeout(() => {
            toasts.value = toasts.value.filter((toast) => toast.id !== id);
        }, 3400);
    }

    const request = ref<ConfirmRequest | null>(null);
    let resolver: ((confirmed: boolean) => void) | null = null;

    /** Show a confirmation dialog and resolve to the user's choice. */
    function confirm(options: {
        title: string;
        body: string;
        confirmLabel?: string;
    }): Promise<boolean> {
        request.value = {
            title: options.title,
            body: options.body,
            confirmLabel: options.confirmLabel ?? 'Удалить',
        };
        return new Promise((resolve) => {
            resolver = resolve;
        });
    }

    function resolve(confirmed: boolean): void {
        request.value = null;
        resolver?.(confirmed);
        resolver = null;
    }

    return { toasts, notify, request, confirm, resolve };
});
