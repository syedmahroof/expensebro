<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Plus, Trash2, Users, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { dashboard } from '@/routes';

const { confirm } = useConfirm();
import { destroy as groupDestroy, index as groupsIndex, store as groupsStore } from '@/routes/groups';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Groups', href: groupsIndex() },
        ],
    },
});

interface GroupSummary {
    id: number;
    name: string;
    description: string | null;
    currency: string;
    members_count: number;
    total_expenses: number;
    created_at: string;
}

const props = defineProps<{
    groups: GroupSummary[];
    currencies: Record<string, string>;
}>();

const showModal = ref(false);

const form = useForm({
    name: '',
    description: '',
    currency: 'PKR',
    members: [{ name: '', phone: '' }] as { name: string; phone: string }[],
});

const CURRENCIES = computed(() => Object.keys(props.currencies));

function addMember() {
    form.members.push({ name: '', phone: '' });
}

function removeMember(index: number) {
    if (form.members.length > 1) {
        form.members.splice(index, 1);
    }
}

function openModal() {
    form.reset();
    form.members = [{ name: '', phone: '' }];
    showModal.value = true;
}

function submit() {
    form.post(groupsStore().url, {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

async function deleteGroup(id: number) {
    const ok = await confirm('This group and all its expenses will be permanently deleted.', {
        title: 'Delete group?',
        confirmText: 'Delete',
    });
    if (!ok) { return; }
    router.delete(groupDestroy(id).url);
}

function fmt(amount: number, currency: string) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
}

function formatDate(d: string) {
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Groups" />

    <div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">

        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight">Group Expenses</h1>
                <p class="text-sm text-muted-foreground">Split expenses with friends and track who owes what</p>
            </div>
            <button
                @click="openModal"
                class="flex items-center gap-1.5 rounded-xl bg-orange-600 px-3 py-2 text-xs font-semibold text-white shadow-md shadow-orange-500/20 transition-colors hover:bg-orange-500"
            >
                <Plus class="h-3.5 w-3.5" /> New Group
            </button>
        </div>

        <!-- Summary -->
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-orange-500/20 bg-orange-500/5 p-4">
                <p class="text-xs text-muted-foreground">Total Groups</p>
                <p class="mt-1.5 text-xl font-bold text-orange-400">{{ groups.length }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">active groups</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/50 bg-card p-4">
                <p class="text-xs text-muted-foreground">Total Members</p>
                <p class="mt-1.5 text-xl font-bold">{{ groups.reduce((s, g) => s + g.members_count, 0) }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">across all groups</p>
            </div>
        </div>

        <!-- Groups list -->
        <div v-if="groups.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 py-16 text-center">
            <Users class="mb-3 h-10 w-10 text-muted-foreground/30" />
            <p class="font-medium text-muted-foreground">No groups yet</p>
            <p class="mt-1 text-sm text-muted-foreground/60">Create a group to start splitting expenses</p>
            <button @click="openModal" class="mt-3 text-sm text-orange-400 hover:text-orange-300">
                + Create your first group
            </button>
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="group in groups"
                :key="group.id"
                class="group relative rounded-xl border border-sidebar-border/50 bg-card p-4 transition-all hover:border-orange-500/30"
            >
                <Link :href="{ url: `/groups/${group.id}`, method: 'get' }" class="absolute inset-0 rounded-xl" />

                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-500/15">
                            <Users class="h-4.5 w-4.5 text-orange-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ group.name }}</p>
                            <p v-if="group.description" class="truncate text-xs text-muted-foreground">{{ group.description }}</p>
                        </div>
                    </div>
                    <button
                        @click.prevent="deleteGroup(group.id)"
                        class="relative z-10 shrink-0 text-muted-foreground opacity-0 transition-opacity hover:text-red-400 group-hover:opacity-100"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div class="mt-3 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3 text-xs text-muted-foreground">
                        <span class="flex items-center gap-1">
                            <Users class="h-3 w-3" /> {{ group.members_count }} members
                        </span>
                        <span class="flex items-center gap-1">
                            <CalendarDays class="h-3 w-3" /> {{ formatDate(group.created_at) }}
                        </span>
                    </div>
                    <p class="text-sm font-bold tabular-nums text-orange-400">{{ fmt(group.total_expenses, group.currency) }}</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Create Group Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm" @click.self="showModal = false">
            <div class="my-8 w-full max-w-md overflow-hidden rounded-2xl border border-sidebar-border bg-card shadow-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-sidebar-border/50 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-500/15">
                            <Users class="h-4 w-4 text-orange-400" />
                        </div>
                        <p class="font-semibold">New Group</p>
                    </div>
                    <button @click="showModal = false" class="text-muted-foreground hover:text-foreground">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 p-5">
                    <!-- Name -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Group Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Weekend Trip, Roommates..."
                            required
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Description (optional)</label>
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="What's this group for?"
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                        />
                    </div>

                    <!-- Currency -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Default Currency</label>
                        <select
                            v-model="form.currency"
                            class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                        >
                            <option v-for="c in CURRENCIES" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </div>

                    <!-- Members -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-xs font-medium text-muted-foreground">Members *</label>
                            <button
                                type="button"
                                @click="addMember"
                                class="text-xs text-orange-400 hover:text-orange-300"
                            >+ Add member</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(member, index) in form.members" :key="index" class="flex items-center gap-2">
                                <input
                                    v-model="member.name"
                                    type="text"
                                    :placeholder="`Member ${index + 1} name`"
                                    required
                                    class="min-w-0 flex-1 rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                                />
                                <input
                                    v-model="member.phone"
                                    type="tel"
                                    placeholder="Phone (opt)"
                                    class="w-28 rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2 text-sm outline-none focus:border-primary/60"
                                />
                                <button
                                    v-if="form.members.length > 1"
                                    type="button"
                                    @click="removeMember(index)"
                                    class="shrink-0 text-muted-foreground hover:text-red-400"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors['members']" class="mt-1 text-xs text-red-400">{{ form.errors['members'] }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-1">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="flex-1 rounded-xl border border-sidebar-border/50 py-2.5 text-sm font-semibold transition-colors hover:bg-accent"
                        >Cancel</button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 rounded-xl bg-orange-600 py-2.5 text-sm font-semibold text-white shadow-md shadow-orange-500/20 transition-colors hover:bg-orange-500 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Creating…' : 'Create Group' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
