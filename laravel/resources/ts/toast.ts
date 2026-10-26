import { reactive } from 'vue';

export interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error';
}

export const toasts = reactive<Toast[]>([]);

let sequence = 0;

export function toast(message: string, type: Toast['type'] = 'success'): void {
    const id = ++sequence;
    toasts.push({ id, message, type });
    setTimeout(() => dismiss(id), 4000);
}

export function dismiss(id: number): void {
    const index = toasts.findIndex((item) => item.id === id);
    if (index !== -1) {
        toasts.splice(index, 1);
    }
}
