<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bell, CheckCheck, Info, TriangleAlert } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import { index as notificationsIndex } from '@/routes/notifications';

interface Notification {
    id: number;
    title: string;
    body: string;
    type: 'info' | 'success' | 'warning';
    read: boolean;
    created_at: string;
}

const open = ref(false);
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);

async function fetchNotifications() {
    loading.value = true;
    try {
        const res = await fetch('/notifications', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } finally {
        loading.value = false;
    }
}

async function markAllRead() {
    await fetch('/notifications/read', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': getCsrf(),
        },
        body: JSON.stringify({ ids: [] }),
    });
    notifications.value = notifications.value.map((n) => ({
        ...n,
        read: true,
    }));
    unreadCount.value = 0;
}

function getCsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function toggle() {
    open.value = !open.value;
    if (open.value && notifications.value.length === 0) {
        fetchNotifications();
    }
}

function typeIcon(type: string) {
    if (type === 'warning') return TriangleAlert;
    if (type === 'success') return CheckCheck;
    return Info;
}

function typeColor(type: string) {
    if (type === 'warning') return 'text-amber-400';
    if (type === 'success') return 'text-emerald-400';
    return 'text-blue-400';
}

function typeBg(type: string) {
    if (type === 'warning') return 'bg-amber-400/15';
    if (type === 'success') return 'bg-emerald-400/15';
    return 'bg-blue-400/15';
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    return `${Math.floor(h / 24)}d ago`;
}

function closeOnOutside(e: MouseEvent) {
    if (!(e.target as Element).closest('[data-notification-bell]')) {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', closeOnOutside);
    fetchNotifications();
});
onUnmounted(() => document.removeEventListener('click', closeOnOutside));
</script>

<template>
    <div class="relative" data-notification-bell>
        <button
            @click="toggle"
            class="relative flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
        >
            <Bell class="h-4 w-4" />
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"
                >{{ unreadCount > 9 ? '9+' : unreadCount }}</span
            >
        </button>

        <Transition name="dropdown">
            <div
                v-if="open"
                class="absolute top-10 right-0 z-50 w-80 overflow-hidden rounded-xl border border-sidebar-border bg-card shadow-xl"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/50 px-4 py-3"
                >
                    <p class="text-sm font-semibold">Notifications</p>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-xs text-primary hover:text-primary/80"
                    >
                        Mark all read
                    </button>
                </div>

                <div
                    v-if="loading"
                    class="flex items-center justify-center py-10"
                >
                    <div
                        class="h-5 w-5 animate-spin rounded-full border-2 border-primary border-t-transparent"
                    />
                </div>

                <div
                    v-else-if="notifications.length === 0"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    No notifications
                </div>

                <div
                    v-else
                    class="max-h-72 divide-y divide-sidebar-border/30 overflow-y-auto"
                >
                    <div
                        v-for="n in notifications"
                        :key="n.id"
                        class="flex gap-3 px-4 py-3 transition-colors"
                        :class="n.read ? '' : 'bg-primary/5'"
                    >
                        <div
                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                            :class="typeBg(n.type)"
                        >
                            <component
                                :is="typeIcon(n.type)"
                                class="h-3.5 w-3.5"
                                :class="typeColor(n.type)"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p
                                    class="text-xs font-semibold"
                                    :class="
                                        n.read
                                            ? 'text-foreground/80'
                                            : 'text-foreground'
                                    "
                                >
                                    {{ n.title }}
                                </p>
                                <span
                                    v-if="!n.read"
                                    class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                                />
                            </div>
                            <p
                                class="mt-0.5 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{ n.body }}
                            </p>
                            <p
                                class="mt-1 text-[10px] text-muted-foreground/60"
                            >
                                {{ timeAgo(n.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-sidebar-border/50 px-4 py-2.5">
                    <button
                        @click="
                            router.visit(notificationsIndex().url);
                            open = false;
                        "
                        class="w-full text-center text-xs font-medium text-primary hover:text-primary/80"
                    >
                        View all notifications
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        opacity 0.15s,
        transform 0.15s;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.97);
}
</style>
