<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    Circle,
    Plus,
    Receipt,
    Trash2,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { setLayoutProps } from '@inertiajs/vue3';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();
import { dashboard } from '@/routes';
import { index as groupsIndex, show as groupsShow } from '@/routes/groups';
import {
    destroy as destroyExpense,
    store as storeExpense,
} from '@/routes/groups/expenses';
import { settle as settleSplit } from '@/routes/groups/splits';

interface GroupInfo {
    id: number;
    name: string;
    description: string | null;
    currency: string;
}

interface Member {
    id: number;
    name: string;
    phone: string | null;
}

interface Split {
    id: number;
    group_member_id: number;
    member_name: string;
    amount: string;
    settled_at: string | null;
}

interface Expense {
    id: number;
    description: string;
    amount: string;
    currency: string;
    converted_amount: string | null;
    date: string;
    notes: string | null;
    paid_by_member_id: number;
    paid_by_name: string;
    splits: Split[];
}

interface Balance {
    from: number;
    to: number;
    fromName: string;
    toName: string;
    amount: number;
}

const props = defineProps<{
    group: GroupInfo;
    members: Member[];
    expenses: Expense[];
    balances: Balance[];
    currencies: Record<string, string>;
    defaultCurrency: string;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Groups', href: groupsIndex() },
        { title: props.group.name, href: groupsShow(props.group.id) },
    ],
});

const CURRENCIES = computed(() => Object.keys(props.currencies));
const showExpenseModal = ref(false);

const totalExpenses = computed(() =>
    props.expenses.reduce(
        (sum, e) => sum + Number(e.converted_amount ?? e.amount),
        0,
    ),
);

const perMemberShare = computed(() =>
    props.members.length > 0 ? totalExpenses.value / props.members.length : 0,
);

const form = useForm({
    paid_by_member_id: props.members[0]?.id ?? (null as number | null),
    amount: '' as number | '',
    currency: props.group.currency,
    description: '',
    date: new Date().toISOString().slice(0, 10),
    notes: '',
    splits: props.members.map((m) => ({
        group_member_id: m.id,
        amount: '' as number | '',
    })),
});

function openExpenseModal() {
    form.reset();
    form.currency = props.group.currency;
    form.paid_by_member_id = props.members[0]?.id ?? null;
    form.date = new Date().toISOString().slice(0, 10);
    form.splits = props.members.map((m) => ({
        group_member_id: m.id,
        amount: '' as number | '',
    }));
    showExpenseModal.value = true;
}

function splitEvenly() {
    if (!form.amount || props.members.length === 0) {
        return;
    }
    const share =
        Math.round((Number(form.amount) / props.members.length) * 100) / 100;
    form.splits = form.splits.map((s) => ({ ...s, amount: share }));
}

function submitExpense() {
    form.post(storeExpense(props.group.id).url, {
        onSuccess: () => {
            showExpenseModal.value = false;
            form.reset();
        },
    });
}

async function deleteExpense(expenseId: number) {
    const ok = await confirm(
        'This expense and all its splits will be permanently removed.',
        {
            title: 'Delete expense?',
            confirmText: 'Delete',
        },
    );
    if (!ok) {
        return;
    }
    router.delete(
        destroyExpense({ group: props.group.id, expense: expenseId }).url,
        { preserveScroll: true },
    );
}

function toggleSplit(splitId: number) {
    router.patch(
        settleSplit({ group: props.group.id, split: splitId }).url,
        {},
        { preserveScroll: true },
    );
}

function fmt(amount: number | string, currency?: string) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: currency ?? props.group.currency,
        maximumFractionDigits: 0,
    }).format(Number(amount));
}

function formatDate(d: string) {
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function memberName(id: number) {
    return props.members.find((m) => m.id === id)?.name ?? '—';
}

const settledSplitsCount = computed(
    () =>
        props.expenses.flatMap((e) => e.splits).filter((s) => s.settled_at)
            .length,
);
const totalSplitsCount = computed(
    () => props.expenses.flatMap((e) => e.splits).length,
);
</script>

<template>
    <Head :title="group.name" />

    <div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight">
                    {{ group.name }}
                </h1>
                <p
                    v-if="group.description"
                    class="text-sm text-muted-foreground"
                >
                    {{ group.description }}
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    {{ members.length }} members · {{ group.currency }}
                </p>
            </div>
            <button
                @click="openExpenseModal"
                class="flex items-center gap-1.5 rounded-xl bg-orange-600 px-3 py-2 text-xs font-semibold text-white shadow-md shadow-orange-500/20 transition-colors hover:bg-orange-500"
            >
                <Plus class="h-3.5 w-3.5" /> Add Expense
            </button>
        </div>

        <!-- Stats -->
        <div class="grid gap-3 sm:grid-cols-3">
            <div
                class="rounded-xl border border-orange-500/20 bg-orange-500/5 p-4"
            >
                <p class="text-xs text-muted-foreground">Total Expenses</p>
                <p class="mt-1.5 text-xl font-bold text-orange-400">
                    {{ fmt(totalExpenses) }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{ expenses.length }} expense{{
                        expenses.length !== 1 ? 's' : ''
                    }}
                </p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Per Member Share</p>
                <p class="mt-1.5 text-xl font-bold">
                    {{ fmt(perMemberShare) }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    even split · {{ members.length }} people
                </p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Settlement Progress</p>
                <p class="mt-1.5 text-xl font-bold">
                    {{ settledSplitsCount }}/{{ totalSplitsCount }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    splits settled
                </p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <!-- Expenses list -->
            <div class="lg:col-span-2">
                <div class="mb-3 flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-500/15"
                    >
                        <Receipt class="h-3.5 w-3.5 text-orange-400" />
                    </div>
                    <h2 class="text-sm font-semibold">Expenses</h2>
                </div>

                <div
                    v-if="expenses.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 py-12 text-center"
                >
                    <Receipt class="mb-2 h-8 w-8 text-muted-foreground/30" />
                    <p class="text-sm text-muted-foreground">No expenses yet</p>
                    <button
                        @click="openExpenseModal"
                        class="mt-2 text-xs text-orange-400 hover:text-orange-300"
                    >
                        + Add first expense
                    </button>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="group rounded-xl border border-sidebar-border/50 bg-card p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-baseline justify-between gap-2"
                                >
                                    <p class="truncate font-semibold">
                                        {{ expense.description }}
                                    </p>
                                    <div class="shrink-0 text-right">
                                        <p
                                            class="font-bold text-orange-400 tabular-nums"
                                        >
                                            {{
                                                fmt(
                                                    expense.converted_amount ??
                                                        expense.amount,
                                                )
                                            }}
                                        </p>
                                        <p
                                            v-if="
                                                expense.currency !==
                                                group.currency
                                            "
                                            class="text-[10px] text-muted-foreground"
                                        >
                                            {{
                                                fmt(
                                                    expense.amount,
                                                    expense.currency,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <span
                                        >Paid by
                                        <strong class="text-foreground">{{
                                            expense.paid_by_name
                                        }}</strong></span
                                    >
                                    <span
                                        >· {{ formatDate(expense.date) }}</span
                                    >
                                </div>

                                <!-- Splits -->
                                <div class="mt-3 space-y-1">
                                    <div
                                        v-for="split in expense.splits"
                                        :key="split.id"
                                        class="flex items-center gap-2"
                                    >
                                        <button
                                            @click="toggleSplit(split.id)"
                                            class="shrink-0 transition-transform hover:scale-110"
                                            :title="
                                                split.settled_at
                                                    ? 'Mark as unsettled'
                                                    : 'Mark as settled'
                                            "
                                        >
                                            <CheckCircle2
                                                v-if="split.settled_at"
                                                class="h-4 w-4 text-emerald-400"
                                            />
                                            <Circle
                                                v-else
                                                class="h-4 w-4 text-muted-foreground/40 hover:text-emerald-400"
                                            />
                                        </button>
                                        <span
                                            class="min-w-0 flex-1 truncate text-xs"
                                            :class="
                                                split.settled_at
                                                    ? 'text-muted-foreground/50 line-through'
                                                    : 'text-muted-foreground'
                                            "
                                        >
                                            {{ split.member_name }}
                                        </span>
                                        <span
                                            class="text-xs font-medium tabular-nums"
                                            :class="
                                                split.settled_at
                                                    ? 'text-muted-foreground/50 line-through'
                                                    : ''
                                            "
                                        >
                                            {{ fmt(split.amount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button
                                @click="deleteExpense(expense.id)"
                                class="mt-0.5 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-red-400"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Members + Balances -->
            <div class="space-y-5">
                <!-- Members -->
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/15"
                        >
                            <Users class="h-3.5 w-3.5 text-blue-400" />
                        </div>
                        <h2 class="text-sm font-semibold">Members</h2>
                        <span
                            class="rounded-full bg-sidebar-accent/60 px-2 py-0.5 text-[10px] font-medium"
                            >{{ members.length }}</span
                        >
                    </div>
                    <div class="space-y-1.5">
                        <div
                            v-for="member in members"
                            :key="member.id"
                            class="flex items-center gap-2.5 rounded-lg border border-sidebar-border/40 bg-card px-3 py-2"
                        >
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sidebar-accent text-[10px] font-bold uppercase"
                            >
                                {{ member.name.slice(0, 2) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium">
                                    {{ member.name }}
                                </p>
                                <p
                                    v-if="member.phone"
                                    class="text-[10px] text-muted-foreground"
                                >
                                    {{ member.phone }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Balances / Who Owes Whom -->
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/15"
                        >
                            <ArrowRight class="h-3.5 w-3.5 text-emerald-400" />
                        </div>
                        <h2 class="text-sm font-semibold">Who Owes Whom</h2>
                    </div>

                    <div
                        v-if="balances.length === 0"
                        class="rounded-xl border border-dashed border-sidebar-border/50 py-6 text-center"
                    >
                        <CheckCircle2
                            class="mx-auto mb-1.5 h-6 w-6 text-emerald-400/60"
                        />
                        <p class="text-xs text-muted-foreground">
                            All settled up!
                        </p>
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="(balance, i) in balances"
                            :key="i"
                            class="rounded-xl border border-sidebar-border/40 bg-card p-3"
                        >
                            <div class="flex items-center gap-2 text-xs">
                                <span class="font-semibold text-red-400">{{
                                    balance.fromName
                                }}</span>
                                <ArrowRight
                                    class="h-3 w-3 shrink-0 text-muted-foreground/50"
                                />
                                <span class="font-semibold text-emerald-400">{{
                                    balance.toName
                                }}</span>
                            </div>
                            <p class="mt-1 text-sm font-bold tabular-nums">
                                {{ fmt(balance.amount) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <Teleport to="body">
        <div
            v-if="showExpenseModal"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
            @click.self="showExpenseModal = false"
        >
            <div
                class="my-8 w-full max-w-md overflow-hidden rounded-2xl border border-sidebar-border bg-card shadow-2xl"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/50 px-5 py-4"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-500/15"
                        >
                            <Receipt class="h-4 w-4 text-orange-400" />
                        </div>
                        <p class="font-semibold">Add Expense</p>
                    </div>
                    <button
                        @click="showExpenseModal = false"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitExpense" class="space-y-4 p-5">
                    <!-- Description -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-muted-foreground"
                            >What for? *</label
                        >
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Dinner, taxi, hotel..."
                            required
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                        />
                        <p
                            v-if="form.errors.description"
                            class="mt-1 text-xs text-red-400"
                        >
                            {{ form.errors.description }}
                        </p>
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
                    </div>

                    <!-- Paid by -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-muted-foreground"
                            >Paid by *</label
                        >
                        <select
                            v-model="form.paid_by_member_id"
                            required
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                        >
                            <option
                                v-for="m in members"
                                :key="m.id"
                                :value="m.id"
                            >
                                {{ m.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.paid_by_member_id"
                            class="mt-1 text-xs text-red-400"
                        >
                            {{ form.errors.paid_by_member_id }}
                        </p>
                    </div>

                    <!-- Date -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-muted-foreground"
                            >Date *</label
                        >
                        <input
                            v-model="form.date"
                            type="date"
                            required
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                        />
                    </div>

                    <!-- Splits -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                >Split amounts *</label
                            >
                            <button
                                type="button"
                                @click="splitEvenly"
                                class="text-xs text-orange-400 hover:text-orange-300"
                            >
                                Split evenly
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="(split, index) in form.splits"
                                :key="index"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="min-w-0 flex-1 truncate text-xs font-medium"
                                    >{{
                                        memberName(split.group_member_id)
                                    }}</span
                                >
                                <input
                                    v-model="split.amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="w-28 rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-right text-sm outline-none focus:border-primary/60"
                                />
                            </div>
                        </div>
                        <p
                            v-if="form.errors['splits']"
                            class="mt-1 text-xs text-red-400"
                        >
                            {{ form.errors['splits'] }}
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-muted-foreground"
                            >Notes (optional)</label
                        >
                        <input
                            v-model="form.notes"
                            type="text"
                            placeholder="Any details..."
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-1">
                        <button
                            type="button"
                            @click="showExpenseModal = false"
                            class="flex-1 rounded-xl border border-sidebar-border/50 py-2.5 text-sm font-semibold transition-colors hover:bg-accent"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 rounded-xl bg-orange-600 py-2.5 text-sm font-semibold text-white shadow-md shadow-orange-500/20 transition-colors hover:bg-orange-500 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Saving…' : 'Add Expense' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
