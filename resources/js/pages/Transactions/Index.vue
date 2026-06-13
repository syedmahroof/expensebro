<script setup lang="ts">
import {
    Head,
    InfiniteScroll,
    Link,
    router,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    Download,
    MapPin,
    Plus,
    RefreshCw,
    Search,
    Store,
    Tag,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type {
    Category,
    CategoryGroup,
    Entity,
    MerchantGroup,
    Transaction,
    Wallet,
} from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import { dashboard } from '@/routes';

const { confirm } = useConfirm();
import { index as aiIndex } from '@/routes/ai';
import {
    destroy,
    exportMethod,
    index as transactionsIndex,
    store,
} from '@/routes/transactions';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Transactions', href: transactionsIndex() },
        ],
    },
});

const props = defineProps<{
    view: 'list' | 'categories' | 'merchants' | 'timeline';
    transactions?: {
        data: Transaction[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    timelineItems?: { data: Transaction[] };
    categoryGroups?: CategoryGroup[];
    merchantGroups?: MerchantGroup[];
    wallets: Wallet[];
    categories: Category[];
    merchants: Array<{ id: number; name: string }>;
    trips: Array<{ id: number; name: string; destination: string | null }>;
    entities: Entity[];
    filters: {
        type?: string;
        category_id?: string;
        wallet_id?: string;
        merchant_id?: string;
        entity_id?: string;
        search?: string;
        date_from?: string;
        date_to?: string;
        view?: string;
    };
}>();

// ── Filter state ──────────────────────────────────────────────────────────────
const currentView = ref(props.view);
const search = ref(props.filters.search ?? '');
const selectedType = ref(props.filters.type ?? '');
const selectedCategory = ref(props.filters.category_id ?? '');
const selectedWallet = ref(props.filters.wallet_id ?? '');
const selectedMerchant = ref(props.filters.merchant_id ?? '');
const selectedEntity = ref(props.filters.entity_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

// ── Date helpers ──────────────────────────────────────────────────────────────
const todayStr = () => new Date().toISOString().slice(0, 10);
const firstOfMonth = () => {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth(), 1)
        .toISOString()
        .slice(0, 10);
};
const firstOfYear = () =>
    new Date(new Date().getFullYear(), 0, 1).toISOString().slice(0, 10);

const activePreset = computed<'today' | 'month' | 'year' | null>(() => {
    const today = todayStr();
    if (dateFrom.value === today && dateTo.value === today) return 'today';
    if (dateFrom.value === firstOfMonth() && dateTo.value === today)
        return 'month';
    if (dateFrom.value === firstOfYear() && dateTo.value === today)
        return 'year';
    return null;
});

function applyPreset(preset: 'today' | 'month' | 'year') {
    const today = todayStr();
    if (preset === 'today') {
        dateFrom.value = today;
        dateTo.value = today;
    } else if (preset === 'month') {
        dateFrom.value = firstOfMonth();
        dateTo.value = today;
    } else {
        dateFrom.value = firstOfYear();
        dateTo.value = today;
    }
    navigate();
}

function clearDates() {
    dateFrom.value = '';
    dateTo.value = '';
    navigate();
}

// ── Navigation ────────────────────────────────────────────────────────────────
function navigate(overrides: Record<string, string | undefined> = {}) {
    router.get(
        transactionsIndex(),
        {
            view: currentView.value !== 'list' ? currentView.value : undefined,
            search: search.value || undefined,
            type: selectedType.value || undefined,
            category_id: selectedCategory.value || undefined,
            wallet_id: selectedWallet.value || undefined,
            merchant_id: selectedMerchant.value || undefined,
            entity_id: selectedEntity.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            ...overrides,
        },
        { preserveState: true, replace: true },
    );
}

const timelineGroups = computed(() => {
    const items = props.timelineItems?.data ?? [];
    const groups: Record<string, Transaction[]> = {};
    for (const tx of items) {
        const day = tx.date.slice(0, 10);
        if (!groups[day]) groups[day] = [];
        groups[day].push(tx);
    }
    return Object.entries(groups).map(([date, txns]) => ({ date, txns }));
});

function formatDateLabel(dateStr: string): string {
    const today = new Date().toISOString().slice(0, 10);
    const yesterday = new Date(Date.now() - 86_400_000)
        .toISOString()
        .slice(0, 10);
    if (dateStr === today) return 'Today';
    if (dateStr === yesterday) return 'Yesterday';
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('en', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}

function formatBubbleTime(createdAt: string | undefined): string {
    if (!createdAt) return '';
    return new Date(createdAt).toLocaleTimeString('en', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

function switchView(view: 'list' | 'categories' | 'merchants' | 'timeline') {
    currentView.value = view;
    navigate();
}

function drillIntoCategory(categoryId: number) {
    router.get(
        transactionsIndex(),
        {
            category_id: String(categoryId),
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { replace: true },
    );
}

function drillIntoMerchant(merchantId: number) {
    router.get(
        transactionsIndex(),
        {
            merchant_id: String(merchantId),
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { replace: true },
    );
}

let searchTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(navigate, 400);
});
watch(
    [
        selectedType,
        selectedCategory,
        selectedWallet,
        selectedMerchant,
        selectedEntity,
    ],
    navigate,
);

// ── Add transaction modal ─────────────────────────────────────────────────────
const showModal = ref(false);
const page = usePage<{
    userCurrency: string;
    currencies: Record<string, string>;
}>();
const defaultCurrency = computed(() => page.props.userCurrency ?? 'PKR');

const CURRENCIES = Object.keys(page.props.currencies ?? {});

const form = useForm({
    wallet_id: props.wallets[0]?.id ?? '',
    category_id: '' as number | '',
    trip_id: '' as number | '',
    entity_id: '' as number | '',
    type: 'expense' as 'expense' | 'income' | 'transfer',
    amount: '' as number | '',
    currency: defaultCurrency.value,
    description: '',
    notes: '',
    location: '',
    date: new Date().toISOString().slice(0, 10),
});

const isForeignCurrency = computed(
    () => form.currency && form.currency !== defaultCurrency.value,
);

function submitTransaction() {
    form.post(store().url, {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
        preserveScroll: true,
    });
}

// ── Export ────────────────────────────────────────────────────────────────────
function exportCsv() {
    const params = new URLSearchParams();
    if (search.value) {
        params.set('search', search.value);
    }
    if (selectedType.value) {
        params.set('type', selectedType.value);
    }
    if (selectedCategory.value) {
        params.set('category_id', selectedCategory.value);
    }
    if (selectedWallet.value) {
        params.set('wallet_id', selectedWallet.value);
    }
    if (dateFrom.value) {
        params.set('date_from', dateFrom.value);
    }
    if (dateTo.value) {
        params.set('date_to', dateTo.value);
    }
    const qs = params.toString();
    window.location.href = exportMethod().url + (qs ? '?' + qs : '');
}

// ── Delete ────────────────────────────────────────────────────────────────────
async function deleteTransaction(id: number) {
    const ok = await confirm('This transaction will be permanently deleted.', {
        title: 'Delete transaction?',
        confirmText: 'Delete',
    });
    if (!ok) {
        return;
    }
    router.delete(destroy({ transaction: id }).url, { preserveScroll: true });
}

// ── Formatters ────────────────────────────────────────────────────────────────
function formatAmount(amount: number, currency = 'PKR') {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
}
function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
function getInitial(name: string) {
    return name.charAt(0).toUpperCase();
}
</script>

<template>
    <Head title="Transactions" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <!-- Row 1: View switcher + action buttons -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div
                class="flex items-center gap-1 rounded-lg border border-input bg-background p-1"
            >
                <button
                    v-for="v in [
                        { key: 'list', label: 'List' },
                        { key: 'timeline', label: 'Timeline' },
                        { key: 'categories', label: 'Categories' },
                        { key: 'merchants', label: 'Merchants' },
                    ] as const"
                    :key="v.key"
                    @click="switchView(v.key)"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        currentView === v.key
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    {{ v.label }}
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="showModal = true"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" /> Add
                </button>
                <button
                    @click="exportCsv"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-input bg-background px-4 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <Download class="h-4 w-4" /> Export
                </button>
                <Link
                    :href="aiIndex()"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-primary/40 px-4 text-sm font-medium text-primary transition-colors hover:bg-primary/10"
                >
                    AI Chat
                </Link>
            </div>
        </div>

        <!-- Row 2: Date filters -->
        <div class="flex flex-wrap items-center gap-2">
            <div
                class="flex items-center gap-1 rounded-lg border border-input bg-background p-1"
            >
                <button
                    v-for="p in [
                        { key: 'today', label: 'Today' },
                        { key: 'month', label: 'Month' },
                        { key: 'year', label: 'Year' },
                    ] as const"
                    :key="p.key"
                    @click="applyPreset(p.key)"
                    class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        activePreset === p.key
                            ? 'bg-sidebar-accent text-foreground'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    {{ p.label }}
                </button>
            </div>

            <input
                v-model="dateFrom"
                type="date"
                @change="navigate()"
                class="h-9 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
            />
            <span class="text-xs text-muted-foreground">→</span>
            <input
                v-model="dateTo"
                type="date"
                @change="navigate()"
                class="h-9 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
            />
            <button
                v-if="dateFrom || dateTo"
                @click="clearDates"
                class="rounded p-1 text-muted-foreground hover:text-foreground"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <!-- Row 3: List-view filters -->
        <div
            v-if="currentView === 'list'"
            class="flex flex-wrap items-center gap-3"
        >
            <div class="relative min-w-48 flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search transactions..."
                    class="h-9 w-full rounded-lg border border-input bg-background pr-3 pl-9 text-sm outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <select
                v-model="selectedType"
                class="h-9 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
            >
                <option value="">All Types</option>
                <option value="expense">Expense</option>
                <option value="income">Income</option>
                <option value="transfer">Transfer</option>
            </select>
            <select
                v-model="selectedCategory"
                class="h-9 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
            >
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
            <select
                v-model="selectedWallet"
                class="h-9 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
            >
                <option value="">All Wallets</option>
                <option v-for="w in wallets" :key="w.id" :value="w.id">
                    {{ w.name }}
                </option>
            </select>
            <select
                v-model="selectedMerchant"
                class="h-9 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
            >
                <option value="">All Merchants</option>
                <option v-for="m in merchants" :key="m.id" :value="m.id">
                    {{ m.name }}
                </option>
            </select>
            <select
                v-if="entities.length > 0"
                v-model="selectedEntity"
                class="h-9 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
            >
                <option value="">All Entities</option>
                <option v-for="e in entities" :key="e.id" :value="e.id">
                    {{ e.emoji ? e.emoji + ' ' : '' }}{{ e.name }}
                </option>
            </select>
        </div>

        <!-- ── LIST VIEW ───────────────────────────────────────────────────── -->
        <template v-if="currentView === 'list'">
            <p class="text-sm text-muted-foreground">
                {{ transactions?.total ?? 0 }} transaction{{
                    (transactions?.total ?? 0) !== 1 ? 's' : ''
                }}
            </p>

            <div
                v-if="!transactions?.data.length"
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 p-12 text-center"
            >
                <div>
                    <p class="mb-3 text-sm text-muted-foreground">
                        No transactions found.
                    </p>
                    <button
                        @click="showModal = true"
                        class="text-xs text-primary hover:text-primary/80"
                    >
                        Add your first transaction →
                    </button>
                </div>
            </div>

            <div
                v-else
                class="divide-y divide-sidebar-border/30 rounded-xl border border-sidebar-border/50 bg-card"
            >
                <div
                    v-for="tx in transactions!.data"
                    :key="tx.id"
                    class="group flex items-center gap-4 p-4"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :style="{
                            backgroundColor:
                                (tx.category?.color ?? '#6b7280') + '20',
                        }"
                    >
                        <ArrowDownRight
                            v-if="tx.type === 'expense'"
                            class="h-4 w-4 text-red-400"
                        />
                        <ArrowUpRight v-else class="h-4 w-4 text-emerald-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{
                                tx.description ||
                                tx.category?.name ||
                                'Transaction'
                            }}
                        </p>
                        <div class="mt-0.5 flex flex-wrap items-center gap-2">
                            <span
                                v-if="tx.category"
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs"
                                :style="{
                                    backgroundColor: tx.category.color + '20',
                                    color: tx.category.color,
                                }"
                                >{{ tx.category.name }}</span
                            >
                            <span
                                v-if="tx.entity"
                                class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs"
                                :style="{
                                    backgroundColor: tx.entity.color + '20',
                                    color: tx.entity.color,
                                }"
                            >
                                <span
                                    v-if="tx.entity.emoji"
                                    class="text-[10px]"
                                    >{{ tx.entity.emoji }}</span
                                >{{ tx.entity.name }}
                            </span>
                            <span class="text-xs text-muted-foreground"
                                >{{ tx.wallet?.name }} ·
                                {{ formatDate(tx.date) }}</span
                            >
                            <span
                                v-if="tx.location"
                                class="inline-flex items-center gap-0.5 text-xs text-muted-foreground/70"
                            >
                                <MapPin class="h-2.5 w-2.5" />{{ tx.location }}
                            </span>
                        </div>
                    </div>
                    <p
                        class="shrink-0 text-sm font-semibold tabular-nums"
                        :class="
                            tx.type === 'expense'
                                ? 'text-red-400'
                                : 'text-emerald-400'
                        "
                    >
                        {{ tx.type === 'expense' ? '-' : '+'
                        }}{{ formatAmount(tx.amount, tx.currency) }}
                    </p>
                    <button
                        @click="deleteTransaction(tx.id)"
                        class="ml-2 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-red-400"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div
                v-if="(transactions?.last_page ?? 0) > 1"
                class="flex items-center justify-center gap-1"
            >
                <template v-for="link in transactions!.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </template>

        <!-- ── CATEGORIES VIEW ─────────────────────────────────────────────── -->
        <template v-else-if="currentView === 'categories'">
            <p class="text-sm text-muted-foreground">
                {{ categoryGroups?.length ?? 0 }} categories with transactions
            </p>

            <div
                v-if="!categoryGroups?.length"
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 p-12 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No transactions in this period.
                </p>
            </div>

            <div
                v-else
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
            >
                <button
                    v-for="cat in categoryGroups"
                    :key="cat.id"
                    @click="drillIntoCategory(cat.id)"
                    class="group flex flex-col gap-3 rounded-xl border border-sidebar-border/50 bg-card p-4 text-left transition-all hover:border-primary/40 hover:shadow-md"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg transition-transform group-hover:scale-105"
                        :style="{ backgroundColor: cat.color + '25' }"
                    >
                        <Tag class="h-5 w-5" :style="{ color: cat.color }" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">
                            {{ cat.name }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ cat.transaction_count }} txn{{
                                cat.transaction_count !== 1 ? 's' : ''
                            }}
                        </p>
                    </div>
                    <div>
                        <p
                            v-if="cat.expense_amount > 0"
                            class="text-sm font-semibold text-red-400 tabular-nums"
                        >
                            -{{ formatAmount(cat.expense_amount) }}
                        </p>
                        <p
                            v-if="cat.income_amount > 0"
                            class="text-xs font-medium text-emerald-400 tabular-nums"
                        >
                            +{{ formatAmount(cat.income_amount) }}
                        </p>
                    </div>
                </button>
            </div>
        </template>

        <!-- ── TIMELINE VIEW (WhatsApp-style) ────────────────────────────── -->
        <template v-else-if="currentView === 'timeline'">
            <!-- Empty state -->
            <div
                v-if="!timelineGroups.length && !props.timelineItems"
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 p-12 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No transactions in this period.
                </p>
            </div>

            <template v-else>
                <!-- Chat background wrapper -->
                <div
                    class="-mx-4 -mt-2 flex-1 rounded-xl md:-mx-0"
                    style="
                        background-image: radial-gradient(
                            circle,
                            color-mix(in srgb, currentColor 4%, transparent) 1px,
                            transparent 1px
                        );
                        background-size: 22px 22px;
                    "
                >
                    <InfiniteScroll
                        data="timelineItems"
                        only-next
                        class="px-3 pt-2 pb-6 md:px-4"
                    >
                        <div
                            v-for="group in timelineGroups"
                            :key="group.date"
                            class="mb-2"
                        >
                            <!-- Date separator pill -->
                            <div class="flex justify-center py-3">
                                <span
                                    class="rounded-full border border-sidebar-border/40 bg-card px-3 py-1 text-[11px] font-medium text-muted-foreground shadow-sm"
                                >
                                    {{ formatDateLabel(group.date) }}
                                </span>
                            </div>

                            <!-- Bubbles for this day -->
                            <div class="flex flex-col gap-1.5">
                                <div
                                    v-for="tx in group.txns"
                                    :key="tx.id"
                                    class="flex items-end gap-2"
                                    :class="
                                        tx.type === 'expense'
                                            ? 'flex-row-reverse'
                                            : tx.type === 'transfer'
                                              ? 'justify-center'
                                              : ''
                                    "
                                >
                                    <!-- Avatar dot -->
                                    <div
                                        v-if="tx.type !== 'transfer'"
                                        class="mb-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                        :style="{
                                            backgroundColor:
                                                (tx.category?.color ??
                                                    (tx.type === 'expense'
                                                        ? '#ef4444'
                                                        : '#10b981')) + '25',
                                            color:
                                                tx.category?.color ??
                                                (tx.type === 'expense'
                                                    ? '#ef4444'
                                                    : '#10b981'),
                                        }"
                                    >
                                        {{
                                            tx.category?.name?.[0] ??
                                            (tx.type === 'income' ? '↑' : '↓')
                                        }}
                                    </div>

                                    <!-- Bubble -->
                                    <div
                                        class="group relative max-w-[75%] rounded-2xl border px-3.5 py-2.5 shadow-sm transition-shadow hover:shadow-md"
                                        :class="{
                                            'rounded-br-[4px] border-red-500/25 bg-red-500/[0.07]':
                                                tx.type === 'expense',
                                            'rounded-bl-[4px] border-emerald-500/25 bg-emerald-500/[0.07]':
                                                tx.type === 'income',
                                            'max-w-[55%] rounded-xl border-blue-500/25 bg-blue-500/[0.07]':
                                                tx.type === 'transfer',
                                        }"
                                    >
                                        <!-- Top: type + time -->
                                        <div
                                            class="mb-1 flex items-center justify-between gap-4"
                                        >
                                            <span
                                                class="text-[10px] font-bold tracking-wider uppercase"
                                                :class="{
                                                    'text-red-400':
                                                        tx.type === 'expense',
                                                    'text-emerald-400':
                                                        tx.type === 'income',
                                                    'text-blue-400':
                                                        tx.type === 'transfer',
                                                }"
                                            >
                                                {{
                                                    tx.type === 'expense'
                                                        ? '↓ Expense'
                                                        : tx.type === 'income'
                                                          ? '↑ Income'
                                                          : '⇄ Transfer'
                                                }}
                                            </span>
                                            <span
                                                class="shrink-0 text-[10px] text-muted-foreground/60"
                                                >{{
                                                    formatBubbleTime(
                                                        tx.created_at,
                                                    )
                                                }}</span
                                            >
                                        </div>

                                        <!-- Description -->
                                        <p
                                            class="text-sm leading-snug font-semibold"
                                        >
                                            {{
                                                tx.description ||
                                                tx.merchant?.name ||
                                                tx.category?.name ||
                                                'Transaction'
                                            }}
                                        </p>

                                        <!-- Amount -->
                                        <p
                                            class="mt-1 text-xl font-bold tabular-nums"
                                            :class="{
                                                'text-red-400':
                                                    tx.type === 'expense',
                                                'text-emerald-400':
                                                    tx.type === 'income',
                                                'text-blue-400':
                                                    tx.type === 'transfer',
                                            }"
                                        >
                                            {{
                                                tx.type === 'expense'
                                                    ? '−'
                                                    : '+'
                                            }}{{
                                                formatAmount(
                                                    tx.amount,
                                                    tx.currency,
                                                )
                                            }}
                                        </p>

                                        <!-- Tags row -->
                                        <div
                                            class="mt-2 flex flex-wrap items-center gap-1"
                                        >
                                            <span
                                                v-if="tx.category"
                                                class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                                :style="{
                                                    backgroundColor:
                                                        tx.category.color +
                                                        '20',
                                                    color: tx.category.color,
                                                }"
                                                >{{ tx.category.name }}</span
                                            >
                                            <span
                                                v-if="tx.entity"
                                                class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                                :style="{
                                                    backgroundColor:
                                                        tx.entity.color + '20',
                                                    color: tx.entity.color,
                                                }"
                                                >{{
                                                    tx.entity.emoji
                                                        ? tx.entity.emoji + ' '
                                                        : ''
                                                }}{{ tx.entity.name }}</span
                                            >
                                            <span
                                                v-if="tx.wallet"
                                                class="text-[10px] text-muted-foreground/50"
                                                >· {{ tx.wallet.name }}</span
                                            >
                                            <span
                                                v-if="tx.location"
                                                class="flex items-center gap-0.5 text-[10px] text-muted-foreground/50"
                                            >
                                                <MapPin class="h-2.5 w-2.5" />{{
                                                    tx.location
                                                }}
                                            </span>
                                        </div>

                                        <!-- Delete on hover -->
                                        <button
                                            @click="deleteTransaction(tx.id)"
                                            class="absolute -top-1 -right-1 hidden h-5 w-5 items-center justify-center rounded-full bg-card text-muted-foreground shadow-sm ring-1 ring-sidebar-border/40 group-hover:flex hover:text-red-400"
                                        >
                                            <X class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading / done slots -->
                        <template #loading>
                            <div class="flex justify-center py-4">
                                <span
                                    class="rounded-full border border-sidebar-border/40 bg-card px-4 py-1.5 text-xs text-muted-foreground"
                                >
                                    Loading…
                                </span>
                            </div>
                        </template>
                        <template #complete>
                            <div class="flex justify-center py-6">
                                <span class="text-xs text-muted-foreground/40"
                                    >All transactions loaded</span
                                >
                            </div>
                        </template>
                    </InfiniteScroll>
                </div>
            </template>
        </template>

        <!-- ── MERCHANTS VIEW ──────────────────────────────────────────────── -->
        <template v-else-if="currentView === 'merchants'">
            <p class="text-sm text-muted-foreground">
                {{ merchantGroups?.length ?? 0 }} merchants with transactions
            </p>

            <div
                v-if="!merchantGroups?.length"
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 p-12 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No transactions in this period.
                </p>
            </div>

            <div
                v-else
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
            >
                <button
                    v-for="merchant in merchantGroups"
                    :key="merchant.id"
                    @click="drillIntoMerchant(merchant.id)"
                    class="group flex flex-col gap-3 rounded-xl border border-sidebar-border/50 bg-card p-4 text-left transition-all hover:border-primary/40 hover:shadow-md"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-sidebar-accent transition-transform group-hover:scale-105"
                    >
                        <Store
                            v-if="!merchant.logo"
                            class="h-5 w-5 text-muted-foreground"
                        />
                        <img
                            v-else
                            :src="merchant.logo"
                            :alt="merchant.name"
                            class="h-10 w-10 rounded-lg object-cover"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">
                            {{ merchant.name }}
                        </p>
                        <p
                            v-if="merchant.category"
                            class="mt-0.5 truncate text-xs font-medium"
                            :style="{ color: merchant.category.color }"
                        >
                            {{ merchant.category.name }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ merchant.transaction_count }} txn{{
                                merchant.transaction_count !== 1 ? 's' : ''
                            }}
                        </p>
                    </div>
                    <div>
                        <p
                            v-if="merchant.expense_amount > 0"
                            class="text-sm font-semibold text-red-400 tabular-nums"
                        >
                            -{{ formatAmount(merchant.expense_amount) }}
                        </p>
                        <p
                            v-if="merchant.income_amount > 0"
                            class="text-xs font-medium text-emerald-400 tabular-nums"
                        >
                            +{{ formatAmount(merchant.income_amount) }}
                        </p>
                    </div>
                </button>
            </div>
        </template>
    </div>

    <!-- ── Add Transaction Modal ───────────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-sidebar-border bg-card p-6 shadow-2xl"
            >
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Add Transaction</h2>
                    <button
                        @click="
                            showModal = false;
                            form.reset();
                        "
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitTransaction" class="space-y-4">
                    <div class="flex rounded-lg border border-input p-1">
                        <button
                            v-for="t in ['expense', 'income', 'transfer']"
                            :key="t"
                            type="button"
                            @click="form.type = t as typeof form.type"
                            class="flex-1 rounded-md py-1.5 text-xs font-medium capitalize transition-colors"
                            :class="
                                form.type === t
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            {{ t }}
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="mb-1.5 block text-xs font-medium"
                                >Amount</label
                            >
                            <div class="flex gap-1.5">
                                <select
                                    v-model="form.currency"
                                    class="h-10 rounded-lg border border-input bg-background px-2 text-xs outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option
                                        v-for="c in CURRENCIES"
                                        :key="c"
                                        :value="c"
                                    >
                                        {{ c }}
                                    </option>
                                </select>
                                <input
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    placeholder="0.00"
                                    class="h-10 min-w-0 flex-1 rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                                />
                            </div>
                            <p
                                v-if="form.errors.amount"
                                class="mt-1 text-xs text-red-400"
                            >
                                {{ form.errors.amount }}
                            </p>
                            <p
                                v-if="isForeignCurrency"
                                class="mt-1 flex items-center gap-1 text-xs text-primary"
                            >
                                <RefreshCw class="h-3 w-3" /> Auto-converted to
                                {{ defaultCurrency }}
                            </p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium"
                                >Date</label
                            >
                            <input
                                v-model="form.date"
                                type="date"
                                required
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium"
                            >Description</label
                        >
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Coffee, Uber, Salary..."
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium"
                                >Wallet</label
                            >
                            <select
                                v-model="form.wallet_id"
                                required
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option
                                    v-for="w in wallets"
                                    :key="w.id"
                                    :value="w.id"
                                >
                                    {{ w.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.wallet_id"
                                class="mt-1 text-xs text-red-400"
                            >
                                {{ form.errors.wallet_id }}
                            </p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium"
                                >Category</label
                            >
                            <select
                                v-model="form.category_id"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option value="">No category</option>
                                <option
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    :value="cat.id"
                                >
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium"
                            >Notes
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></label
                        >
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Any extra details..."
                            class="w-full resize-none rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1.5 flex items-center gap-1 text-xs font-medium"
                            >
                                <MapPin class="h-3 w-3 text-muted-foreground" />
                                Location
                            </label>
                            <input
                                v-model="form.location"
                                type="text"
                                placeholder="e.g. Dubai Mall"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            />
                        </div>
                        <div v-if="trips.length > 0">
                            <label class="mb-1.5 block text-xs font-medium"
                                >Trip</label
                            >
                            <select
                                v-model="form.trip_id"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option value="">No trip</option>
                                <option
                                    v-for="trip in trips"
                                    :key="trip.id"
                                    :value="trip.id"
                                >
                                    {{ trip.name
                                    }}{{
                                        trip.destination
                                            ? ` · ${trip.destination}`
                                            : ''
                                    }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="entities.length > 0">
                        <label class="mb-1.5 block text-xs font-medium"
                            >Entity
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></label
                        >
                        <select
                            v-model="form.entity_id"
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                        >
                            <option value="">No entity</option>
                            <option
                                v-for="e in entities"
                                :key="e.id"
                                :value="e.id"
                            >
                                {{ e.emoji ? e.emoji + ' ' : ''
                                }}{{ e.name }} ({{ e.type }})
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button
                            type="button"
                            @click="
                                showModal = false;
                                form.reset();
                            "
                            class="h-10 flex-1 rounded-lg border border-input text-sm transition-colors hover:bg-accent"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="h-10 flex-1 rounded-lg bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
