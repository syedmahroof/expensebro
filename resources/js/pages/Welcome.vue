<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BarChart3,
    Bot,
    CreditCard,
    MessageCircle,
    Mic,
    Receipt,
    ScanLine,
    Shield,
    Sparkles,
    TrendingDown,
    TrendingUp,
    Wallet,
    Zap,
} from 'lucide-vue-next';
import { dashboard, login, register } from '@/routes';
import { phone as whatsappLogin } from '@/routes/otp';

defineProps<{ canRegister: boolean }>();

const features = [
    {
        icon: Bot,
        title: 'AI Chat Logging',
        desc: 'Type "coffee 120" or "uber 450" — AI extracts amount, category, merchant automatically.',
        color: 'text-zinc-400',
        bg: 'bg-white/10',
    },
    {
        icon: MessageCircle,
        title: 'WhatsApp Sync',
        desc: 'Send expenses directly from WhatsApp. No app switching needed.',
        color: 'text-green-400',
        bg: 'bg-green-500/10',
    },
    {
        icon: Mic,
        title: 'Voice Input',
        desc: 'Whisper-powered voice logging. Speak your expense in any language.',
        color: 'text-blue-400',
        bg: 'bg-blue-500/10',
    },
    {
        icon: ScanLine,
        title: 'Receipt Scanner',
        desc: 'Snap a receipt. AI reads it and creates the transaction instantly.',
        color: 'text-amber-400',
        bg: 'bg-amber-500/10',
    },
    {
        icon: BarChart3,
        title: 'Smart Insights',
        desc: 'Spending trends, subscription detection, unusual alerts — all automated.',
        color: 'text-purple-400',
        bg: 'bg-purple-500/10',
    },
    {
        icon: Wallet,
        title: 'Multi-Wallet',
        desc: 'Cash, bank, card, crypto — track all accounts in one place.',
        color: 'text-cyan-400',
        bg: 'bg-cyan-500/10',
    },
];

const steps = [
    { n: '01', title: 'Add an expense naturally', body: 'Type, speak, or snap. "petrol 2000" is all you need to say.' },
    { n: '02', title: 'AI parses instantly', body: 'Amount, category, merchant, wallet — all extracted in under a second.' },
    { n: '03', title: 'Insights appear automatically', body: 'Your dashboard updates. Trends and alerts surface without any manual work.' },
];
</script>

<template>
    <Head title="ExpenseBro — AI-Native Expense Tracker" />

    <div class="min-h-screen bg-[#0a0a0a] text-white">

        <!-- Nav -->
        <header class="sticky top-0 z-50 border-b border-white/5 bg-[#0a0a0a]/80 backdrop-blur-xl">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 md:px-6">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-black">
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <span class="text-sm font-semibold tracking-tight">ExpenseBro</span>
                </div>

                <nav class="hidden items-center gap-6 md:flex">
                    <a href="#features" class="text-sm text-white/50 transition-colors hover:text-white">Features</a>
                    <a href="#how-it-works" class="text-sm text-white/50 transition-colors hover:text-white">How it works</a>
                </nav>

                <div class="flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard()"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-white px-4 text-sm font-medium text-black transition-colors hover:bg-zinc-200"
                        >
                            Dashboard <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="text-sm text-white/60 transition-colors hover:text-white">Log in</Link>
                        <Link
                            :href="register()"
                            class="inline-flex h-8 items-center rounded-lg bg-white px-4 text-sm font-medium text-black transition-colors hover:bg-zinc-200"
                        >
                            Get started
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden px-4 pb-24 pt-20 md:px-6 md:pb-32 md:pt-32">
            <!-- Glow -->
            <div class="pointer-events-none absolute inset-0 flex items-start justify-center pt-20">
                <div class="h-[400px] w-[700px] rounded-full bg-white/5 blur-[120px]" />
            </div>

            <div class="relative mx-auto max-w-3xl text-center">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs text-zinc-400">
                    <Zap class="h-3 w-3" />
                    AI-powered expense tracking
                </div>

                <h1 class="mb-5 text-4xl font-bold leading-tight tracking-tight md:text-6xl">
                    Track expenses the way
                    <span class="bg-gradient-to-r from-white to-zinc-500 bg-clip-text text-transparent">you actually talk</span>
                </h1>

                <p class="mx-auto mb-10 max-w-xl text-base text-white/50 md:text-lg">
                    Type "coffee 120" or "salary 50000". Our AI handles the rest — no forms, no categories, no friction.
                </p>

                <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        :href="register()"
                        class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-6 text-sm font-semibold text-black transition-all hover:bg-zinc-200 hover:shadow-lg hover:shadow-white/10"
                    >
                        Start for free <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        :href="whatsappLogin()"
                        class="inline-flex h-11 items-center gap-2 rounded-xl border border-green-500/30 bg-green-500/10 px-6 text-sm font-semibold text-green-400 transition-colors hover:bg-green-500/20"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Login with WhatsApp
                    </Link>
                </div>

                <p class="mt-4 text-xs text-white/25">No credit card required · Free to start</p>
            </div>

            <!-- Dashboard preview -->
            <div class="relative mx-auto mt-16 max-w-4xl">
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-[#111] shadow-2xl shadow-black/50">
                    <!-- Fake browser bar -->
                    <div class="flex items-center gap-2 border-b border-white/5 bg-[#0d0d0d] px-4 py-3">
                        <div class="flex gap-1.5">
                            <div class="h-2.5 w-2.5 rounded-full bg-white/10" />
                            <div class="h-2.5 w-2.5 rounded-full bg-white/10" />
                            <div class="h-2.5 w-2.5 rounded-full bg-white/10" />
                        </div>
                        <div class="mx-auto flex h-5 w-48 items-center justify-center rounded bg-white/5 text-xs text-white/20">
                            expensebro.app/dashboard
                        </div>
                    </div>

                    <!-- Fake UI -->
                    <div class="p-6">
                        <!-- Stats row -->
                        <div class="mb-6 grid grid-cols-3 gap-3">
                            <div class="rounded-xl border border-white/5 bg-white/3 p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs text-white/40">Expenses</span>
                                    <TrendingDown class="h-3.5 w-3.5 text-red-400" />
                                </div>
                                <p class="text-lg font-bold text-red-400">₨ 24,500</p>
                                <p class="mt-0.5 text-xs text-white/25">This month</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-white/3 p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs text-white/40">Income</span>
                                    <TrendingUp class="h-3.5 w-3.5 text-emerald-400" />
                                </div>
                                <p class="text-lg font-bold text-emerald-400">₨ 85,000</p>
                                <p class="mt-0.5 text-xs text-white/25">This month</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-white/3 p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs text-white/40">Balance</span>
                                    <Wallet class="h-3.5 w-3.5 text-zinc-400" />
                                </div>
                                <p class="text-lg font-bold text-white">₨ 60,500</p>
                                <p class="mt-0.5 text-xs text-white/25">This month</p>
                            </div>
                        </div>

                        <!-- Recent transactions -->
                        <div class="space-y-2">
                            <div
                                v-for="tx in [
                                    { icon: '☕', label: 'Starbucks Coffee', cat: 'Food', amount: '- ₨ 450', color: 'text-red-400', bg: 'bg-amber-500/15' },
                                    { icon: '🚗', label: 'Uber Ride', cat: 'Transport', amount: '- ₨ 380', color: 'text-red-400', bg: 'bg-blue-500/15' },
                                    { icon: '💼', label: 'Salary', cat: 'Income', amount: '+ ₨ 85,000', color: 'text-emerald-400', bg: 'bg-emerald-500/15' },
                                    { icon: '🛒', label: 'Grocery', cat: 'Shopping', amount: '- ₨ 2,100', color: 'text-red-400', bg: 'bg-pink-500/15' },
                                ]"
                                :key="tx.label"
                                class="flex items-center gap-3 rounded-lg p-2.5"
                            >
                                <div :class="['flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm', tx.bg]">{{ tx.icon }}</div>
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-sm font-medium text-white/80">{{ tx.label }}</p>
                                    <p class="text-xs text-white/30">{{ tx.cat }}</p>
                                </div>
                                <p :class="['text-sm font-semibold tabular-nums', tx.color]">{{ tx.amount }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Glow under card -->
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-[#0a0a0a]" />
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="border-t border-white/5 px-4 py-24 md:px-6">
            <div class="mx-auto max-w-5xl">
                <div class="mb-16 text-center">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">Features</p>
                    <h2 class="text-3xl font-bold tracking-tight md:text-4xl">Everything you need, nothing you don't</h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="f in features"
                        :key="f.title"
                        class="group rounded-2xl border border-white/5 bg-white/2 p-6 transition-colors hover:border-white/10 hover:bg-white/4"
                    >
                        <div :class="['mb-4 inline-flex h-10 w-10 items-center justify-center rounded-xl', f.bg]">
                            <component :is="f.icon" :class="['h-5 w-5', f.color]" />
                        </div>
                        <h3 class="mb-2 text-sm font-semibold text-white">{{ f.title }}</h3>
                        <p class="text-sm leading-relaxed text-white/40">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how-it-works" class="border-t border-white/5 px-4 py-24 md:px-6">
            <div class="mx-auto max-w-4xl">
                <div class="mb-16 text-center">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">How it works</p>
                    <h2 class="text-3xl font-bold tracking-tight md:text-4xl">Three steps, then you're done</h2>
                </div>

                <div class="relative">
                    <!-- Connector line -->
                    <div class="absolute left-[22px] top-10 hidden h-[calc(100%-80px)] w-px bg-gradient-to-b from-white/20 via-white/5 to-transparent md:block" />

                    <div class="space-y-10">
                        <div v-for="s in steps" :key="s.n" class="relative flex gap-6">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-sm font-bold text-white">
                                {{ s.n }}
                            </div>
                            <div class="pt-1.5">
                                <h3 class="mb-2 text-base font-semibold">{{ s.title }}</h3>
                                <p class="text-sm leading-relaxed text-white/40">{{ s.body }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Social proof strip -->
        <section class="border-t border-white/5 px-4 py-12 md:px-6">
            <div class="mx-auto max-w-5xl">
                <div class="grid grid-cols-2 gap-6 text-center md:grid-cols-4">
                    <div v-for="s in [
                        { val: '< 2s', label: 'AI parse time' },
                        { val: '6+', label: 'Input methods' },
                        { val: '12', label: 'Default categories' },
                        { val: '100%', label: 'Private & secure' },
                    ]" :key="s.label">
                        <p class="text-3xl font-bold text-white">{{ s.val }}</p>
                        <p class="mt-1 text-sm text-white/40">{{ s.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="border-t border-white/5 px-4 py-24 text-center md:px-6">
            <div class="relative mx-auto max-w-2xl">
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="h-64 w-96 rounded-full bg-white/5 blur-[80px]" />
                </div>
                <div class="relative">
                    <h2 class="mb-4 text-3xl font-bold tracking-tight md:text-5xl">
                        Start tracking smarter
                    </h2>
                    <p class="mb-8 text-white/40">Free to start. No credit card. No complicated setup.</p>

                    <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Link
                            :href="register()"
                            class="inline-flex h-12 items-center gap-2 rounded-xl bg-white px-8 text-sm font-semibold text-black transition-all hover:bg-zinc-200 hover:shadow-xl hover:shadow-white/10"
                        >
                            Create free account <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="whatsappLogin()"
                            class="inline-flex h-12 items-center gap-2 rounded-xl border border-white/10 px-8 text-sm font-semibold text-white/70 transition-colors hover:border-white/20 hover:text-white"
                        >
                            <MessageCircle class="h-4 w-4 text-green-400" />
                            Use WhatsApp
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-white/5 px-4 py-8 md:px-6">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded-md bg-white text-black">
                        <Sparkles class="h-3.5 w-3.5" />
                    </div>
                    <span class="text-sm font-semibold text-white/60">ExpenseBro</span>
                </div>
                <p class="text-xs text-white/25">AI-native expense tracking</p>
                <div class="flex items-center gap-4">
                    <Link :href="login()" class="text-xs text-white/30 transition-colors hover:text-white/60">Login</Link>
                    <Link v-if="canRegister" :href="register()" class="text-xs text-white/30 transition-colors hover:text-white/60">Register</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
