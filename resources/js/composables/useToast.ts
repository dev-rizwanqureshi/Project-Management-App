import { readonly, ref } from 'vue';

export type ToastIntent = 'info' | 'success' | 'warning' | 'error';

export type ToastMessage = {
    id: string;
    message: string;
    intent: ToastIntent;
};

const messages = ref<ToastMessage[]>([]);

export function useToast() {
    const push = (message: string, intent: ToastIntent = 'info') => {
        const toast: ToastMessage = {
            id: crypto.randomUUID(),
            message,
            intent,
        };

        messages.value = [...messages.value, toast];

        return toast.id;
    };

    const remove = (id: string) => {
        messages.value = messages.value.filter((toast) => toast.id !== id);
    };

    return {
        messages: readonly(messages),
        push,
        remove,
    };
}
