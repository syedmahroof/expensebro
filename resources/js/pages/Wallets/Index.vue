<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Banknote,
    Bitcoin,
    Building2,
    CreditCard,
    Plus,
    Wallet,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Wallet as WalletType } from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import {
    destroy,
    index as walletsIndex,
    store,
    update,
} from '@/routes/wallets';
import { dashboard } from '@/routes';

const { confirm } = useConfirm();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Wallets', href: walletsIndex() },
        ],
    },
});

defineProps<{ wallets: WalletType[] }>();

const page = usePage<{
    userCurrency: string;
    currencies: Record<string, string>;
}>();
const defaultCurrency = computed(() => page.props.userCurrency ?? 'PKR');
const currencies = computed(() => page.props.currencies ?? {});

const showModal = ref(false);
const editingWallet = ref<WalletType | null>(null);

const form = useForm({
    name: '',
    type: 'cash' as WalletType['type'],
    currency: defaultCurrency.value,
    balance: 0,
    color: '#6366f1',
    icon: 'wallet',
    is_default: false,
});

function openCreate() {
    editingWallet.value = null;
    form.reset();
    form.currency = defaultCurrency.value;
    showModal.value = true;
}

function openEdit(wallet: WalletType) {
    editingWallet.value = wallet;
    form.name = wallet.name;
    form.type = wallet.type;
    form.currency = wallet.currency;
    form.balance = wallet.balance;
    form.color = wallet.color;
    form.icon = wallet.icon;
    form.is_default = wallet.is_default;
    showModal.value = true;
}

function submit() {
    if (editingWallet.value) {
        form.put(update({ wallet: editingWallet.value.id }).url, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

async function archiveWallet(id: number) {
    const ok = await confirm(
        'This wallet will be archived and hidden from your active list.',
        {
            title: 'Archive wallet?',
            confirmText: 'Archive',
            danger: false,
        },
    );
    if (!ok) {
        return;
    }
    form.delete(destroy({ wallet: id }).url, { preserveScroll: true });
}

function formatAmount(amount: number, currency = 'PKR') {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
}

const walletTypeIcons: Record<string, typeof Wallet> = {
    cash: Banknote,
    bank: Building2,
    card: CreditCard,
    crypto: Bitcoin,
    other: Wallet,
};

const walletColors = [
    '#6366f1',
    '#10b981',
    '#f59e0b',
    '#ef4444',
    '#3b82f6',
    '#8b5cf6',
    '#ec4899',
    '#06b6d4',
];
</script>

<template>
    <Head title="Wallets" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Wallets</h1>
                <p class="text-sm text-muted-foreground">
                    Manage your accounts and balances
                </p>
            </div>
            <button
                @click="openCreate"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
            >
                <Plus class="h-4 w-4" />
                Add Wallet
            </button>
        </div>

        <!-- Total Balance -->
        <div class="rounded-xl border border-sidebar-border/50 bg-card p-6">
            <p class="mb-1 text-sm text-muted-foreground">Total Balance</p>
            <p class="text-3xl font-semibold tracking-tight">
                {{
                    formatAmount(
                        wallets.reduce((sum, w) => sum + Number(w.balance), 0),
                    )
                }}
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                Across {{ wallets.length }} wallet{{
                    wallets.length !== 1 ? 's' : ''
                }}
            </p>
        </div>

        <!-- Wallets Grid -->
        <div
            v-if="wallets.length === 0"
            class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 p-12 text-center"
        >
            <div>
                <Wallet
                    class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40"
                />
                <p class="mb-2 text-sm text-muted-foreground">
                    No wallets yet.
                </p>
                <button
                    @click="openCreate"
                    class="text-xs text-primary hover:text-primary/80"
                >
                    Add your first wallet →
                </button>
            </div>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="wallet in wallets"
                :key="wallet.id"
                class="group relative rounded-xl border border-sidebar-border/50 bg-card p-5 transition-shadow hover:shadow-md"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl"
                        :style="{ backgroundColor: wallet.color + '25' }"
                    >
                        <component
                            :is="walletTypeIcons[wallet.type] ?? Wallet"
                            class="h-5 w-5"
                            :style="{ color: wallet.color }"
                        />
                    </div>
                    <span
                        v-if="wallet.is_default"
                        class="rounded-full bg-primary/20 px-2 py-0.5 text-xs text-primary"
                        >Default</span
                    >
                </div>

                <p class="text-base font-semibold">{{ wallet.name }}</p>
                <p class="mb-3 text-xs text-muted-foreground capitalize">
                    {{ wallet.type }}
                </p>
                <p class="text-xl font-bold tabular-nums">
                    {{ formatAmount(wallet.balance, wallet.currency) }}
                </p>

                <div
                    class="mt-4 flex gap-2 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <button
                        @click="openEdit(wallet)"
                        class="text-xs text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Edit
                    </button>
                    <span class="text-xs text-muted-foreground">·</span>
                    <button
                        @click="archiveWallet(wallet.id)"
                        class="text-xs text-red-400/70 transition-colors hover:text-red-400"
                    >
                        Archive
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-sidebar-border bg-card p-6 shadow-2xl"
            >
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-base font-semibold">
                        {{ editingWallet ? 'Edit Wallet' : 'New Wallet' }}
                    </h2>
                    <button
                        @click="showModal = false"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium"
                            >Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Cash, HDFC Bank..."
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-400"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium"
                                >Type</label
                            >
                            <select
                                v-model="form.type"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="card">Card</option>
                                <option value="crypto">Crypto</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium"
                                >Currency</label
                            >
                            <select
                                v-model="form.currency"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option
                                    v-for="(name, code) in currencies"
                                    :key="code"
                                    :value="code"
                                >
                                    {{ code }} — {{ name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="!editingWallet">
                        <label class="mb-1.5 block text-sm font-medium"
                            >Opening Balance</label
                        >
                        <input
                            v-model="form.balance"
                            type="number"
                            step="0.01"
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium"
                            >Color</label
                        >
                        <div class="flex gap-2">
                            <button
                                v-for="c in walletColors"
                                :key="c"
                                type="button"
                                @click="form.color = c"
                                class="h-7 w-7 rounded-full transition-transform hover:scale-110"
                                :style="{ backgroundColor: c }"
                                :class="
                                    form.color === c
                                        ? 'ring-2 ring-white/50 ring-offset-2 ring-offset-card'
                                        : ''
                                "
                            />
                        </div>
                    </div>

                    <label class="flex items-center gap-2">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            class="rounded"
                        />
                        <span class="text-sm">Set as default wallet</span>
                    </label>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="h-10 flex-1 rounded-lg border border-input text-sm transition-colors hover:bg-accent"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="h-10 flex-1 rounded-lg bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
                        >
                            {{
                                form.processing
                                    ? 'Saving...'
                                    : editingWallet
                                      ? 'Update'
                                      : 'Create'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
