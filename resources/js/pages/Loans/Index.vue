<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDownLeft,
    ArrowUpRight,
    CheckCircle2,
    Circle,
    Clock,
    Phone,
    Plus,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { dashboard } from '@/routes';
import {
    destroy as loanDestroy,
    index as loansIndex,
    settle as loanSettle,
    store as loansStore,
} from '@/routes/loans';

const { confirm } = useConfirm();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Loans', href: loansIndex() },
        ],
    },
});

interface Loan {
    id: number;
    contact_name: string;
    contact_phone: string | null;
    type: 'lent' | 'borrowed';
    amount: string;
    currency: string;
    converted_amount: string | null;
    description: string | null;
    due_date: string | null;
    settled_at: string | null;
    notes: string | null;
    created_at: string;
}

const props = defineProps<{
    lent: Loan[];
    borrowed: Loan[];
    summary: { totalLent: number; totalBorrowed: number; settledCount: number };
    defaultCurrency: string;
    currencies: Record<string, string>;
}>();

const page = usePage<{ userCurrency: string }>();
const CURRENCIES = computed(() => Object.keys(props.currencies));

const showModal = ref(false);
const defaultType = ref<'lent' | 'borrowed'>('lent');

const form = useForm({
    contact_name: '',
    contact_phone: '',
    type: 'lent' as 'lent' | 'borrowed',
    amount: '' as number | '',
    currency: props.defaultCurrency,
    description: '',
    due_date: '',
    notes: '',
});

function openModal(type: 'lent' | 'borrowed') {
    form.reset();
    form.type = type;
    form.currency = props.defaultCurrency;
    defaultType.value = type;
    showModal.value = true;
}

function submit() {
    form.post(loansStore().url, {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

function toggleSettle(loan: Loan) {
    router.patch(loanSettle(loan.id).url, {}, { preserveScroll: true });
}

async function deleteLoan(id: number) {
    const ok = await confirm('This loan entry will be permanently removed.', {
        title: 'Delete loan entry?',
        confirmText: 'Delete',
    });
    if (!ok) {
        return;
    }
    router.delete(loanDestroy(id).url, { preserveScroll: true });
}

function fmt(amount: number | string, currency?: string) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: currency ?? props.defaultCurrency,
        maximumFractionDigits: 0,
    }).format(Number(amount));
}

function formatDate(d: string | null) {
    if (!d) {
        return null;
    }
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function isOverdue(loan: Loan) {
    return (
        !loan.settled_at &&
        loan.due_date &&
        new Date(loan.due_date) < new Date()
    );
}

function daysOverdue(loan: Loan) {
    if (!loan.due_date) {
        return 0;
    }
    return Math.floor(
        (Date.now() - new Date(loan.due_date).getTime()) / 86400000,
    );
}

function effectiveAmount(loan: Loan) {
    return Number(loan.converted_amount ?? loan.amount);
}
</script>

<template>
    <Head title="Loans" />

    <div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight">Loans & Debts</h1>
                <p class="text-sm text-muted-foreground">
                    Track money you've lent and borrowed
                </p>
            </div>
            <div class="flex gap-2">
                <button
                    @click="openModal('lent')"
                    class="flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-md shadow-emerald-500/20 transition-colors hover:bg-emerald-500"
                >
                    <ArrowUpRight class="h-3.5 w-3.5" /> I Lent
                </button>
                <button
                    @click="openModal('borrowed')"
                    class="flex items-center gap-1.5 rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-md shadow-red-500/20 transition-colors hover:bg-red-500"
                >
                    <ArrowDownLeft class="h-3.5 w-3.5" /> I Borrowed
                </button>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid gap-3 sm:grid-cols-3">
            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4"
            >
                <p class="text-xs text-muted-foreground">They Owe Me</p>
                <p class="mt-1.5 text-xl font-bold text-emerald-400">
                    {{ fmt(summary.totalLent) }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{ lent.filter((l) => !l.settled_at).length }} outstanding
                </p>
            </div>
            <div class="rounded-xl border border-red-500/20 bg-red-500/5 p-4">
                <p class="text-xs text-muted-foreground">I Owe</p>
                <p class="mt-1.5 text-xl font-bold text-red-400">
                    {{ fmt(summary.totalBorrowed) }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{ borrowed.filter((l) => !l.settled_at).length }}
                    outstanding
                </p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Net Position</p>
                <p
                    class="mt-1.5 text-xl font-bold"
                    :class="
                        summary.totalLent >= summary.totalBorrowed
                            ? 'text-emerald-400'
                            : 'text-red-400'
                    "
                >
                    {{
                        fmt(Math.abs(summary.totalLent - summary.totalBorrowed))
                    }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{
                        summary.totalLent >= summary.totalBorrowed
                            ? 'Net owed to you'
                            : 'Net you owe'
                    }}
                </p>
            </div>
        </div>

        <!-- Two columns: lent / borrowed -->
        <div class="grid gap-5 lg:grid-cols-2">
            <!-- They Owe Me (Lent) -->
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/15"
                    >
                        <ArrowUpRight class="h-3.5 w-3.5 text-emerald-400" />
                    </div>
                    <h2 class="text-sm font-semibold">They Owe Me</h2>
                    <span
                        class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-medium text-emerald-400"
                    >
                        {{ lent.filter((l) => !l.settled_at).length }} active
                    </span>
                </div>

                <div
                    v-if="lent.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 py-12 text-center"
                >
                    <ArrowUpRight
                        class="mb-2 h-8 w-8 text-muted-foreground/30"
                    />
                    <p class="text-sm text-muted-foreground">
                        No lending records
                    </p>
                    <button
                        @click="openModal('lent')"
                        class="mt-2 text-xs text-emerald-400 hover:text-emerald-300"
                    >
                        + Record money you lent
                    </button>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="loan in lent"
                        :key="loan.id"
                        class="group relative rounded-xl border bg-card p-4 transition-all"
                        :class="[
                            loan.settled_at
                                ? 'border-sidebar-border/30 opacity-60'
                                : 'border-sidebar-border/50',
                            isOverdue(loan) && !loan.settled_at
                                ? 'border-red-500/30 bg-red-500/5'
                                : '',
                        ]"
                    >
                        <!-- Overdue badge -->
                        <div
                            v-if="isOverdue(loan) && !loan.settled_at"
                            class="mb-2 flex items-center gap-1 text-xs text-red-400"
                        >
                            <AlertTriangle class="h-3 w-3" />
                            {{ daysOverdue(loan) }}d overdue
                        </div>

                        <div class="flex items-start gap-3">
                            <!-- Settle toggle -->
                            <button
                                @click="toggleSettle(loan)"
                                class="mt-0.5 shrink-0 transition-transform hover:scale-110"
                                :title="
                                    loan.settled_at
                                        ? 'Mark as outstanding'
                                        : 'Mark as settled'
                                "
                            >
                                <CheckCircle2
                                    v-if="loan.settled_at"
                                    class="h-5 w-5 text-emerald-400"
                                />
                                <Circle
                                    v-else
                                    class="h-5 w-5 text-muted-foreground/40 hover:text-emerald-400"
                                />
                            </button>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-baseline justify-between gap-2"
                                >
                                    <p
                                        class="truncate text-sm font-semibold"
                                        :class="
                                            loan.settled_at
                                                ? 'text-muted-foreground line-through'
                                                : ''
                                        "
                                    >
                                        {{ loan.contact_name }}
                                    </p>
                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-sm font-bold text-emerald-400 tabular-nums"
                                        >
                                            {{ fmt(effectiveAmount(loan)) }}
                                        </p>
                                        <p
                                            v-if="
                                                loan.currency !==
                                                defaultCurrency
                                            "
                                            class="text-[10px] text-muted-foreground"
                                        >
                                            {{
                                                fmt(loan.amount, loan.currency)
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <p
                                    v-if="loan.description"
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{ loan.description }}
                                </p>
                                <div
                                    class="mt-1.5 flex flex-wrap items-center gap-2 text-[10px] text-muted-foreground"
                                >
                                    <span
                                        v-if="loan.contact_phone"
                                        class="flex items-center gap-0.5"
                                    >
                                        <Phone class="h-2.5 w-2.5" />
                                        {{ loan.contact_phone }}
                                    </span>
                                    <span
                                        v-if="loan.due_date"
                                        class="flex items-center gap-0.5"
                                        :class="
                                            isOverdue(loan) && !loan.settled_at
                                                ? 'text-red-400'
                                                : ''
                                        "
                                    >
                                        <Clock class="h-2.5 w-2.5" /> Due
                                        {{ formatDate(loan.due_date) }}
                                    </span>
                                    <span
                                        v-if="loan.settled_at"
                                        class="text-emerald-400"
                                        >✓ Settled
                                        {{ formatDate(loan.settled_at) }}</span
                                    >
                                </div>
                            </div>

                            <!-- Delete -->
                            <button
                                @click="deleteLoan(loan.id)"
                                class="shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-red-400"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- I Owe (Borrowed) -->
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-red-500/15"
                    >
                        <ArrowDownLeft class="h-3.5 w-3.5 text-red-400" />
                    </div>
                    <h2 class="text-sm font-semibold">I Owe</h2>
                    <span
                        class="rounded-full bg-red-500/15 px-2 py-0.5 text-[10px] font-medium text-red-400"
                    >
                        {{ borrowed.filter((l) => !l.settled_at).length }}
                        active
                    </span>
                </div>

                <div
                    v-if="borrowed.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 py-12 text-center"
                >
                    <ArrowDownLeft
                        class="mb-2 h-8 w-8 text-muted-foreground/30"
                    />
                    <p class="text-sm text-muted-foreground">
                        No borrowing records
                    </p>
                    <button
                        @click="openModal('borrowed')"
                        class="mt-2 text-xs text-red-400 hover:text-red-300"
                    >
                        + Record money you borrowed
                    </button>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="loan in borrowed"
                        :key="loan.id"
                        class="group relative rounded-xl border bg-card p-4 transition-all"
                        :class="[
                            loan.settled_at
                                ? 'border-sidebar-border/30 opacity-60'
                                : 'border-sidebar-border/50',
                            isOverdue(loan) && !loan.settled_at
                                ? 'border-orange-500/30 bg-orange-500/5'
                                : '',
                        ]"
                    >
                        <!-- Overdue badge -->
                        <div
                            v-if="isOverdue(loan) && !loan.settled_at"
                            class="mb-2 flex items-center gap-1 text-xs text-orange-400"
                        >
                            <AlertTriangle class="h-3 w-3" />
                            {{ daysOverdue(loan) }}d overdue · Pay back soon
                        </div>

                        <div class="flex items-start gap-3">
                            <!-- Settle toggle -->
                            <button
                                @click="toggleSettle(loan)"
                                class="mt-0.5 shrink-0 transition-transform hover:scale-110"
                                :title="
                                    loan.settled_at
                                        ? 'Mark as outstanding'
                                        : 'Mark as paid back'
                                "
                            >
                                <CheckCircle2
                                    v-if="loan.settled_at"
                                    class="h-5 w-5 text-emerald-400"
                                />
                                <Circle
                                    v-else
                                    class="h-5 w-5 text-muted-foreground/40 hover:text-emerald-400"
                                />
                            </button>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-baseline justify-between gap-2"
                                >
                                    <p
                                        class="truncate text-sm font-semibold"
                                        :class="
                                            loan.settled_at
                                                ? 'text-muted-foreground line-through'
                                                : ''
                                        "
                                    >
                                        {{ loan.contact_name }}
                                    </p>
                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-sm font-bold text-red-400 tabular-nums"
                                        >
                                            {{ fmt(effectiveAmount(loan)) }}
                                        </p>
                                        <p
                                            v-if="
                                                loan.currency !==
                                                defaultCurrency
                                            "
                                            class="text-[10px] text-muted-foreground"
                                        >
                                            {{
                                                fmt(loan.amount, loan.currency)
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <p
                                    v-if="loan.description"
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{ loan.description }}
                                </p>
                                <div
                                    class="mt-1.5 flex flex-wrap items-center gap-2 text-[10px] text-muted-foreground"
                                >
                                    <span
                                        v-if="loan.contact_phone"
                                        class="flex items-center gap-0.5"
                                    >
                                        <Phone class="h-2.5 w-2.5" />
                                        {{ loan.contact_phone }}
                                    </span>
                                    <span
                                        v-if="loan.due_date"
                                        class="flex items-center gap-0.5"
                                        :class="
                                            isOverdue(loan) && !loan.settled_at
                                                ? 'text-orange-400'
                                                : ''
                                        "
                                    >
                                        <Clock class="h-2.5 w-2.5" /> Pay by
                                        {{ formatDate(loan.due_date) }}
                                    </span>
                                    <span
                                        v-if="loan.settled_at"
                                        class="text-emerald-400"
                                        >✓ Paid back
                                        {{ formatDate(loan.settled_at) }}</span
                                    >
                                </div>
                            </div>

                            <!-- Delete -->
                            <button
                                @click="deleteLoan(loan.id)"
                                class="shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-red-400"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Loan Modal -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="showModal = false"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl border border-sidebar-border bg-card shadow-2xl"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/50 px-5 py-4"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg"
                            :class="
                                form.type === 'lent'
                                    ? 'bg-emerald-500/15'
                                    : 'bg-red-500/15'
                            "
                        >
                            <ArrowUpRight
                                v-if="form.type === 'lent'"
                                class="h-4 w-4 text-emerald-400"
                            />
                            <ArrowDownLeft
                                v-else
                                class="h-4 w-4 text-red-400"
                            />
                        </div>
                        <p class="font-semibold">
                            {{
                                form.type === 'lent'
                                    ? 'I Lent Money'
                                    : 'I Borrowed Money'
                            }}
                        </p>
                    </div>
                    <button
                        @click="showModal = false"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 p-5">
                    <!-- Type toggle -->
                    <div class="flex rounded-lg border border-input p-1">
                        <button
                            v-for="opt in [
                                { key: 'lent', label: 'I Lent' },
                                { key: 'borrowed', label: 'I Borrowed' },
                            ]"
                            :key="opt.key"
                            type="button"
                            @click="form.type = opt.key as 'lent' | 'borrowed'"
                            class="flex-1 rounded-md py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                form.type === opt.key
                                    ? opt.key === 'lent'
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-red-600 text-white'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <!-- Person -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-medium text-muted-foreground"
                                >Person *</label
                            >
                            <input
                                v-model="form.contact_name"
                                type="text"
                                placeholder="Ali, Sara..."
                                required
                                class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                            />
                            <p
                                v-if="form.errors.contact_name"
                                class="mt-1 text-xs text-red-400"
                            >
                                {{ form.errors.contact_name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-medium text-muted-foreground"
                                >Phone (optional)</label
                            >
                            <input
                                v-model="form.contact_phone"
                                type="tel"
                                placeholder="+92 300..."
                                class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <!-- Amount + Currency -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-muted-foreground"
                            >Amount *</label
                        >
                        <div class="flex gap-1.5">
                            <select
                                v-model="form.currency"
                                class="rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-2 py-2 text-xs outline-none focus:border-primary/60"
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
                                class="min-w-0 flex-1 rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                            />
                        </div>
                        <p
                            v-if="form.errors.amount"
                            class="mt-1 text-xs text-red-400"
                        >
                            {{ form.errors.amount }}
                        </p>
                        <p
                            v-if="form.currency !== defaultCurrency"
                            class="mt-1 text-xs text-primary"
                        >
                            Auto-converted to {{ defaultCurrency }} for reports
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-muted-foreground"
                            >What for?</label
                        >
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Rent, trip, emergency..."
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                        />
                    </div>

                    <!-- Due date -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-muted-foreground"
                        >
                            {{
                                form.type === 'lent'
                                    ? 'Expected back by'
                                    : 'Pay back by'
                            }}
                            (optional)
                        </label>
                        <input
                            v-model="form.due_date"
                            type="date"
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-1">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="flex-1 rounded-xl border border-sidebar-border/50 py-2.5 text-sm font-semibold transition-colors hover:bg-accent"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white shadow-md transition-colors disabled:opacity-60"
                            :class="
                                form.type === 'lent'
                                    ? 'bg-emerald-600 shadow-emerald-500/20 hover:bg-emerald-500'
                                    : 'bg-red-600 shadow-red-500/20 hover:bg-red-500'
                            "
                        >
                            {{
                                form.processing
                                    ? 'Saving…'
                                    : form.type === 'lent'
                                      ? 'Save – I Lent'
                                      : 'Save – I Borrowed'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
