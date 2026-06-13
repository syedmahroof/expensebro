<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    Search,
    Tag,
    Wallet,
    X,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as walletsIndex } from '@/routes/wallets';

interface SearchResult {
    transactions: Array<{
        id: number;
        description: string | null;
        amount: number;
        currency: string;
        type: string;
        date: string;
        category?: { name: string; color: string };
        wallet?: { name: string };
    }>;
    wallets: Array<{
        id: number;
        name: string;
        type: string;
        balance: number;
        currency: string;
        color: string;
    }>;
    categories: Array<{
        id: number;
        name: string;
        color: string;
        type: string;
    }>;
}

const emit = defineEmits<{ close: [] }>();

const query = ref('');
const results = ref<SearchResult>({
    transactions: [],
    wallets: [],
    categories: [],
});
const loading = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);

let debounceTimer: ReturnType<typeof setTimeout>;

watch(query, (q) => {
    clearTimeout(debounceTimer);
    if (q.length < 2) {
        results.value = { transactions: [], wallets: [], categories: [] };
        return;
    }
    loading.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/search?q=${encodeURIComponent(q)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            results.value = await res.json();
        } finally {
            loading.value = false;
        }
    }, 300);
});

const hasResults = () =>
    results.value.transactions.length +
        results.value.wallets.length +
        results.value.categories.length >
    0;

function goToTransaction(id: number) {
    emit('close');
    router.get(transactionsIndex(), { search: String(id) }, { replace: true });
}

function goToWallet() {
    emit('close');
    router.visit(walletsIndex());
}

function goToCategory(categoryId: number) {
    emit('close');
    router.get(
        transactionsIndex(),
        { category_id: String(categoryId) },
        { replace: true },
    );
}

function formatAmount(amount: number, currency = 'PKR') {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        emit('close');
    }
}

onMounted(() => {
    inputRef.value?.focus();
    document.addEventListener('keydown', onKeydown);
});
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 pt-[15vh] backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div
            class="w-full max-w-xl overflow-hidden rounded-2xl border border-sidebar-border bg-card shadow-2xl"
        >
            <!-- Input -->
            <div
                class="flex items-center gap-3 border-b border-sidebar-border/50 px-4 py-3"
            >
                <Search class="h-5 w-5 shrink-0 text-muted-foreground" />
                <input
                    ref="inputRef"
                    v-model="query"
                    type="text"
                    placeholder="Search transactions, wallets, categories…"
                    class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                />
                <div
                    v-if="loading"
                    class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-primary border-t-transparent"
                />
                <button
                    v-else
                    @click="$emit('close')"
                    class="shrink-0 text-muted-foreground hover:text-foreground"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Empty / hint -->
            <div
                v-if="query.length < 2"
                class="px-4 py-8 text-center text-sm text-muted-foreground"
            >
                Type at least 2 characters to search
            </div>

            <!-- No results -->
            <div
                v-else-if="!loading && !hasResults()"
                class="px-4 py-8 text-center text-sm text-muted-foreground"
            >
                No results for "{{ query }}"
            </div>

            <!-- Results -->
            <div
                v-else-if="hasResults()"
                class="max-h-96 divide-y divide-sidebar-border/30 overflow-y-auto"
            >
                <!-- Transactions -->
                <div v-if="results.transactions.length">
                    <p
                        class="px-4 pt-3 pb-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Transactions
                    </p>
                    <button
                        v-for="tx in results.transactions"
                        :key="tx.id"
                        @click="goToTransaction(tx.id)"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-accent"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :style="{
                                backgroundColor:
                                    (tx.category?.color ?? '#6b7280') + '20',
                            }"
                        >
                            <ArrowDownRight
                                v-if="tx.type === 'expense'"
                                class="h-3.5 w-3.5 text-red-400"
                            />
                            <ArrowUpRight
                                v-else
                                class="h-3.5 w-3.5 text-emerald-400"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{
                                    tx.description ||
                                    tx.category?.name ||
                                    'Transaction'
                                }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ tx.wallet?.name }} ·
                                {{ new Date(tx.date).toLocaleDateString() }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 text-sm font-semibold tabular-nums"
                            :class="
                                tx.type === 'expense'
                                    ? 'text-red-400'
                                    : 'text-emerald-400'
                            "
                        >
                            {{ tx.type === 'expense' ? '-' : '+'
                            }}{{ formatAmount(tx.amount, tx.currency) }}
                        </span>
                    </button>
                </div>

                <!-- Wallets -->
                <div v-if="results.wallets.length">
                    <p
                        class="px-4 pt-3 pb-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Wallets
                    </p>
                    <button
                        v-for="w in results.wallets"
                        :key="w.id"
                        @click="goToWallet()"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-accent"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :style="{ backgroundColor: w.color + '20' }"
                        >
                            <Wallet
                                class="h-3.5 w-3.5"
                                :style="{ color: w.color }"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ w.name }}
                            </p>
                            <p class="text-xs text-muted-foreground capitalize">
                                {{ w.type }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 text-sm font-semibold text-emerald-400 tabular-nums"
                            >{{ formatAmount(w.balance, w.currency) }}</span
                        >
                    </button>
                </div>

                <!-- Categories -->
                <div v-if="results.categories.length">
                    <p
                        class="px-4 pt-3 pb-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Categories
                    </p>
                    <button
                        v-for="cat in results.categories"
                        :key="cat.id"
                        @click="goToCategory(cat.id)"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-accent"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :style="{ backgroundColor: cat.color + '20' }"
                        >
                            <Tag
                                class="h-3.5 w-3.5"
                                :style="{ color: cat.color }"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ cat.name }}
                            </p>
                            <p class="text-xs text-muted-foreground capitalize">
                                {{ cat.type }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-between border-t border-sidebar-border/50 px-4 py-2 text-xs text-muted-foreground"
            >
                <span
                    >Press
                    <kbd
                        class="rounded border border-sidebar-border px-1 py-0.5 font-mono text-[10px]"
                        >Esc</kbd
                    >
                    to close</span
                >
                <span
                    ><kbd
                        class="rounded border border-sidebar-border px-1 py-0.5 font-mono text-[10px]"
                        >⌘K</kbd
                    >
                    to open</span
                >
            </div>
        </div>
    </div>
</template>
