<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, CreditCard, HandCoins, LayoutGrid, MapPin, MessageSquare, Monitor, Moon, Settings, Sun, Tag, Users, Wallet } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard } from '@/routes';
import { index as aiIndex } from '@/routes/ai';
import { analytics } from '@/routes';
import { subscription } from '@/routes';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as groupsIndex } from '@/routes/groups';
import { index as loansIndex } from '@/routes/loans';
import { index as tripsIndex } from '@/routes/trips';
import { index as walletsIndex } from '@/routes/wallets';
import { index as entitiesIndex } from '@/routes/entities';
import type { NavItem } from '@/types';

const page = usePage();
const { appearance, updateAppearance } = useAppearance();

const themeIcons = { light: Sun, dark: Moon, system: Monitor } as const;

function cycleTheme() {
    const order = ['light', 'dark', 'system'] as const;
    const next = order[(order.indexOf(appearance.value as typeof order[number]) + 1) % 3];
    updateAppearance(next);
}

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid, color: '#6366f1' },
    { title: 'Transactions', href: transactionsIndex(), icon: CreditCard, color: '#10b981' },
    { title: 'Wallets', href: walletsIndex(), icon: Wallet, color: '#f59e0b' },
    { title: 'Trips', href: tripsIndex(), icon: MapPin, color: '#f43f5e' },
    { title: 'Groups', href: groupsIndex(), icon: Users, color: '#f97316' },
    { title: 'Loans', href: loansIndex(), icon: HandCoins, color: '#06b6d4' },
    { title: 'Entities', href: entitiesIndex(), icon: Tag, color: '#f97316' },
    { title: 'Analytics', href: analytics(), icon: BarChart3, color: '#8b5cf6' },
    { title: 'AI Chat', href: aiIndex(), icon: MessageSquare, color: '#14b8a6' },
];

const footerNavItems: NavItem[] = [
    { title: 'Subscription', href: subscription(), icon: CreditCard, color: '#ec4899' },
    { title: 'Settings', href: '/settings/profile', icon: Settings, color: '#71717a' },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <div class="px-2 pb-1">
                <button
                    @click="cycleTheme"
                    class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <component :is="themeIcons[appearance as keyof typeof themeIcons] ?? Sun" class="h-3.5 w-3.5" />
                    <span class="capitalize">{{ appearance }} mode</span>
                </button>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
