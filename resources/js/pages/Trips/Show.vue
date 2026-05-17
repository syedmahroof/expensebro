<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowLeft, ArrowUpRight, CalendarDays, Check, MapPin, TrendingDown, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { dashboard } from '@/routes';
import { index as tripsIndex, update as tripUpdate, destroy as tripDestroy } from '@/routes/trips';

const { confirm } = useConfirm();

interface Trip {
    id: number;
    name: string;
    destination: string | null;
    budget: string | null;
    currency: string;
    start_date: string | null;
    end_date: string | null;
    status: 'active' | 'completed' | 'archived';
    notes: string | null;
}

interface Transaction {
    id: number;
    description: string | null;
    amount: number;
    currency: string;
    type: string;
    date: string;
    location: string | null;
    category: { name: string; color: string } | null;
    merchant: { name: string } | null;
    wallet: { name: string } | null;
}

interface DailySpend {
    date: string;
    label: string;
    amount: number;
}

interface CategoryBreakdown {
    category_id: number | null;
    name: string;
    color: string;
    amount: number;
    count: number;
    percentage: number;
}

interface LocationHotspot {
    location: string;
    amount: number;
    count: number;
}

interface Stats {
    totalSpent: number;
    totalIncome: number;
    budget: number | null;
    remaining: number | null;
    dailySafeSpend: number;
    transactionCount: number;
    daysActive: number;
}

const props = defineProps<{
    trip: Trip;
    transactions: Transaction[];
    stats: Stats;
    dailySpend: DailySpend[];
    categoryBreakdown: CategoryBreakdown[];
    locationHotspots: LocationHotspot[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Trips', href: tripsIndex() },
            { title: 'Trip Detail', href: '#' },
        ],
    },
});

const maxDailySpend = computed(() => Math.max(...props.dailySpend.map((d) => d.amount), 1));

const budgetPercent = computed(() => {
    if (!props.stats.budget) { return null; }
    return Math.min(100, Math.round((props.stats.totalSpent / props.stats.budget) * 100));
});

const statusForm = useForm({ status: props.trip.status });

function markCompleted() {
    statusForm.status = 'completed';
    statusForm.put(tripUpdate(props.trip.id).url);
}

async function deleteTrip() {
    const ok = await confirm('All expense tags will be unlinked. This cannot be undone.', {
        title: 'Delete this trip?',
        confirmText: 'Delete Trip',
    });
    if (!ok) { return; }
    router.delete(tripDestroy(props.trip.id).url);
}

function fmt(n: number, currency = props.trip.currency) {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency, maximumFractionDigits: 0 }).format(n);
}

function formatDate(d: string | null) {
    if (!d) { return '?'; }
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head :title="trip.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">

        <!-- Back + header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Link :href="tripsIndex()" class="mb-2 inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground">
                    <ArrowLeft class="h-3.5 w-3.5" /> All Trips
                </Link>
                <h1 class="text-2xl font-bold tracking-tight">{{ trip.name }}</h1>
                <div v-if="trip.destination" class="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                    <MapPin class="h-4 w-4 shrink-0" />
                    <span>{{ trip.destination }}</span>
                </div>
                <div v-if="trip.start_date || trip.end_date" class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                    <span>{{ formatDate(trip.start_date) }} — {{ formatDate(trip.end_date) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button
                    v-if="trip.status === 'active'"
                    @click="markCompleted"
                    class="flex items-center gap-1.5 rounded-xl border border-sidebar-border/50 px-3 py-2 text-xs font-semibold transition-colors hover:bg-accent"
                >
                    <Check class="h-3.5 w-3.5 text-emerald-400" /> Mark Completed
                </button>
                <button
                    @click="deleteTrip"
                    class="flex items-center gap-1.5 rounded-xl border border-red-500/20 bg-red-500/5 px-3 py-2 text-xs font-semibold text-red-400 transition-colors hover:bg-red-500/10"
                >
                    Delete
                </button>
            </div>
        </div>

        <!-- Stats cards -->
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Total Spent</p>
                <p class="mt-1.5 text-xl font-bold text-red-400">{{ fmt(stats.totalSpent) }}</p>
            </div>
            <div v-if="stats.budget" class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Remaining</p>
                <p class="mt-1.5 text-xl font-bold" :class="(stats.remaining ?? 0) <= 0 ? 'text-red-400' : 'text-emerald-400'">
                    {{ fmt(stats.remaining ?? 0) }}
                </p>
            </div>
            <div v-if="stats.dailySafeSpend > 0" class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Daily Safe Spend</p>
                <p class="mt-1.5 text-xl font-bold text-primary">{{ fmt(stats.dailySafeSpend) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Transactions</p>
                <p class="mt-1.5 text-xl font-bold">{{ stats.transactionCount }}</p>
            </div>
        </div>

        <!-- Budget progress -->
        <div v-if="stats.budget && budgetPercent !== null" class="rounded-xl border border-sidebar-border/50 bg-card p-5">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm font-semibold">Budget</p>
                <span class="text-sm font-medium">
                    {{ fmt(stats.totalSpent) }} <span class="text-muted-foreground">/ {{ fmt(stats.budget) }}</span>
                </span>
            </div>
            <div class="h-2.5 rounded-full bg-sidebar-border/40">
                <div
                    class="h-2.5 rounded-full transition-all"
                    :class="budgetPercent >= 90 ? 'bg-red-400' : budgetPercent >= 70 ? 'bg-amber-400' : 'bg-primary'"
                    :style="{ width: `${budgetPercent}%` }"
                />
            </div>
            <p class="mt-2 text-xs text-muted-foreground">{{ budgetPercent }}% of budget used · {{ stats.daysActive }} day{{ stats.daysActive !== 1 ? 's' : '' }}</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            <!-- Daily Spend Chart -->
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <h2 class="mb-5 text-sm font-semibold">Daily Spending</h2>
                <div v-if="dailySpend.length === 0" class="py-8 text-center text-sm text-muted-foreground">No daily data</div>
                <div v-else class="flex items-end gap-1.5 overflow-x-auto" style="height: 130px">
                    <div
                        v-for="day in dailySpend"
                        :key="day.date"
                        class="flex min-w-[28px] flex-1 flex-col items-center gap-1"
                    >
                        <div class="flex w-full items-end justify-center" style="height: 100px">
                            <div
                                class="w-full rounded-t bg-primary/70 transition-all"
                                :style="{ height: `${Math.max(4, (day.amount / maxDailySpend) * 100)}%` }"
                                :title="`${day.label}: ${fmt(day.amount)}`"
                            />
                        </div>
                        <span class="text-[9px] text-muted-foreground whitespace-nowrap">{{ day.label }}</span>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <h2 class="mb-4 text-sm font-semibold">Spending by Category</h2>
                <div v-if="categoryBreakdown.length === 0" class="py-8 text-center text-sm text-muted-foreground">No category data</div>
                <div v-else class="space-y-3">
                    <div v-for="item in categoryBreakdown" :key="String(item.category_id)" class="flex items-center gap-3">
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                            :style="{ backgroundColor: item.color + '25', color: item.color }"
                        >
                            {{ item.name[0] }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="truncate font-medium">{{ item.name }}</span>
                                <span class="ml-2 shrink-0 text-muted-foreground">{{ fmt(item.amount) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-sidebar-border/40">
                                <div class="h-1.5 rounded-full" :style="{ backgroundColor: item.color, width: `${item.percentage}%` }" />
                            </div>
                        </div>
                        <span class="shrink-0 text-xs text-muted-foreground">{{ item.percentage }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Hotspots -->
        <div v-if="locationHotspots.length > 0" class="rounded-xl border border-sidebar-border/50 bg-card p-5">
            <h2 class="mb-4 text-sm font-semibold">Location Hotspots</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(spot, i) in locationHotspots"
                    :key="spot.location"
                    class="flex items-center gap-3 rounded-xl border border-sidebar-border/30 p-3"
                >
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                        {{ i + 1 }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ spot.location }}</p>
                        <p class="text-xs text-muted-foreground">{{ spot.count }} transaction{{ spot.count !== 1 ? 's' : '' }}</p>
                    </div>
                    <p class="shrink-0 text-sm font-semibold text-red-400">{{ fmt(spot.amount) }}</p>
                </div>
            </div>
        </div>

        <!-- Transactions list -->
        <div>
            <h2 class="mb-3 text-sm font-semibold">Transactions</h2>
            <div v-if="transactions.length === 0" class="rounded-xl border border-dashed border-sidebar-border/50 p-8 text-center text-sm text-muted-foreground">
                No transactions tagged to this trip yet.
            </div>
            <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/50">
                <div class="divide-y divide-sidebar-border/30">
                    <div
                        v-for="tx in transactions"
                        :key="tx.id"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-accent/30 transition-colors"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                            :style="{ backgroundColor: (tx.category?.color ?? '#6b7280') + '20' }"
                        >
                            <ArrowDownRight v-if="tx.type === 'expense'" class="h-4 w-4 text-red-400" />
                            <ArrowUpRight v-else class="h-4 w-4 text-emerald-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ tx.description || tx.category?.name || 'Transaction' }}</p>
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <span>{{ new Date(tx.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</span>
                                <span v-if="tx.location" class="flex items-center gap-0.5">
                                    <MapPin class="h-2.5 w-2.5" /> {{ tx.location }}
                                </span>
                                <span v-if="tx.wallet">{{ tx.wallet.name }}</span>
                            </div>
                        </div>
                        <span
                            class="shrink-0 text-sm font-semibold tabular-nums"
                            :class="tx.type === 'expense' ? 'text-red-400' : 'text-emerald-400'"
                        >
                            {{ tx.type === 'expense' ? '-' : '+' }}{{ fmt(tx.amount, tx.currency) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
