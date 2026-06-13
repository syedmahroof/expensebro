<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Bell, CheckCheck, Info, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import {
    index as notificationsIndex,
    read as markRead,
} from '@/routes/notifications';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Notifications', href: notificationsIndex() },
        ],
    },
});

interface Notification {
    id: number;
    title: string;
    body: string;
    type: 'info' | 'success' | 'warning';
    read: boolean;
    created_at: string;
}

interface PaginatedNotifications {
    data: Notification[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    notifications: PaginatedNotifications;
    unreadCount: number;
}>();

const hasUnread = computed(() => props.unreadCount > 0);

function markAllRead() {
    router.post(markRead().url, { ids: [] }, { preserveScroll: true });
}

function typeIcon(type: string) {
    if (type === 'warning') {
        return TriangleAlert;
    }
    if (type === 'success') {
        return CheckCheck;
    }
    return Info;
}

function typeColor(type: string) {
    if (type === 'warning') {
        return 'text-amber-400';
    }
    if (type === 'success') {
        return 'text-emerald-400';
    }
    return 'text-blue-400';
}

function typeBg(type: string) {
    if (type === 'warning') {
        return 'bg-amber-400/15 border-amber-500/20';
    }
    if (type === 'success') {
        return 'bg-emerald-400/15 border-emerald-500/20';
    }
    return 'bg-blue-400/15 border-blue-500/20';
}

function typeBadge(type: string) {
    if (type === 'warning') {
        return 'bg-amber-500/15 text-amber-400';
    }
    if (type === 'success') {
        return 'bg-emerald-500/15 text-emerald-400';
    }
    return 'bg-blue-500/15 text-blue-400';
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 1) {
        return 'just now';
    }
    if (m < 60) {
        return `${m}m ago`;
    }
    const h = Math.floor(m / 60);
    if (h < 24) {
        return `${h}h ago`;
    }
    const d = Math.floor(h / 24);
    if (d < 7) {
        return `${d}d ago`;
    }
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Notifications" />

    <div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight">Notifications</h1>
                <p class="text-sm text-muted-foreground">
                    {{
                        unreadCount > 0
                            ? `${unreadCount} unread`
                            : 'All caught up'
                    }}
                    · {{ notifications.total }} total
                </p>
            </div>
            <button
                v-if="hasUnread"
                @click="markAllRead"
                class="flex items-center gap-1.5 rounded-xl border border-sidebar-border/50 px-3 py-2 text-xs font-semibold transition-colors hover:bg-accent"
            >
                <CheckCheck class="h-3.5 w-3.5 text-emerald-400" /> Mark all
                read
            </button>
        </div>

        <!-- Summary cards -->
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-4">
                <p class="text-xs text-muted-foreground">Unread</p>
                <p class="mt-1.5 text-xl font-bold text-blue-400">
                    {{ unreadCount }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    notifications
                </p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Total</p>
                <p class="mt-1.5 text-xl font-bold">
                    {{ notifications.total }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">all time</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">This Page</p>
                <p class="mt-1.5 text-xl font-bold">
                    {{ notifications.data.length }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    of {{ notifications.per_page }} per page
                </p>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="notifications.data.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 py-16 text-center"
        >
            <Bell class="mb-3 h-10 w-10 text-muted-foreground/30" />
            <p class="font-medium text-muted-foreground">
                No notifications yet
            </p>
            <p class="mt-1 text-sm text-muted-foreground/60">
                You're all caught up!
            </p>
        </div>

        <!-- Notification list -->
        <div v-else class="space-y-2">
            <div
                v-for="n in notifications.data"
                :key="n.id"
                class="flex gap-4 rounded-xl border bg-card p-4 transition-all"
                :class="
                    n.read
                        ? 'border-sidebar-border/40 opacity-80'
                        : 'border-sidebar-border/70'
                "
            >
                <!-- Icon -->
                <div
                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border"
                    :class="typeBg(n.type)"
                >
                    <component
                        :is="typeIcon(n.type)"
                        class="h-4.5 w-4.5"
                        :class="typeColor(n.type)"
                    />
                </div>

                <!-- Content -->
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start gap-2">
                        <p
                            class="flex-1 text-sm font-semibold"
                            :class="
                                n.read
                                    ? 'text-foreground/70'
                                    : 'text-foreground'
                            "
                        >
                            {{ n.title }}
                        </p>
                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                :class="typeBadge(n.type)"
                                >{{ n.type }}</span
                            >
                            <span
                                v-if="!n.read"
                                class="h-2 w-2 rounded-full bg-primary"
                            />
                        </div>
                    </div>
                    <p
                        class="mt-1 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ n.body }}
                    </p>
                    <p class="mt-2 text-[11px] text-muted-foreground/50">
                        {{ timeAgo(n.created_at) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="notifications.last_page > 1"
            class="flex items-center justify-center gap-1.5 pt-2"
        >
            <button
                @click="goToPage(notifications.prev_page_url)"
                :disabled="!notifications.prev_page_url"
                class="rounded-lg border border-sidebar-border/50 px-3 py-1.5 text-xs font-medium transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40"
            >
                ← Prev
            </button>

            <template v-for="link in notifications.links" :key="link.label">
                <button
                    v-if="
                        link.url &&
                        !link.label.includes('Previous') &&
                        !link.label.includes('Next')
                    "
                    @click="goToPage(link.url)"
                    class="min-w-[32px] rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors"
                    :class="
                        link.active
                            ? 'border-primary/50 bg-primary/10 text-primary'
                            : 'border-sidebar-border/50 hover:bg-accent'
                    "
                >
                    {{ link.label }}
                </button>
            </template>

            <button
                @click="goToPage(notifications.next_page_url)"
                :disabled="!notifications.next_page_url"
                class="rounded-lg border border-sidebar-border/50 px-3 py-1.5 text-xs font-medium transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40"
            >
                Next →
            </button>
        </div>
    </div>
</template>
