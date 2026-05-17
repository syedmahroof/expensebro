<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, CreditCard, LayoutGrid, MapPin, Users, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import { analytics, dashboard } from '@/routes';
import { index as groupsIndex } from '@/routes/groups';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as tripsIndex } from '@/routes/trips';
import { index as walletsIndex } from '@/routes/wallets';

const page = usePage();
const current = computed(() => page.url);

function isActive(href: string | { url: string }) {
    const url = typeof href === 'string' ? href : href.url;
    return current.value.startsWith(url.split('?')[0]);
}

const items = [
    { label: 'Home', href: dashboard(), icon: LayoutGrid, color: '#6366f1' },
    { label: 'Spend', href: transactionsIndex(), icon: CreditCard, color: '#10b981' },
    { label: 'Wallets', href: walletsIndex(), icon: Wallet, color: '#f59e0b' },
    { label: 'Trips', href: tripsIndex(), icon: MapPin, color: '#f43f5e' },
    { label: 'Groups', href: groupsIndex(), icon: Users, color: '#f97316' },
    { label: 'Analytics', href: analytics(), icon: BarChart3, color: '#8b5cf6' },
];
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-40 flex items-center border-t border-sidebar-border/70 bg-sidebar md:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
        <Link
            v-for="item in items"
            :key="typeof item.href === 'string' ? item.href : item.href.url"
            :href="item.href"
            class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium transition-colors"
            :class="isActive(item.href)
                ? 'text-primary'
                : 'text-sidebar-foreground/50 hover:text-sidebar-foreground'"
        >
            <component :is="item.icon" class="h-5 w-5" :style="{ color: item.color }" :class="{ 'opacity-100': isActive(item.href), 'opacity-60': !isActive(item.href) }" />
            {{ item.label }}
        </Link>
    </nav>
</template>
