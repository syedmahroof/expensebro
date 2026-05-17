<script setup lang="ts">
import { AlertTriangle, HelpCircle, X } from 'lucide-vue-next';
import { useConfirm } from '@/composables/useConfirm';

const { state, handleConfirm, handleCancel } = useConfirm();

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') { handleCancel(); }
    if (e.key === 'Enter') { handleConfirm(); }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="state.open"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click.self="handleCancel"
                @keydown="onKeydown"
                tabindex="-1"
            >
                <Transition
                    enter-active-class="duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="duration-100 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="state.open"
                        class="w-full max-w-sm overflow-hidden rounded-2xl border border-sidebar-border bg-card shadow-2xl"
                    >
                        <!-- Header -->
                        <div class="flex items-start gap-3 p-5 pb-4">
                            <div
                                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="state.danger ? 'bg-red-500/15' : 'bg-blue-500/15'"
                            >
                                <AlertTriangle v-if="state.danger" class="h-4.5 w-4.5 text-red-400" />
                                <HelpCircle v-else class="h-4.5 w-4.5 text-blue-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold leading-tight">{{ state.title }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ state.message }}</p>
                            </div>
                            <button
                                @click="handleCancel"
                                class="-mr-1 -mt-1 shrink-0 rounded-lg p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2.5 border-t border-sidebar-border/50 px-5 py-4">
                            <button
                                type="button"
                                @click="handleCancel"
                                class="flex-1 rounded-xl border border-sidebar-border/50 py-2.5 text-sm font-semibold transition-colors hover:bg-accent"
                            >
                                {{ state.cancelText }}
                            </button>
                            <button
                                type="button"
                                @click="handleConfirm"
                                class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white shadow-md transition-colors"
                                :class="state.danger
                                    ? 'bg-red-600 shadow-red-500/20 hover:bg-red-500'
                                    : 'bg-primary shadow-primary/20 hover:bg-primary/90'"
                            >
                                {{ state.confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
