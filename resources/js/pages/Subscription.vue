<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Check, Lock, Sparkles, Zap } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { subscription } from '@/routes';
import {
    checkout as checkoutRoute,
    portal as portalRoute,
} from '@/routes/subscription';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Subscription', href: subscription() },
        ],
    },
});

interface Plan {
    id: string;
    name: string;
    price: number;
    period: string;
    tagline: string;
    features: string[];
    locked: string[];
    cta: string;
    highlighted: boolean;
}

interface PaymentLog {
    id: number;
    plan: string;
    amount: number;
    currency: string;
    status: 'paid' | 'failed' | 'refunded';
    invoice_id: string | null;
    paid_at: string;
}

const props = defineProps<{
    currentPlan: 'free' | 'now' | 'family';
    planExpiresAt: string | null;
    paymentHistory: PaymentLog[];
    plans: Plan[];
}>();

const currentTab = ref('subscription');

function formatDate(d: string) {
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

const statusClass = (s: string) =>
    ({
        paid: 'bg-emerald-400/15 text-emerald-400',
        failed: 'bg-red-400/15 text-red-400',
        refunded: 'bg-amber-400/15 text-amber-400',
    })[s] ?? 'bg-muted text-muted-foreground';

function handleUpgrade(planId: string) {
    if (planId === props.currentPlan) {
        if (planId !== 'free') {
            window.location.href = portalRoute().url;
        }
        return;
    }

    if (planId === 'free') return;

    router.post(checkoutRoute().url, {
        plan: planId,
    });
}
</script>

<template>
    <Head title="Subscription" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4 md:p-8">
        <!-- Header -->
        <div class="text-center">
            <div
                class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-1.5 text-sm font-medium text-primary"
            >
                <Sparkles class="h-3.5 w-3.5" />
                Simple, honest pricing
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight">
                Choose your plan
            </h1>
            <p class="mt-2 text-muted-foreground">
                Start free. Upgrade for more power when you're ready.
            </p>
        </div>

        <!-- Tabs -->
        <div class="flex items-center justify-center">
            <div
                class="flex w-fit items-center rounded-lg border border-sidebar-border/50 bg-sidebar-accent/50 p-1"
            >
                <button
                    @click="currentTab = 'subscription'"
                    class="rounded-md px-6 py-1.5 text-sm font-medium transition-all"
                    :class="
                        currentTab === 'subscription'
                            ? 'bg-card text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    Subscription
                </button>
                <button
                    @click="currentTab = 'history'"
                    class="rounded-md px-6 py-1.5 text-sm font-medium transition-all"
                    :class="
                        currentTab === 'history'
                            ? 'bg-card text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    Payment History
                </button>
            </div>
        </div>

        <!-- Subscription Tab -->
        <div
            v-if="currentTab === 'subscription'"
            class="flex animate-in flex-col gap-8 duration-500 fade-in slide-in-from-bottom-2"
        >
            <!-- Plans grid -->
            <div class="mx-auto grid w-full max-w-4xl gap-4 sm:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative flex flex-col rounded-2xl border p-6 transition-all"
                    :class="
                        plan.highlighted
                            ? 'border-primary/60 bg-primary/5 shadow-lg shadow-primary/10'
                            : 'border-sidebar-border/50 bg-card'
                    "
                >
                    <!-- Popular badge -->
                    <div
                        v-if="plan.highlighted"
                        class="absolute -top-3 left-1/2 -translate-x-1/2"
                    >
                        <span
                            class="flex items-center gap-1 rounded-full bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground"
                        >
                            <Zap class="h-3 w-3" /> Most popular
                        </span>
                    </div>

                    <!-- Current plan badge -->
                    <div
                        v-if="plan.id === currentPlan"
                        class="absolute top-4 right-4"
                    >
                        <span
                            class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-400"
                            >Current</span
                        >
                    </div>

                    <!-- Plan header -->
                    <div class="mb-5">
                        <p class="text-base font-bold">{{ plan.name }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ plan.tagline }}
                        </p>
                        <div class="mt-4 flex items-end gap-1">
                            <span class="text-4xl font-bold tracking-tight">
                                {{
                                    plan.price === 0 ? 'Free' : `$${plan.price}`
                                }}
                            </span>
                            <span
                                v-if="plan.price > 0"
                                class="mb-1 text-sm text-muted-foreground"
                                >/{{ plan.period }}</span
                            >
                        </div>
                    </div>

                    <!-- Features -->
                    <ul class="mb-6 flex-1 space-y-2.5">
                        <li
                            v-for="feat in plan.features"
                            :key="feat"
                            class="flex items-start gap-2.5 text-sm"
                        >
                            <Check
                                class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400"
                            />
                            <span>{{ feat }}</span>
                        </li>
                        <li
                            v-for="feat in plan.locked"
                            :key="feat"
                            class="flex items-start gap-2.5 text-sm text-muted-foreground/60"
                        >
                            <Lock class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ feat }}</span>
                        </li>
                    </ul>

                    <!-- CTA button -->
                    <button
                        @click="handleUpgrade(plan.id)"
                        class="w-full rounded-xl py-2.5 text-sm font-semibold transition-all"
                        :class="
                            plan.id === currentPlan
                                ? plan.id === 'free'
                                    ? 'cursor-default border border-sidebar-border/50 text-muted-foreground'
                                    : 'bg-accent text-accent-foreground hover:bg-accent/80'
                                : plan.highlighted
                                  ? 'bg-primary text-primary-foreground shadow-md shadow-primary/20 hover:bg-primary/90'
                                  : 'border border-sidebar-border/50 hover:bg-accent'
                        "
                        :disabled="
                            plan.id === currentPlan && plan.id === 'free'
                        "
                    >
                        {{
                            plan.id === currentPlan && plan.id !== 'free'
                                ? 'Manage Subscription'
                                : plan.cta
                        }}
                    </button>
                </div>
            </div>

            <!-- What's locked on Free -->
            <div
                v-if="currentPlan === 'free'"
                class="mx-auto w-full max-w-2xl rounded-2xl border border-amber-500/20 bg-amber-500/5 p-5"
            >
                <div class="flex items-start gap-3">
                    <Lock class="mt-0.5 h-5 w-5 shrink-0 text-amber-400" />
                    <div>
                        <p class="text-sm font-semibold text-amber-400">
                            You're on the Free plan
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            You can add transactions manually. Upgrade to
                            <strong class="text-foreground">Now ($1/mo)</strong>
                            to unlock AI chat logging, unlimited wallets,
                            receipt scanning, and more.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History Tab -->
        <div
            v-if="currentTab === 'history'"
            class="mx-auto w-full max-w-4xl animate-in duration-500 fade-in slide-in-from-bottom-2"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">
                        Payment History
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        View and download your previous payment invoices.
                    </p>
                </div>

                <div
                    v-if="paymentHistory.length === 0"
                    class="rounded-xl border border-dashed border-sidebar-border/50 bg-card/50 p-12 text-center text-sm text-muted-foreground"
                >
                    <div
                        class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted/50"
                    >
                        <Sparkles class="h-6 w-6 text-muted-foreground/50" />
                    </div>
                    No payments found.
                </div>

                <div
                    v-else
                    class="overflow-hidden rounded-xl border border-sidebar-border/50 bg-card shadow-sm"
                >
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-sidebar-border/50 bg-sidebar-accent/30"
                            >
                                <th
                                    class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Plan
                                </th>
                                <th
                                    class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Amount
                                </th>
                                <th
                                    class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Invoice
                                </th>
                                <th
                                    class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/30">
                            <tr
                                v-for="log in paymentHistory"
                                :key="log.id"
                                class="transition-colors hover:bg-accent/30"
                            >
                                <td class="px-4 py-4 text-muted-foreground">
                                    {{ formatDate(log.paid_at) }}
                                </td>
                                <td class="px-4 py-4 font-medium capitalize">
                                    {{ log.plan }}
                                </td>
                                <td class="px-4 py-4 tabular-nums">
                                    ${{ Number(log.amount).toFixed(2) }}
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-[11px] text-muted-foreground"
                                >
                                    {{ log.invoice_id ?? '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium capitalize"
                                        :class="statusClass(log.status)"
                                    >
                                        {{ log.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
