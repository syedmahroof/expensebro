<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Banknote,
    BarChart3,
    BellRing,
    Bot,
    CheckCircle2,
    ChevronRight,
    Coins,
    CreditCard,
    Globe,
    Layers,
    Lock,
    MessageCircle,
    Mic,
    PieChart,
    PiggyBank,
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
        desc: 'Type "coffee 120" or "uber 450" — AI extracts amount, category, and merchant automatically.',
        color: 'text-blue-600',
        bg: 'bg-blue-50',
    },
    {
        icon: MessageCircle,
        title: 'WhatsApp Sync',
        desc: 'Send expenses directly from WhatsApp. No app switching needed.',
        color: 'text-emerald-600',
        bg: 'bg-emerald-50',
    },
    {
        icon: Mic,
        title: 'Voice Input',
        desc: 'Whisper-powered voice logging. Speak your expense in any language.',
        color: 'text-violet-600',
        bg: 'bg-violet-50',
    },
    {
        icon: ScanLine,
        title: 'Receipt Scanner',
        desc: 'Snap a receipt. AI reads it and creates the transaction instantly.',
        color: 'text-amber-600',
        bg: 'bg-amber-50',
    },
    {
        icon: PieChart,
        title: 'Smart Insights',
        desc: 'Spending trends, subscription detection, unusual alerts — all automated.',
        color: 'text-blue-600',
        bg: 'bg-blue-50',
    },
    {
        icon: Wallet,
        title: 'Multi-Wallet',
        desc: 'Cash, bank, card, crypto — track all accounts in one place.',
        color: 'text-cyan-600',
        bg: 'bg-cyan-50',
    },
    {
        icon: Globe,
        title: 'Multi-Currency',
        desc: 'Track expenses across currencies with real-time conversion rates.',
        color: 'text-indigo-600',
        bg: 'bg-indigo-50',
    },
    {
        icon: PiggyBank,
        title: 'Budget Goals',
        desc: 'Set budgets per category and get alerts before you overspend.',
        color: 'text-rose-600',
        bg: 'bg-rose-50',
    },
    {
        icon: BellRing,
        title: 'Smart Alerts',
        desc: 'Get notified about unusual spending, recurring charges, and bill due dates.',
        color: 'text-orange-600',
        bg: 'bg-orange-50',
    },
];

const steps = [
    {
        icon: MessageCircle,
        title: 'Add an expense naturally',
        body: 'Type, speak, or snap a receipt. "petrol 2000" is all you need to say.',
    },
    {
        icon: Zap,
        title: 'AI parses instantly',
        body: 'Amount, category, merchant, wallet — all extracted in under a second.',
    },
    {
        icon: BarChart3,
        title: 'Insights appear automatically',
        body: 'Your dashboard updates. Trends and alerts surface without any manual work.',
    },
];

const socialProof = [
    { val: '< 2s', label: 'AI parse time', icon: Zap },
    { val: '6+', label: 'Input methods', icon: Layers },
    { val: '12', label: 'Default categories', icon: PieChart },
    { val: '100%', label: 'Private & secure', icon: Shield },
];
</script>

<template>
    <Head title="ExpenseBro — AI-Native Expense Tracker" />

    <div class="min-h-screen bg-white text-gray-900">
        <!-- Nav -->
        <header
            class="sticky top-0 z-50 border-b border-blue-100/60 bg-white/80 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 md:px-6"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-blue-500 text-white shadow-sm shadow-blue-500/20"
                    >
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <span
                        class="text-sm font-semibold tracking-tight text-gray-900"
                        >ExpenseBro</span
                    >
                </div>

                <nav class="hidden items-center gap-6 md:flex">
                    <a
                        href="#features"
                        class="text-sm text-gray-500 transition-colors hover:text-blue-600"
                        >Features</a
                    >
                    <a
                        href="#how-it-works"
                        class="text-sm text-gray-500 transition-colors hover:text-blue-600"
                        >How it works</a
                    >
                </nav>

                <div class="flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard()"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm shadow-blue-600/20 transition-all hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/25"
                        >
                            Dashboard <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm text-gray-500 transition-colors hover:text-blue-600"
                            >Log in</Link
                        >
                        <Link
                            :href="register()"
                            class="inline-flex h-8 items-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm shadow-blue-600/20 transition-all hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/25"
                        >
                            Get started
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section
            class="relative overflow-hidden px-4 pt-20 pb-24 md:px-6 md:pt-32 md:pb-32"
        >
            <!-- Glow -->
            <div
                class="pointer-events-none absolute inset-0 flex items-start justify-center pt-10"
            >
                <div
                    class="h-[500px] w-[800px] rounded-full bg-blue-100/60 blur-[130px]"
                />
            </div>

            <!-- Decorative dots -->
            <div
                class="pointer-events-none absolute inset-0 overflow-hidden opacity-[0.035]"
            >
                <div
                    class="absolute -top-10 -left-10 h-80 w-80"
                    style="background-image: radial-gradient(circle, #1d4ed8 1px, transparent 1px); background-size: 24px 24px"
                />
                <div
                    class="absolute -right-10 top-40 h-80 w-80"
                    style="background-image: radial-gradient(circle, #1d4ed8 1px, transparent 1px); background-size: 24px 24px"
                />
            </div>

            <div class="relative mx-auto max-w-3xl text-center">
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-medium text-blue-700"
                >
                    <Zap class="h-3 w-3 text-blue-500" />
                    AI-powered expense tracking
                </div>

                <h1
                    class="mb-5 text-4xl leading-tight font-bold tracking-tight md:text-6xl"
                >
                    Track expenses the way
                    <span
                        class="bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent"
                        >you actually talk</span
                    >
                </h1>

                <p
                    class="mx-auto mb-10 max-w-xl text-base text-gray-500 md:text-lg"
                >
                    Type "coffee 120" or "salary 50000". Our AI handles the rest
                    — no forms, no categories, no friction.
                </p>

                <div
                    class="flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <Link
                        :href="register()"
                        class="inline-flex h-11 items-center gap-2 rounded-xl bg-blue-600 px-6 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/30"
                    >
                        Start for free
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        :href="whatsappLogin()"
                        class="inline-flex h-11 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-6 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-100"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Login with WhatsApp
                    </Link>
                </div>

                <p class="mt-4 text-xs text-gray-400">
                    No credit card required · Free to start
                </p>
            </div>

            <!-- Dashboard preview -->
            <div class="relative mx-auto mt-16 max-w-4xl">
                <!-- Floating badge - left -->
                <div
                    class="pointer-events-none absolute -left-4 top-10 z-10 hidden items-center gap-2 rounded-xl border border-blue-100 bg-white px-3 py-2 shadow-lg shadow-blue-100/40 md:flex"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50"
                    >
                        <CheckCircle2
                            class="h-4 w-4 text-blue-600"
                        />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-900">
                            AI Parsed
                        </p>
                        <p class="text-[10px] text-gray-400">
                            "coffee 120" → Food
                        </p>
                    </div>
                </div>

                <!-- Floating badge - right -->
                <div
                    class="pointer-events-none absolute -right-4 top-28 z-10 hidden items-center gap-2 rounded-xl border border-emerald-100 bg-white px-3 py-2 shadow-lg shadow-emerald-100/40 md:flex"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50"
                    >
                        <TrendingUp
                            class="h-4 w-4 text-emerald-600"
                        />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-900">
                            -12% this month
                        </p>
                        <p class="text-[10px] text-gray-400">
                            Spending trend
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-blue-100/60 bg-white shadow-2xl shadow-blue-900/5"
                >
                    <!-- Fake browser bar -->
                    <div
                        class="flex items-center gap-2 border-b border-blue-50 bg-gradient-to-r from-blue-50/80 to-white px-4 py-3"
                    >
                        <div class="flex gap-1.5">
                            <div
                                class="h-2.5 w-2.5 rounded-full bg-red-300"
                            />
                            <div
                                class="h-2.5 w-2.5 rounded-full bg-amber-300"
                            />
                            <div
                                class="h-2.5 w-2.5 rounded-full bg-green-300"
                            />
                        </div>
                        <div
                            class="mx-auto flex h-5 w-48 items-center justify-center rounded-md bg-blue-50 text-xs text-blue-400"
                        >
                            expensebro.app/dashboard
                        </div>
                    </div>

                    <!-- Fake UI -->
                    <div class="p-6">
                        <!-- Stats row -->
                        <div class="mb-6 grid grid-cols-3 gap-3">
                            <div
                                class="rounded-xl border border-blue-50 bg-gradient-to-br from-red-50/80 to-white p-4"
                            >
                                <div
                                    class="mb-2 flex items-center justify-between"
                                >
                                    <span class="text-xs text-gray-400"
                                        >Expenses</span
                                    >
                                    <TrendingDown
                                        class="h-3.5 w-3.5 text-red-500"
                                    />
                                </div>
                                <p class="text-lg font-bold text-red-500">
                                    ₨ 24,500
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    This month
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-blue-50 bg-gradient-to-br from-emerald-50/80 to-white p-4"
                            >
                                <div
                                    class="mb-2 flex items-center justify-between"
                                >
                                    <span class="text-xs text-gray-400"
                                        >Income</span
                                    >
                                    <TrendingUp
                                        class="h-3.5 w-3.5 text-emerald-500"
                                    />
                                </div>
                                <p class="text-lg font-bold text-emerald-500">
                                    ₨ 85,000
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    This month
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-blue-50 bg-gradient-to-br from-blue-50/80 to-white p-4"
                            >
                                <div
                                    class="mb-2 flex items-center justify-between"
                                >
                                    <span class="text-xs text-gray-400"
                                        >Balance</span
                                    >
                                    <Wallet
                                        class="h-3.5 w-3.5 text-blue-500"
                                    />
                                </div>
                                <p class="text-lg font-bold text-blue-600">
                                    ₨ 60,500
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    This month
                                </p>
                            </div>
                        </div>

                        <!-- Recent transactions -->
                        <div class="space-y-2">
                            <div
                                v-for="tx in [
                                    {
                                        icon: '☕',
                                        label: 'Starbucks Coffee',
                                        cat: 'Food',
                                        amount: '- ₨ 450',
                                        color: 'text-red-500',
                                        bg: 'bg-amber-50',
                                    },
                                    {
                                        icon: '🚗',
                                        label: 'Uber Ride',
                                        cat: 'Transport',
                                        amount: '- ₨ 380',
                                        color: 'text-red-500',
                                        bg: 'bg-blue-50',
                                    },
                                    {
                                        icon: '💼',
                                        label: 'Salary',
                                        cat: 'Income',
                                        amount: '+ ₨ 85,000',
                                        color: 'text-emerald-500',
                                        bg: 'bg-emerald-50',
                                    },
                                    {
                                        icon: '🛒',
                                        label: 'Grocery',
                                        cat: 'Shopping',
                                        amount: '- ₨ 2,100',
                                        color: 'text-red-500',
                                        bg: 'bg-pink-50',
                                    },
                                ]"
                                :key="tx.label"
                                class="flex items-center gap-3 rounded-lg p-2.5 transition-colors hover:bg-blue-50/40"
                            >
                                <div
                                    :class="[
                                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm',
                                        tx.bg,
                                    ]"
                                >
                                    {{ tx.icon }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-medium text-gray-700"
                                    >
                                        {{ tx.label }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ tx.cat }}
                                    </p>
                                </div>
                                <p
                                    :class="[
                                        'text-sm font-semibold tabular-nums',
                                        tx.color,
                                    ]"
                                >
                                    {{ tx.amount }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Glow under card -->
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-blue-50/60 to-transparent"
                />
            </div>
        </section>

        <!-- Trust bar -->
        <section class="px-4 py-6 md:px-6">
            <div class="mx-auto max-w-3xl">
                <p
                    class="mb-4 text-center text-xs font-medium tracking-wider text-gray-400 uppercase"
                >
                    Built with modern tech stack
                </p>
                <div
                    class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-gray-300"
                >
                    <div class="flex items-center gap-1.5">
                        <Lock class="h-3.5 w-3.5" />
                        <span class="text-xs font-medium">Bank-grade encryption</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Shield class="h-3.5 w-3.5" />
                        <span class="text-xs font-medium">GDPR compliant</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Globe class="h-3.5 w-3.5" />
                        <span class="text-xs font-medium">Multi-language</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <CreditCard class="h-3.5 w-3.5" />
                        <span class="text-xs font-medium">100% free tier</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section
            id="features"
            class="px-4 py-24 md:px-6"
        >
            <div class="mx-auto max-w-6xl">
                <div class="mb-16 text-center">
                    <div
                        class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600"
                    >
                        <Sparkles class="h-3 w-3" />
                        Features
                    </div>
                    <h2
                        class="text-3xl font-bold tracking-tight md:text-4xl"
                    >
                        Everything you need,
                        <span
                            class="bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent"
                            >nothing you don't</span
                        >
                    </h2>
                    <p class="mx-auto mt-4 max-w-lg text-gray-500">
                        One app to replace your spreadsheets, note apps, and
                        bank statement PDFs.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="f in features"
                        :key="f.title"
                        class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 transition-all hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50"
                    >
                        <div
                            class="absolute -top-12 -right-12 h-24 w-24 rounded-full bg-blue-50 opacity-0 transition-opacity group-hover:opacity-100"
                        />
                        <div
                            :class="[
                                'relative mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl transition-transform group-hover:scale-110',
                                f.bg,
                            ]"
                        >
                            <component
                                :is="f.icon"
                                :class="['h-5 w-5', f.color]"
                            />
                        </div>
                        <h3
                            class="relative mb-2 text-sm font-semibold text-gray-900"
                        >
                            {{ f.title }}
                        </h3>
                        <p
                            class="relative text-sm leading-relaxed text-gray-500"
                        >
                            {{ f.desc }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section
            id="how-it-works"
            class="border-t border-blue-50 bg-gradient-to-b from-blue-50/30 to-white px-4 py-24 md:px-6"
        >
            <div class="mx-auto max-w-4xl">
                <div class="mb-16 text-center">
                    <div
                        class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600"
                    >
                        <Zap class="h-3 w-3" />
                        How it works
                    </div>
                    <h2
                        class="text-3xl font-bold tracking-tight md:text-4xl"
                    >
                        Three steps,
                        <span
                            class="bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent"
                            >then you're done</span
                        >
                    </h2>
                </div>

                <div class="relative">
                    <!-- Connector line -->
                    <div
                        class="absolute top-12 left-[27px] hidden h-[calc(100%-96px)] w-px bg-gradient-to-b from-blue-200 via-blue-100 to-transparent md:block"
                    />

                    <div class="space-y-12">
                        <div
                            v-for="s in steps"
                            :key="s.title"
                            class="relative flex gap-6"
                        >
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-blue-100 bg-white shadow-md shadow-blue-100/30"
                            >
                                <component
                                    :is="s.icon"
                                    class="h-6 w-6 text-blue-600"
                                />
                            </div>
                            <div class="pt-1">
                                <h3 class="mb-2 text-lg font-semibold">
                                    {{ s.title }}
                                </h3>
                                <p
                                    class="text-sm leading-relaxed text-gray-500"
                                >
                                    {{ s.body }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Social proof -->
        <section class="px-4 py-16 md:px-6">
            <div class="mx-auto max-w-5xl">
                <div
                    class="grid grid-cols-2 gap-6 text-center md:grid-cols-4"
                >
                    <div
                        v-for="s in socialProof"
                        :key="s.label"
                        class="group rounded-2xl border border-gray-100 bg-white p-6 transition-all hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50"
                    >
                        <div
                            class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 transition-transform group-hover:scale-110"
                        >
                            <component
                                :is="s.icon"
                                class="h-5 w-5 text-blue-600"
                            />
                        </div>
                        <p
                            class="text-3xl font-bold text-gray-900"
                        >
                            {{ s.val }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ s.label }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="px-4 py-24 md:px-6">
            <div class="relative mx-auto max-w-3xl">
                <div
                    class="pointer-events-none absolute inset-0 flex items-center justify-center"
                >
                    <div
                        class="h-72 w-[500px] rounded-full bg-blue-100/50 blur-[100px]"
                    />
                </div>
                <div
                    class="relative overflow-hidden rounded-3xl border border-blue-100/60 bg-white p-12 text-center shadow-2xl shadow-blue-900/5"
                >
                    <!-- Decorative corner accents -->
                    <div
                        class="absolute -top-16 -right-16 h-32 w-32 rounded-full bg-blue-50"
                    />
                    <div
                        class="absolute -bottom-16 -left-16 h-32 w-32 rounded-full bg-blue-50"
                    />

                    <div
                        class="relative mb-6 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-500/30"
                    >
                        <Sparkles class="h-7 w-7 text-white" />
                    </div>

                    <h2
                        class="relative mb-4 text-3xl font-bold tracking-tight md:text-5xl"
                    >
                        Start
                        <span
                            class="bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent"
                            >tracking smarter</span
                        >
                    </h2>
                    <p class="relative mb-8 text-gray-500">
                        Free to start. No credit card. No complicated setup.
                    </p>

                    <div
                        class="relative flex flex-col items-center justify-center gap-3 sm:flex-row"
                    >
                        <Link
                            :href="register()"
                            class="inline-flex h-12 items-center gap-2 rounded-xl bg-blue-600 px-8 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/30"
                        >
                            Create free account
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="whatsappLogin()"
                            class="inline-flex h-12 items-center gap-2 rounded-xl border border-gray-200 px-8 text-sm font-semibold text-gray-600 transition-colors hover:border-blue-200 hover:text-blue-600"
                        >
                            <MessageCircle class="h-4 w-4 text-emerald-500" />
                            Use WhatsApp
                        </Link>
                    </div>

                    <p
                        class="relative mt-6 flex items-center justify-center gap-4 text-xs text-gray-400"
                    >
                        <span class="flex items-center gap-1">
                            <CheckCircle2 class="h-3 w-3 text-blue-400" />
                            Free forever
                        </span>
                        <span class="flex items-center gap-1">
                            <CheckCircle2 class="h-3 w-3 text-blue-400" />
                            No card needed
                        </span>
                        <span class="flex items-center gap-1">
                            <CheckCircle2 class="h-3 w-3 text-blue-400" />
                            Cancel anytime
                        </span>
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-100 px-4 py-8 md:px-6">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-md bg-gradient-to-br from-blue-600 to-blue-500 text-white"
                    >
                        <Sparkles class="h-3.5 w-3.5" />
                    </div>
                    <span class="text-sm font-semibold text-gray-500"
                        >ExpenseBro</span
                    >
                </div>
                <p class="text-xs text-gray-400">
                    AI-native expense tracking
                </p>
                <div class="flex items-center gap-4">
                    <Link
                        :href="login()"
                        class="text-xs text-gray-400 transition-colors hover:text-blue-600"
                        >Login</Link
                    >
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="text-xs text-gray-400 transition-colors hover:text-blue-600"
                        >Register</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
