<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { CalendarDays, MapPin, Plus, Wallet, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { dashboard } from '@/routes';
import { index as tripsIndex, show as tripShow, store as tripsStore } from '@/routes/trips';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Trips', href: tripsIndex() },
        ],
    },
});

interface Trip {
    id: number;
    name: string;
    destination: string | null;
    budget: string | null;
    currency: string;
    start_date: string | null;
    end_date: string | null;
    status: 'active' | 'completed' | 'archived';
    notes: string | null;
    transactions_count: number;
    total_spent: string | null;
}

defineProps<{ trips: Trip[] }>();

const page = usePage<{ userCurrency: string; currencies: Record<string, string> }>();
const currencies = page.props.currencies ?? {};

const showModal = ref(false);

const form = useForm({
    name: '',
    destination: '',
    budget: '',
    currency: page.props.userCurrency ?? 'PKR',
    start_date: '',
    end_date: '',
    notes: '',
});

function submit() {
    form.post(tripsStore().url, {
        onSuccess: () => {
            form.reset();
            showModal.value = false;
        },
    });
}

function fmt(n: number | string, currency = 'PKR') {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency, maximumFractionDigits: 0 }).format(Number(n));
}

function formatDate(d: string | null) {
    if (!d) { return null; }
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function budgetPercent(trip: Trip): number {
    if (!trip.budget || !trip.total_spent) { return 0; }
    return Math.min(100, Math.round((Number(trip.total_spent) / Number(trip.budget)) * 100));
}

const statusColors: Record<string, string> = {
    active: 'bg-emerald-400/15 text-emerald-400',
    completed: 'bg-primary/15 text-primary',
    archived: 'bg-muted text-muted-foreground',
};
</script>

<template>
    <Head title="Trips" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight">My Trips</h1>
                <p class="text-sm text-muted-foreground">Track spending across all your travels</p>
            </div>
            <button
                @click="showModal = true"
                class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/20 transition-colors hover:bg-primary/90"
            >
                <Plus class="h-4 w-4" />
                New Trip
            </button>
        </div>

        <!-- Empty state -->
        <div v-if="trips.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-sidebar-border/50 py-20 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10">
                <MapPin class="h-7 w-7 text-primary" />
            </div>
            <p class="text-base font-semibold">No trips yet</p>
            <p class="mt-1 text-sm text-muted-foreground">Create your first trip to start tracking travel expenses</p>
            <button
                @click="showModal = true"
                class="mt-5 flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
            >
                <Plus class="h-4 w-4" /> Create Trip
            </button>
        </div>

        <!-- Trips grid -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="trip in trips"
                :key="trip.id"
                @click="router.visit(tripShow(trip.id).url)"
                class="group flex cursor-pointer flex-col gap-4 rounded-2xl border border-sidebar-border/50 bg-card p-5 transition-all hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5"
            >
                <!-- Title row -->
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ trip.name }}</p>
                        <div v-if="trip.destination" class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                            <MapPin class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{ trip.destination }}</span>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColors[trip.status]">
                        {{ trip.status }}
                    </span>
                </div>

                <!-- Date range -->
                <div v-if="trip.start_date || trip.end_date" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                    <span>{{ formatDate(trip.start_date) ?? '?' }} — {{ formatDate(trip.end_date) ?? '?' }}</span>
                </div>

                <!-- Budget bar -->
                <div v-if="trip.budget">
                    <div class="mb-1.5 flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Spent</span>
                        <span class="font-medium">
                            {{ fmt(trip.total_spent ?? 0, trip.currency) }}
                            <span class="text-muted-foreground"> / {{ fmt(trip.budget, trip.currency) }}</span>
                        </span>
                    </div>
                    <div class="h-1.5 rounded-full bg-sidebar-border/40">
                        <div
                            class="h-1.5 rounded-full transition-all"
                            :class="budgetPercent(trip) >= 90 ? 'bg-red-400' : budgetPercent(trip) >= 70 ? 'bg-amber-400' : 'bg-primary'"
                            :style="{ width: `${budgetPercent(trip)}%` }"
                        />
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ budgetPercent(trip) }}% used</p>
                </div>

                <!-- Stats row -->
                <div class="flex items-center gap-4 text-xs text-muted-foreground">
                    <span class="flex items-center gap-1">
                        <Wallet class="h-3.5 w-3.5" />
                        {{ trip.transactions_count }} transaction{{ trip.transactions_count !== 1 ? 's' : '' }}
                    </span>
                    <span v-if="!trip.budget && trip.total_spent" class="font-medium text-foreground">
                        {{ fmt(trip.total_spent, trip.currency) }} spent
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Create Trip Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showModal = false">
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-sidebar-border bg-card shadow-2xl">
                <div class="flex items-center justify-between border-b border-sidebar-border/50 px-5 py-4">
                    <p class="font-semibold">New Trip</p>
                    <button @click="showModal = false" class="text-muted-foreground hover:text-foreground">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Trip Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Dubai 2026"
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Destination</label>
                        <input
                            v-model="form.destination"
                            type="text"
                            placeholder="e.g. Dubai, UAE"
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Budget</label>
                            <input
                                v-model="form.budget"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Currency</label>
                            <select
                                v-model="form.currency"
                                class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                            >
                                <option v-for="(name, code) in currencies" :key="code" :value="code">{{ code }} — {{ name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Start Date</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-muted-foreground">End Date</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Notes</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Any notes about this trip…"
                            class="w-full resize-none rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                        />
                    </div>

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
                            class="flex-1 rounded-xl bg-primary py-2.5 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/20 transition-colors hover:bg-primary/90 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Creating…' : 'Create Trip' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
