<script setup lang="ts">
import { Moon, Search, Sun, SunMoon } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), {
    breadcrumbs: () => [],
});

const { appearance, updateAppearance } = useAppearance();
const page = usePage();
const auth = computed(() => page.props.auth);

function cycleTheme() {
    const order = ['light', 'dark', 'system'] as const;
    const next =
        order[
            (order.indexOf(appearance.value as (typeof order)[number]) + 1) % 3
        ];
    updateAppearance(next);
}

const themeIcons = { light: Sun, dark: Moon, system: SunMoon } as const;

const showSearch = ref(false);

function onKeydown(e: KeyboardEvent) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        showSearch.value = true;
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <header
        class="flex h-14 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-1">
            <!-- Global search pill (desktop) -->
            <button
                @click="showSearch = true"
                class="hidden items-center gap-2 rounded-lg border border-sidebar-border/60 bg-sidebar-accent/30 px-3 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground sm:flex"
            >
                <Search class="h-3.5 w-3.5" />
                <span>Search…</span>
                <kbd
                    class="ml-2 rounded border border-sidebar-border px-1 py-0.5 font-mono text-[10px]"
                    >⌘K</kbd
                >
            </button>
            <!-- Search icon (mobile) -->
            <button
                @click="showSearch = true"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-foreground sm:hidden"
            >
                <Search class="h-4 w-4" />
            </button>

            <!-- Theme toggle -->
            <button
                @click="cycleTheme"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                :title="`Theme: ${appearance}`"
            >
                <component
                    :is="
                        themeIcons[appearance as keyof typeof themeIcons] ??
                        SunMoon
                    "
                    class="h-4 w-4"
                />
            </button>

            <!-- Notification bell -->
            <NotificationBell />

            <!-- User Profile Dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger :as-child="true">
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    >
                        <Avatar class="h-6 w-6 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="auth.user.avatar"
                                :src="auth.user.avatar"
                                :alt="auth.user.name"
                            />
                            <AvatarFallback
                                class="rounded-full bg-neutral-200 text-[10px] font-semibold text-black dark:bg-neutral-700 dark:text-white"
                            >
                                {{ getInitials(auth.user?.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="mt-2 w-56">
                    <UserMenuContent :user="auth.user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>

    <Teleport to="body">
        <GlobalSearch v-if="showSearch" @close="showSearch = false" />
    </Teleport>
</template>
