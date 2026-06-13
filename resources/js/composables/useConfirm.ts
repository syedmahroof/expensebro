import { ref } from 'vue';

export interface ConfirmOptions {
    title?: string;
    confirmText?: string;
    cancelText?: string;
    danger?: boolean;
}

interface ConfirmState {
    open: boolean;
    message: string;
    title: string;
    confirmText: string;
    cancelText: string;
    danger: boolean;
    resolve: ((value: boolean) => void) | null;
}

// Module-level singleton so any component can trigger and the dialog component reads it.
const state = ref<ConfirmState>({
    open: false,
    message: '',
    title: 'Confirm',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    danger: false,
    resolve: null,
});

export function useConfirm() {
    function confirm(
        message: string,
        options?: ConfirmOptions,
    ): Promise<boolean> {
        return new Promise((resolve) => {
            state.value = {
                open: true,
                message,
                title: options?.title ?? 'Confirm',
                confirmText: options?.confirmText ?? 'Confirm',
                cancelText: options?.cancelText ?? 'Cancel',
                danger: options?.danger ?? true,
                resolve,
            };
        });
    }

    function handleConfirm() {
        state.value.resolve?.(true);
        state.value.open = false;
    }

    function handleCancel() {
        state.value.resolve?.(false);
        state.value.open = false;
    }

    return { state, confirm, handleConfirm, handleCancel };
}
