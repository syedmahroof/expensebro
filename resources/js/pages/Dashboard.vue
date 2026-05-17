<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle, ArrowDownLeft, ArrowDownRight, ArrowUpRight, Baby, Banknote, BarChart3, Bitcoin,
    Building, Building2, CalendarDays, Car, CreditCard, HandCoins, Home, MessageSquarePlus, PiggyBank,
    RefreshCw, Sparkles, Tag, TrendingDown, TrendingUp, Users, Wallet, Zap,
} from 'lucide-vue-next';
import { WhenVisible } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Category, Entity, EntityType, Transaction, Wallet as WalletType } from '@/types';
import { useI18n } from '@/composables/useI18n';
import { dashboard } from '@/routes';
import { index as aiIndex } from '@/routes/ai';
import { index as entitiesIndex } from '@/routes/entities';
import { index as loansIndex } from '@/routes/loans';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as walletsIndex } from '@/routes/wallets';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});

interface Stats {
    totalExpenses: number;
    totalIncome: number;
    balance: number;
    netWorth: number;
    savingsRate: number;
    avgDailySpend: number;
    txCount: number;
    expenseChange: number;
}

interface RecurringItem {
    key: string;
    name: string;
    type: string;
    interval: string;
    avg_amount: number;
    currency: string;
    next_date: string;
    is_overdue: boolean;
    days_until: number;
    category: { name: string; color: string } | null;
}

interface Insight {
    type: string;
    icon: string;
    title: string;
    description: string;
    severity: 'high' | 'medium' | 'info' | 'positive';
    value: number;
}

const props = defineProps<{
    wallets: WalletType[];
    recentTransactions: Transaction[];
    stats: Stats;
    categoryBreakdown: Array<{ category_id: number | null; total: string; category: Category | null }>;
    loans: { totalLent: number; totalBorrowed: number; netPosition: number; count: number };
    yesterday: { expenses: number; income: number; count: number; date: string };
    recurring?: RecurringItem[];
    insights?: Insight[];
    entityBreakdown?: Entity[];
}>();

const { t } = useI18n();
const page = usePage<{ userCurrency: string }>();
const currency = computed(() => page.props.userCurrency ?? 'PKR');

function fmt(amount: number, cur?: string) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: cur ?? currency.value,
        maximumFractionDigits: 0,
    }).format(amount);
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-PK', { month: 'short', day: 'numeric' });
}

const walletIcons: Record<string, typeof Wallet> = {
    cash: Banknote,
    bank: CreditCard,
    card: CreditCard,
    crypto: Bitcoin,
    other: Wallet,
};

const totalCategorySpend = computed(() =>
    props.categoryBreakdown.reduce((s, c) => s + Number(c.total), 0) || 1,
);

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
});

function insightColor(severity: string) {
    if (severity === 'high') return 'text-red-400 bg-red-500/10 border-red-500/20';
    if (severity === 'medium') return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
    if (severity === 'positive') return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
    return 'text-primary bg-primary/10 border-primary/20';
}

const entityTypeIcons: Record<EntityType, typeof Tag> = {
    vehicle: Car,
    office: Building2,
    team: Users,
    house: Home,
    children: Baby,
    property: Building,
    other: Tag,
};

const totalEntitySpend = computed(() =>
    (props.entityBreakdown ?? []).reduce((s, e) => s + (e.total_spent ?? 0), 0) || 1,
);

function daysLabel(days: number): string {
    if (days < 0) return `${Math.abs(days)}d overdue`;
    if (days === 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    return `In ${days}d`;
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto p-4 md:p-6">

        <!-- Yesterday Summary Banner -->
        <div v-if="yesterday.expenses > 0 || yesterday.income > 0" class="flex items-center gap-3 rounded-xl border border-sidebar-border/50 bg-card px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                <CalendarDays class="h-4 w-4 text-primary" />
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-sm font-medium">{{ greeting }} · </span>
                <span class="text-sm text-muted-foreground">Yesterday you spent </span>
                <span class="text-sm font-semibold text-red-400">{{ fmt(yesterday.expenses) }}</span>
                <span v-if="yesterday.income > 0" class="text-sm text-muted-foreground"> and earned </span>
                <span v-if="yesterday.income > 0" class="text-sm font-semibold text-emerald-400">{{ fmt(yesterday.income) }}</span>
                <span v-if="yesterday.count > 1" class="text-xs text-muted-foreground"> across {{ yesterday.count }} transactions</span>
            </div>
        </div>

        <!-- Stats Row — 3 top + 3 secondary -->
        <div class="grid gap-3 sm:grid-cols-3">

            <!-- Monthly Expenses -->
            <div class="relative overflow-hidden rounded-xl border border-red-500/20 bg-card p-5">
                <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 to-transparent" />
                <div class="relative">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">{{ t('dashboard.monthlyExpenses') }}</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500/10">
                            <TrendingDown class="h-4 w-4 text-red-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-red-400">{{ fmt(stats.totalExpenses) }}</p>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                                :class="stats.expenseChange > 0 ? 'bg-red-500/10 text-red-400' : 'bg-emerald-500/10 text-emerald-400'"
                            >
                                {{ stats.expenseChange > 0 ? '+' : '' }}{{ stats.expenseChange }}%
                            </span>
                            <span class="text-xs text-muted-foreground">vs last month</span>
                        </div>
                    </div>
                    <!-- Month-burn bar: shows spend vs income this month -->
                    <div class="mt-3">
                        <div class="h-1.5 overflow-hidden rounded-full bg-red-500/10">
                            <div
                                class="h-full rounded-full bg-red-400 transition-all"
                                :style="{ width: `${Math.min(100, stats.totalIncome > 0 ? (stats.totalExpenses / stats.totalIncome) * 100 : 100)}%` }"
                            />
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">
                            {{ stats.totalIncome > 0 ? Math.round((stats.totalExpenses / stats.totalIncome) * 100) : 100 }}% of income
                        </p>
                    </div>
                </div>
            </div>

            <!-- Monthly Income -->
            <div class="relative overflow-hidden rounded-xl border border-emerald-500/20 bg-card p-5">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent" />
                <div class="relative">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">{{ t('dashboard.monthlyIncome') }}</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10">
                            <TrendingUp class="h-4 w-4 text-emerald-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-emerald-400">{{ fmt(stats.totalIncome) }}</p>
                    <p class="mt-2 text-xs text-muted-foreground">{{ t('dashboard.thisMonth') }}</p>
                    <!-- Income vs expense bar -->
                    <div class="mt-3">
                        <div class="h-1.5 overflow-hidden rounded-full bg-emerald-500/10">
                            <div
                                class="h-full rounded-full bg-emerald-400 transition-all"
                                :style="{ width: `${Math.min(100, stats.totalExpenses > 0 ? (stats.totalIncome / Math.max(stats.totalIncome, stats.totalExpenses)) * 100 : 100)}%` }"
                            />
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">{{ stats.txCount }} transactions this month</p>
                    </div>
                </div>
            </div>

            <!-- Net Balance -->
            <div class="relative overflow-hidden rounded-xl border border-primary/20 bg-card p-5">
                <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent" />
                <div class="relative">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">{{ t('dashboard.netBalance') }}</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                            <Wallet class="h-4 w-4 text-primary" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold tracking-tight" :class="stats.balance >= 0 ? 'text-emerald-400' : 'text-red-400'">
                        {{ fmt(stats.balance) }}
                    </p>
                    <p class="mt-2 text-xs text-muted-foreground">{{ t('dashboard.thisMonth') }}</p>
                    <!-- Income vs expense split -->
                    <div class="mt-3">
                        <div class="flex h-1.5 overflow-hidden rounded-full bg-sidebar-border/30">
                            <div
                                class="h-full bg-emerald-400 transition-all"
                                :style="{ width: `${stats.totalIncome > 0 || stats.totalExpenses > 0 ? (stats.totalIncome / (stats.totalIncome + stats.totalExpenses)) * 100 : 50}%` }"
                            />
                            <div class="h-full flex-1 bg-red-400/60" />
                        </div>
                        <div class="mt-1 flex justify-between text-[10px] text-muted-foreground">
                            <span class="text-emerald-400">In</span>
                            <span class="text-red-400">Out</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary stats row -->
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/50 bg-card px-4 py-3.5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">{{ t('dashboard.netWorth') }}</p>
                        <p class="mt-1 text-base font-bold">{{ fmt(stats.netWorth) }}</p>
                    </div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-500/10">
                        <BarChart3 class="h-4 w-4 text-purple-400" />
                    </div>
                </div>
            </div>

            <!-- Savings rate with ring -->
            <div class="rounded-xl border border-sidebar-border/50 bg-card px-4 py-3.5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">{{ t('dashboard.savingsRate') }}</p>
                        <p class="mt-1 text-base font-bold" :class="stats.savingsRate >= 0 ? 'text-emerald-400' : 'text-red-400'">
                            {{ stats.savingsRate }}%
                        </p>
                    </div>
                    <!-- SVG ring progress -->
                    <svg width="36" height="36" viewBox="0 0 36 36" class="-rotate-90 shrink-0">
                        <circle cx="18" cy="18" r="14" fill="none" stroke="currentColor" stroke-width="3" class="text-emerald-500/10" />
                        <circle
                            cx="18" cy="18" r="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                            class="text-emerald-400 transition-all"
                            :stroke-dasharray="`${Math.max(0, Math.min(100, stats.savingsRate)) / 100 * 87.96} 87.96`"
                        />
                    </svg>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/50 bg-card px-4 py-3.5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">{{ t('dashboard.avgDailySpend') }}</p>
                        <p class="mt-1 text-base font-bold text-amber-400">{{ fmt(stats.avgDailySpend) }}</p>
                    </div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10">
                        <Zap class="h-4 w-4 text-amber-400" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/50 bg-card px-4 py-3.5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">{{ t('dashboard.transactions') }}</p>
                        <p class="mt-1 text-base font-bold text-primary">{{ stats.txCount }}</p>
                    </div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                        <CreditCard class="h-4 w-4 text-primary" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Loans outstanding (only show if there are any) -->
        <div v-if="loans.count > 0" class="grid gap-3 sm:grid-cols-3">
            <Link :href="loansIndex()" class="group rounded-xl border border-emerald-500/20 bg-emerald-500/5 px-4 py-3.5 transition-colors hover:border-emerald-500/40 hover:bg-emerald-500/8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">They Owe Me</p>
                        <p class="mt-1 text-base font-bold text-emerald-400">{{ fmt(loans.totalLent) }}</p>
                    </div>
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/10">
                        <ArrowUpRight class="h-3.5 w-3.5 text-emerald-400" />
                    </div>
                </div>
            </Link>
            <Link :href="loansIndex()" class="group rounded-xl border border-red-500/20 bg-red-500/5 px-4 py-3.5 transition-colors hover:border-red-500/40 hover:bg-red-500/8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">I Owe</p>
                        <p class="mt-1 text-base font-bold text-red-400">{{ fmt(loans.totalBorrowed) }}</p>
                    </div>
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-500/10">
                        <ArrowDownLeft class="h-3.5 w-3.5 text-red-400" />
                    </div>
                </div>
            </Link>
            <Link :href="loansIndex()" class="group rounded-xl border border-sidebar-border/50 bg-card px-4 py-3.5 transition-colors hover:border-sidebar-border hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Net Loans Position</p>
                        <p class="mt-1 text-base font-bold" :class="loans.netPosition >= 0 ? 'text-emerald-400' : 'text-red-400'">
                            {{ loans.netPosition >= 0 ? '+' : '' }}{{ fmt(Math.abs(loans.netPosition)) }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">{{ loans.count }} outstanding</p>
                    </div>
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10">
                        <HandCoins class="h-3.5 w-3.5 text-primary" />
                    </div>
                </div>
            </Link>
        </div>

        <div class="grid gap-5 md:grid-cols-5">
            <!-- Left Column -->
            <div class="flex flex-col gap-5 md:col-span-3">

                <!-- Wallets -->
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">{{ t('dashboard.wallets') }}</h2>
                        <Link :href="walletsIndex()" class="text-xs text-muted-foreground transition-colors hover:text-foreground">
                            {{ t('dashboard.manageWallets') }}
                        </Link>
                    </div>

                    <div v-if="wallets.length === 0" class="rounded-xl border border-dashed border-sidebar-border/50 p-6 text-center">
                        <p class="text-sm text-muted-foreground">{{ t('dashboard.noWallets') }}</p>
                        <Link :href="walletsIndex()" class="mt-2 inline-block text-xs text-primary hover:text-primary/80">
                            {{ t('dashboard.addWallet') }}
                        </Link>
                    </div>

                    <div v-else class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="wallet in wallets"
                            :key="wallet.id"
                            class="flex items-center gap-4 rounded-xl border border-sidebar-border/50 bg-card p-4 transition-all hover:border-sidebar-border hover:shadow-sm"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :style="{ backgroundColor: wallet.color + '25' }">
                                <component :is="walletIcons[wallet.type] ?? Wallet" class="h-5 w-5" :style="{ color: wallet.color }" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ wallet.name }}</p>
                                <p class="text-xs text-muted-foreground capitalize">{{ wallet.type }} · {{ wallet.currency }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-bold tabular-nums">{{ fmt(wallet.balance, wallet.currency) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">{{ t('dashboard.recentTransactions') }}</h2>
                        <Link :href="transactionsIndex()" class="text-xs text-muted-foreground transition-colors hover:text-foreground">
                            {{ t('dashboard.viewAll') }}
                        </Link>
                    </div>

                    <div v-if="recentTransactions.length === 0" class="rounded-xl border border-dashed border-sidebar-border/50 p-8 text-center">
                        <p class="mb-2 text-sm text-muted-foreground">{{ t('dashboard.noTransactions') }}</p>
                        <Link :href="aiIndex()" class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80">
                            <MessageSquarePlus class="h-3.5 w-3.5" /> {{ t('dashboard.addViaAI') }}
                        </Link>
                    </div>

                    <div v-else class="divide-y divide-sidebar-border/30 overflow-hidden rounded-xl border border-sidebar-border/50 bg-card">
                        <div v-for="tx in recentTransactions" :key="tx.id" class="flex items-center gap-4 px-4 py-3 transition-colors hover:bg-accent/30">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :style="{ backgroundColor: (tx.category?.color ?? '#6b7280') + '20' }">
                                <ArrowDownRight v-if="tx.type === 'expense'" class="h-4 w-4 text-red-400" />
                                <ArrowUpRight v-else class="h-4 w-4 text-emerald-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ tx.description ?? tx.category?.name ?? 'Transaction' }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(tx.date) }} · {{ tx.wallet?.name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="shrink-0 text-sm font-semibold tabular-nums" :class="tx.type === 'expense' ? 'text-red-400' : 'text-emerald-400'">
                                    {{ tx.type === 'expense' ? '-' : '+' }}{{ fmt(tx.amount, tx.currency) }}
                                </p>
                                <p v-if="tx.currency !== currency" class="text-[10px] text-muted-foreground">{{ tx.currency }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="md:col-span-2 flex flex-col gap-5">

                <!-- Category Breakdown -->
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">{{ t('dashboard.topCategories') }}</h2>
                        <span class="text-xs text-muted-foreground">{{ t('dashboard.thisMonth') }}</span>
                    </div>

                    <div v-if="categoryBreakdown.length === 0" class="rounded-xl border border-dashed border-sidebar-border/50 p-6 text-center">
                        <p class="text-sm text-muted-foreground">{{ t('common.noData') }}</p>
                    </div>

                    <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/50 bg-card">
                        <div v-for="(item, i) in categoryBreakdown" :key="i" class="flex items-center gap-3 border-b border-sidebar-border/30 p-4 last:border-0 hover:bg-accent/20 transition-colors">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold" :style="{ backgroundColor: (item.category?.color ?? '#6b7280') + '25', color: item.category?.color ?? '#6b7280' }">
                                {{ item.category?.name?.[0] ?? '?' }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ item.category?.name ?? 'Uncategorized' }}</p>
                                <div class="mt-1.5 h-1 rounded-full bg-sidebar-border/40">
                                    <div
                                        class="h-1 rounded-full transition-all"
                                        :style="{
                                            backgroundColor: item.category?.color ?? '#6b7280',
                                            width: `${Math.min(100, (Number(item.total) / totalCategorySpend) * 100)}%`,
                                        }"
                                    />
                                </div>
                            </div>
                            <p class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ fmt(Number(item.total)) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Add via AI -->
                <Link
                    :href="aiIndex()"
                    class="flex items-center justify-center gap-2 rounded-xl border border-primary/30 bg-primary/10 p-4 text-sm text-primary transition-colors hover:bg-primary/15"
                >
                    <MessageSquarePlus class="h-4 w-4" />
                    {{ t('dashboard.addViaAI') }}
                </Link>
            </div>
        </div>

        <!-- Recurring + Insights row -->
        <div class="grid gap-5 lg:grid-cols-2">

            <!-- Recurring Expenses -->
            <WhenVisible data="recurring" :buffer="100">
                <template #fallback>
                    <div class="space-y-2">
                        <div class="h-5 w-40 animate-pulse rounded bg-sidebar-border/40" />
                        <div v-for="i in 3" :key="i" class="h-14 animate-pulse rounded-xl bg-sidebar-border/20" />
                    </div>
                </template>
                <template #default>
                    <div v-if="recurring && recurring.length > 0">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-sm font-semibold">
                                <RefreshCw class="h-4 w-4 text-primary" /> Recurring Detected
                            </h2>
                            <span class="text-xs text-muted-foreground">{{ recurring.length }} patterns</span>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-sidebar-border/50 bg-card">
                            <div
                                v-for="item in recurring"
                                :key="item.key"
                                class="flex items-center gap-3 border-b border-sidebar-border/20 px-4 py-3 last:border-0 hover:bg-accent/20 transition-colors"
                            >
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                                    :style="item.category ? { backgroundColor: item.category.color + '20', color: item.category.color } : { backgroundColor: '#6366f120', color: '#6366f1' }"
                                >
                                    {{ item.name[0] }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ item.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ item.interval }} · ~{{ fmt(item.avg_amount) }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p
                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                        :class="item.is_overdue ? 'bg-red-500/15 text-red-400' : item.days_until <= 3 ? 'bg-amber-500/15 text-amber-400' : 'bg-sidebar-accent text-muted-foreground'"
                                    >
                                        {{ daysLabel(item.days_until) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </WhenVisible>

            <!-- Spending Insights -->
            <WhenVisible data="insights" :buffer="100">
                <template #fallback>
                    <div class="space-y-2">
                        <div class="h-5 w-40 animate-pulse rounded bg-sidebar-border/40" />
                        <div v-for="i in 3" :key="i" class="h-20 animate-pulse rounded-xl bg-sidebar-border/20" />
                    </div>
                </template>
                <template #default>
                    <div v-if="insights && insights.length > 0">
                        <div class="mb-3 flex items-center gap-2">
                            <h2 class="flex items-center gap-2 text-sm font-semibold">
                                <Sparkles class="h-4 w-4 text-amber-400" /> Spending Insights
                            </h2>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="insight in insights"
                                :key="insight.type"
                                class="flex items-start gap-3 rounded-xl border p-4 transition-colors"
                                :class="insightColor(insight.severity)"
                            >
                                <AlertTriangle v-if="insight.severity === 'high' || insight.severity === 'medium'" class="mt-0.5 h-4 w-4 shrink-0" />
                                <Sparkles v-else class="mt-0.5 h-4 w-4 shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold">{{ insight.title }}</p>
                                    <p class="mt-0.5 text-xs opacity-80">{{ insight.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </WhenVisible>

        </div>

        <!-- Entity Breakdown (deferred) -->
        <WhenVisible data="entityBreakdown" :buffer="100">
            <template #fallback>
                <div class="space-y-2">
                    <div class="h-5 w-40 animate-pulse rounded bg-sidebar-border/40" />
                    <div v-for="i in 3" :key="i" class="h-14 animate-pulse rounded-xl bg-sidebar-border/20" />
                </div>
            </template>
            <template #default>
                <div v-if="entityBreakdown && entityBreakdown.length > 0">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-sm font-semibold">
                            <Tag class="h-4 w-4 text-primary" /> By Entity
                        </h2>
                        <Link :href="entitiesIndex()" class="text-xs text-muted-foreground transition-colors hover:text-foreground">
                            Manage entities
                        </Link>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-sidebar-border/50 bg-card">
                        <div
                            v-for="entity in entityBreakdown"
                            :key="entity.id"
                            class="flex items-center gap-3 border-b border-sidebar-border/20 px-4 py-3 last:border-0 hover:bg-accent/20 transition-colors"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm"
                                :style="{ backgroundColor: entity.color + '20' }"
                            >
                                <span v-if="entity.emoji">{{ entity.emoji }}</span>
                                <component v-else :is="entityTypeIcons[entity.type] ?? Tag" class="h-4 w-4" :style="{ color: entity.color }" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ entity.name }}</p>
                                <div class="mt-1.5 h-1 rounded-full bg-sidebar-border/40">
                                    <div
                                        class="h-1 rounded-full transition-all"
                                        :style="{
                                            backgroundColor: entity.color,
                                            width: `${Math.min(100, ((entity.total_spent ?? 0) / totalEntitySpend) * 100)}%`,
                                        }"
                                    />
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs tabular-nums font-medium">{{ fmt(entity.total_spent ?? 0) }}</p>
                                <p class="text-[10px] text-muted-foreground">{{ entity.tx_count }} txns</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </WhenVisible>

    </div>
</template>
