<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { BarChart3, TrendingDown, TrendingUp, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import { analytics } from '@/routes';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Analytics', href: analytics() },
        ],
    },
});

const props = defineProps<{
    monthlyTrend: Array<{
        month: string;
        year: number;
        expenses: number;
        income: number;
    }>;
    categoryBreakdown: Array<{
        category_id: number | null;
        total: string;
        count: number;
        category: { id: number; name: string; color: string } | null;
    }>;
    walletBreakdown: Array<{
        id: number;
        name: string;
        balance: number;
        currency: string;
        color: string;
        type: string;
    }>;
    topMerchants: Array<{
        merchant_id: number | null;
        total: string;
        count: number;
        merchant: { id: number; name: string } | null;
    }>;
    stats: {
        thisMonth: number;
        lastMonth: number;
        change: number;
        totalTransactions: number;
    };
}>();

const page = usePage<{ userCurrency: string }>();
const currency = computed(() => page.props.userCurrency ?? 'PKR');

const maxMonthlyExpense = computed(() =>
    Math.max(...props.monthlyTrend.map((m) => m.expenses), 1),
);
const maxMonthlyIncome = computed(() =>
    Math.max(...props.monthlyTrend.map((m) => m.income), 1),
);
const maxBar = computed(() =>
    Math.max(maxMonthlyExpense.value, maxMonthlyIncome.value),
);

const totalCategoryAmount = computed(
    () => props.categoryBreakdown.reduce((s, c) => s + Number(c.total), 0) || 1,
);
const totalWalletBalance = computed(
    () => props.walletBreakdown.reduce((s, w) => s + Number(w.balance), 0) || 1,
);

function fmt(n: number, cur?: string) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: cur ?? currency.value,
        maximumFractionDigits: 0,
    }).format(n);
}
</script>

<template>
    <Head title="Analytics" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <!-- Stats row -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs text-muted-foreground"
                        >This Month</span
                    >
                    <TrendingDown class="h-4 w-4 text-red-400" />
                </div>
                <p class="text-xl font-bold text-red-400">
                    {{ fmt(stats.thisMonth) }}
                </p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs text-muted-foreground"
                        >Last Month</span
                    >
                    <TrendingDown class="h-4 w-4 text-muted-foreground" />
                </div>
                <p class="text-xl font-bold">{{ fmt(stats.lastMonth) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">Change</span>
                    <TrendingUp
                        class="h-4 w-4"
                        :class="
                            stats.change > 0
                                ? 'text-red-400'
                                : 'text-emerald-400'
                        "
                    />
                </div>
                <p
                    class="text-xl font-bold"
                    :class="
                        stats.change > 0 ? 'text-red-400' : 'text-emerald-400'
                    "
                >
                    {{ stats.change > 0 ? '+' : '' }}{{ stats.change }}%
                </p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs text-muted-foreground"
                        >Total Transactions</span
                    >
                    <BarChart3 class="h-4 w-4 text-primary" />
                </div>
                <p class="text-xl font-bold text-primary">
                    {{ stats.totalTransactions }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Monthly Bar Chart -->
            <div
                class="rounded-xl border border-sidebar-border/50 bg-card p-5 lg:col-span-2"
            >
                <h2 class="mb-5 text-sm font-semibold">6-Month Trend</h2>
                <div class="flex h-40 items-end gap-3">
                    <div
                        v-for="m in monthlyTrend"
                        :key="m.month + m.year"
                        class="flex flex-1 flex-col items-center gap-1"
                    >
                        <div
                            class="flex w-full items-end justify-center gap-0.5"
                            style="height: 120px"
                        >
                            <div
                                class="w-3 rounded-t bg-red-400/70 transition-all"
                                :style="{
                                    height:
                                        maxBar > 0
                                            ? `${(m.expenses / maxBar) * 100}%`
                                            : '4px',
                                    minHeight: m.expenses > 0 ? '4px' : '0',
                                }"
                            />
                            <div
                                class="w-3 rounded-t bg-emerald-400/70 transition-all"
                                :style="{
                                    height:
                                        maxBar > 0
                                            ? `${(m.income / maxBar) * 100}%`
                                            : '4px',
                                    minHeight: m.income > 0 ? '4px' : '0',
                                }"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">{{
                            m.month
                        }}</span>
                    </div>
                </div>
                <div
                    class="mt-4 flex items-center gap-4 text-xs text-muted-foreground"
                >
                    <span class="flex items-center gap-1.5"
                        ><span class="h-2 w-2 rounded-full bg-red-400/70" />
                        Expenses</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><span class="h-2 w-2 rounded-full bg-emerald-400/70" />
                        Income</span
                    >
                </div>
            </div>

            <!-- Wallet Breakdown -->
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <h2 class="mb-4 text-sm font-semibold">Wallet Distribution</h2>
                <div
                    v-if="walletBreakdown.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No wallets
                </div>
                <div v-else class="space-y-3">
                    <div v-for="w in walletBreakdown" :key="w.id">
                        <div
                            class="mb-1 flex items-center justify-between text-xs"
                        >
                            <span class="font-medium">{{ w.name }}</span>
                            <span class="text-muted-foreground">{{
                                fmt(w.balance)
                            }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-sidebar-border/40">
                            <div
                                class="h-1.5 rounded-full transition-all"
                                :style="{
                                    backgroundColor: w.color,
                                    width: `${Math.max(2, (w.balance / totalWalletBalance) * 100)}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Category Breakdown -->
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <h2 class="mb-4 text-sm font-semibold">
                    Spending by Category
                    <span class="ml-1 text-xs font-normal text-muted-foreground"
                        >(this month)</span
                    >
                </h2>
                <div
                    v-if="categoryBreakdown.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No expense data this month
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="item in categoryBreakdown"
                        :key="String(item.category_id)"
                        class="flex items-center gap-3"
                    >
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                            :style="{
                                backgroundColor:
                                    (item.category?.color ?? '#6b7280') + '25',
                                color: item.category?.color ?? '#6b7280',
                            }"
                        >
                            {{ (item.category?.name ?? 'Other')[0] }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="truncate font-medium">{{
                                    item.category?.name ?? 'Uncategorized'
                                }}</span>
                                <span
                                    class="ml-2 shrink-0 text-muted-foreground"
                                    >{{ fmt(Number(item.total)) }}</span
                                >
                            </div>
                            <div
                                class="h-1.5 rounded-full bg-sidebar-border/40"
                            >
                                <div
                                    class="h-1.5 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            item.category?.color ?? '#6b7280',
                                        width: `${(Number(item.total) / totalCategoryAmount) * 100}%`,
                                    }"
                                />
                            </div>
                        </div>
                        <span class="shrink-0 text-xs text-muted-foreground"
                            >{{
                                Math.round(
                                    (Number(item.total) / totalCategoryAmount) *
                                        100,
                                )
                            }}%</span
                        >
                    </div>
                </div>
            </div>

            <!-- Top Merchants -->
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-5">
                <h2 class="mb-4 text-sm font-semibold">Top Merchants</h2>
                <div
                    v-if="topMerchants.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No merchant data yet
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="(item, i) in topMerchants"
                        :key="String(item.merchant_id)"
                        class="flex items-center gap-3"
                    >
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary"
                        >
                            {{ i + 1 }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ item.merchant?.name ?? 'Unknown' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.count }} transaction{{
                                    item.count !== 1 ? 's' : ''
                                }}
                            </p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-red-400">
                            {{ fmt(Number(item.total)) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
