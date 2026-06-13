<script setup lang="ts">
import { Head, useHttp, useForm } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    Bot,
    Check,
    Edit3,
    Loader2,
    Mic,
    Send,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { nextTick, ref } from 'vue';
import type { Category, Wallet } from '@/types';
import { parse, save, index as aiIndex } from '@/routes/ai';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'AI Chat', href: aiIndex() },
        ],
    },
});

const props = defineProps<{
    wallets: Wallet[];
    categories: Category[];
}>();

interface ParsedExpense {
    amount: number;
    type: 'expense' | 'income' | 'transfer';
    description: string;
    category: string;
    date: string;
}

interface Message {
    id: number;
    role: 'user' | 'assistant';
    text: string;
    parsed?: ParsedExpense;
    saved?: boolean;
    editing?: boolean;
}

const messages = ref<Message[]>([
    {
        id: 0,
        role: 'assistant',
        text: 'Hi! Tell me about an expense or income. For example: "coffee 120", "uber 450", "salary 50000"',
    },
]);

const input = ref('');
const isLoading = ref(false);
const messagesEnd = ref<HTMLDivElement>();
const defaultWalletId = ref(
    props.wallets.find((w) => w.is_default)?.id ?? props.wallets[0]?.id,
);

const http = useHttp({ message: '' });

async function sendMessage() {
    const text = input.value.trim();
    if (!text || isLoading.value) {
        return;
    }

    messages.value.push({ id: Date.now(), role: 'user', text });
    input.value = '';
    isLoading.value = true;
    await scrollToBottom();

    try {
        const response = await fetch(parse().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ message: text }),
        });

        const parsed: ParsedExpense = await response.json();

        const matchedCategory = props.categories.find((c) =>
            c.name.toLowerCase().includes(parsed.category?.toLowerCase() ?? ''),
        );

        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: `Got it! Here's what I parsed:`,
            parsed: {
                ...parsed,
                category: matchedCategory?.name ?? parsed.category,
            },
        });
    } catch {
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: 'Sorry, I had trouble parsing that. Please try again.',
        });
    } finally {
        isLoading.value = false;
        await scrollToBottom();
    }
}

async function saveTransaction(msg: Message) {
    if (!msg.parsed || !defaultWalletId.value) {
        return;
    }

    const matchedCategory = props.categories.find(
        (c) => c.name === msg.parsed!.category,
    );

    try {
        const response = await fetch(save().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                wallet_id: defaultWalletId.value,
                category_id: matchedCategory?.id ?? null,
                type: msg.parsed.type,
                amount: msg.parsed.amount,
                description: msg.parsed.description,
                date: msg.parsed.date,
            }),
        });

        if (response.ok) {
            msg.saved = true;
            messages.value.push({
                id: Date.now(),
                role: 'assistant',
                text: '✓ Transaction saved successfully!',
            });
            await scrollToBottom();
        }
    } catch {
        messages.value.push({
            id: Date.now(),
            role: 'assistant',
            text: 'Failed to save. Please try again.',
        });
    }
}

function discardParsed(msg: Message) {
    msg.parsed = undefined;
    msg.text = 'Discarded. Try rephrasing your expense.';
}

async function scrollToBottom() {
    await nextTick();
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' });
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function formatAmount(amount: number) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        maximumFractionDigits: 0,
    }).format(amount);
}

const examples = [
    'coffee 120',
    'uber 450 food',
    'salary 50000',
    'petrol 2000',
    'netflix 1200',
];
</script>

<template>
    <Head title="AI Chat" />

    <div class="flex h-full flex-1 flex-col">
        <!-- Header -->
        <div class="border-b border-sidebar-border/50 px-4 py-3 md:px-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/20"
                    >
                        <Sparkles class="h-4 w-4 text-primary" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">AI Expense Logger</p>
                        <p class="text-xs text-muted-foreground">
                            Natural language expense tracking
                        </p>
                    </div>
                </div>

                <div v-if="wallets.length" class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">Wallet:</span>
                    <select
                        v-model="defaultWalletId"
                        class="h-8 rounded-lg border border-input bg-background px-2 text-xs outline-none focus:ring-1 focus:ring-ring"
                    >
                        <option v-for="w in wallets" :key="w.id" :value="w.id">
                            {{ w.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto px-4 py-4 md:px-6">
            <div class="mx-auto max-w-2xl space-y-4">
                <!-- Example pills (shown initially) -->
                <div
                    v-if="messages.length <= 1"
                    class="flex flex-wrap gap-2 pt-2"
                >
                    <p class="mb-1 w-full text-xs text-muted-foreground">
                        Try these examples:
                    </p>
                    <button
                        v-for="ex in examples"
                        :key="ex"
                        @click="input = ex"
                        class="rounded-full border border-sidebar-border/50 px-3 py-1.5 text-xs transition-colors hover:bg-accent"
                    >
                        {{ ex }}
                    </button>
                </div>

                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="flex gap-3"
                    :class="
                        msg.role === 'user' ? 'justify-end' : 'justify-start'
                    "
                >
                    <!-- Bot avatar -->
                    <div
                        v-if="msg.role === 'assistant'"
                        class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/20"
                    >
                        <Bot class="h-3.5 w-3.5 text-primary" />
                    </div>

                    <div class="max-w-sm space-y-2">
                        <!-- Message bubble -->
                        <div
                            class="rounded-2xl px-4 py-2.5 text-sm"
                            :class="
                                msg.role === 'user'
                                    ? 'rounded-br-sm bg-primary text-primary-foreground'
                                    : 'rounded-bl-sm border border-sidebar-border/50 bg-card'
                            "
                        >
                            {{ msg.text }}
                        </div>

                        <!-- Parsed Card -->
                        <div
                            v-if="msg.parsed && !msg.saved"
                            class="rounded-xl border border-sidebar-border bg-card p-4"
                        >
                            <div class="mb-3 flex items-center gap-2">
                                <component
                                    :is="
                                        msg.parsed.type === 'expense'
                                            ? ArrowDownRight
                                            : ArrowUpRight
                                    "
                                    class="h-4 w-4"
                                    :class="
                                        msg.parsed.type === 'expense'
                                            ? 'text-red-400'
                                            : 'text-emerald-400'
                                    "
                                />
                                <span
                                    class="text-xs text-muted-foreground capitalize"
                                    >{{ msg.parsed.type }}</span
                                >
                            </div>

                            <p
                                class="mb-1 text-2xl font-bold tabular-nums"
                                :class="
                                    msg.parsed.type === 'expense'
                                        ? 'text-red-400'
                                        : 'text-emerald-400'
                                "
                            >
                                {{ formatAmount(msg.parsed.amount) }}
                            </p>

                            <div class="mt-2 space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-xs text-muted-foreground"
                                        >Description</span
                                    >
                                    <span class="text-xs">{{
                                        msg.parsed.description || '—'
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-muted-foreground"
                                        >Category</span
                                    >
                                    <span class="text-xs">{{
                                        msg.parsed.category
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-muted-foreground"
                                        >Date</span
                                    >
                                    <span class="text-xs">{{
                                        msg.parsed.date
                                    }}</span>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-2">
                                <button
                                    @click="saveTransaction(msg)"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-primary py-2 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                                >
                                    <Check class="h-3.5 w-3.5" />
                                    Save
                                </button>
                                <button
                                    @click="discardParsed(msg)"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-input px-3 py-2 text-xs transition-colors hover:bg-accent"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="msg.saved"
                            class="flex items-center gap-1.5 text-xs text-emerald-400"
                        >
                            <Check class="h-3 w-3" />
                            Saved
                        </div>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div v-if="isLoading" class="flex justify-start gap-3">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/20"
                    >
                        <Bot class="h-3.5 w-3.5 text-primary" />
                    </div>
                    <div
                        class="rounded-2xl rounded-bl-sm border border-sidebar-border/50 bg-card px-4 py-2.5"
                    >
                        <Loader2
                            class="h-4 w-4 animate-spin text-muted-foreground"
                        />
                    </div>
                </div>

                <div ref="messagesEnd" />
            </div>
        </div>

        <!-- Input -->
        <div class="border-t border-sidebar-border/50 px-4 py-3 md:px-6">
            <div class="mx-auto max-w-2xl">
                <div class="flex items-end gap-2">
                    <div class="relative flex-1">
                        <textarea
                            v-model="input"
                            @keydown="handleKeydown"
                            placeholder="coffee 120, uber 450, salary 50000..."
                            rows="1"
                            class="w-full resize-none rounded-xl border border-input bg-background px-4 py-2.5 pr-12 text-sm outline-none focus:ring-1 focus:ring-ring"
                            style="min-height: 44px; max-height: 120px"
                            @input="
                                (
                                    $event.target as HTMLTextAreaElement
                                ).style.height = 'auto';
                                (
                                    $event.target as HTMLTextAreaElement
                                ).style.height =
                                    Math.min(
                                        ($event.target as HTMLTextAreaElement)
                                            .scrollHeight,
                                        120,
                                    ) + 'px';
                            "
                        />
                    </div>
                    <button
                        @click="sendMessage"
                        :disabled="isLoading || !input.trim()"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
                    >
                        <Send class="h-4 w-4" />
                    </button>
                </div>
                <p class="mt-2 text-center text-xs text-muted-foreground">
                    Press Enter to send · AI parses natural language
                </p>
            </div>
        </div>
    </div>
</template>
