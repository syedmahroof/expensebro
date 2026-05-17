<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Baby, Building, Building2, Car, Home, Plus, Tag, Users, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Entity, EntityType } from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import { dashboard } from '@/routes';
import { destroy, index as entitiesIndex, store, update } from '@/routes/entities';

const { confirm } = useConfirm();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Entities', href: entitiesIndex() },
        ],
    },
});

defineProps<{ entities: Entity[] }>();

const page = usePage<{ userCurrency: string }>();
const currency = computed(() => page.props.userCurrency ?? 'PKR');

const showModal = ref(false);
const editingEntity = ref<Entity | null>(null);

const form = useForm({
    name: '',
    type: 'other' as EntityType,
    color: '#6366f1',
    emoji: '',
    description: '',
});

const entityTypeIcons: Record<EntityType, typeof Tag> = {
    vehicle: Car,
    office: Building2,
    team: Users,
    house: Home,
    children: Baby,
    property: Building,
    other: Tag,
};

const entityTypeColors: Record<EntityType, string> = {
    vehicle: '#3b82f6',
    office: '#8b5cf6',
    team: '#6366f1',
    house: '#f97316',
    children: '#ec4899',
    property: '#14b8a6',
    other: '#6b7280',
};

const entityTypes: Array<{ value: EntityType; label: string }> = [
    { value: 'vehicle', label: 'Vehicle' },
    { value: 'office', label: 'Office' },
    { value: 'team', label: 'Team' },
    { value: 'house', label: 'House' },
    { value: 'children', label: 'Children' },
    { value: 'property', label: 'Property' },
    { value: 'other', label: 'Other' },
];

const colorOptions = ['#6366f1', '#3b82f6', '#8b5cf6', '#ec4899', '#10b981', '#f97316', '#f59e0b', '#ef4444', '#14b8a6', '#6b7280'];

function openCreate() {
    editingEntity.value = null;
    form.reset();
    form.color = '#6366f1';
    showModal.value = true;
}

function openEdit(entity: Entity) {
    editingEntity.value = entity;
    form.name = entity.name;
    form.type = entity.type;
    form.color = entity.color;
    form.emoji = entity.emoji ?? '';
    form.description = entity.description ?? '';
    showModal.value = true;
}

function onTypeChange() {
    form.color = entityTypeColors[form.type];
}

function submit() {
    if (editingEntity.value) {
        form.put(update({ entity: editingEntity.value.id }).url, {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

async function deleteEntity(id: number) {
    const ok = await confirm('All tagged transactions will have their entity tag removed.', {
        title: 'Delete entity?',
        confirmText: 'Delete',
        danger: true,
    });
    if (!ok) { return; }
    form.delete(destroy({ entity: id }).url, { preserveScroll: true });
}

function fmt(amount: number) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: currency.value,
        maximumFractionDigits: 0,
    }).format(amount ?? 0);
}
</script>

<template>
    <Head title="Entities" />

    <div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Entities</h1>
                <p class="text-sm text-muted-foreground">Tag expenses by vehicle, office, team, house and more</p>
            </div>
            <button
                @click="openCreate"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
            >
                <Plus class="h-4 w-4" />
                Add Entity
            </button>
        </div>

        <!-- Empty state -->
        <div v-if="entities.length === 0" class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border/50 p-16 text-center">
            <div>
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10">
                    <Tag class="h-6 w-6 text-primary" />
                </div>
                <p class="font-medium">No entities yet</p>
                <p class="mt-1 text-sm text-muted-foreground">Create entities to group your expenses — vehicles, offices, houses, and more.</p>
                <button @click="openCreate" class="mt-3 text-sm text-primary hover:text-primary/80">
                    Create your first entity →
                </button>
            </div>
        </div>

        <!-- Compact entity list -->
        <div v-else class="flex flex-col gap-2">
            <div
                v-for="entity in entities"
                :key="entity.id"
                class="group flex items-center gap-3 rounded-xl border border-sidebar-border/50 bg-card px-3 py-2.5 transition-all hover:border-sidebar-border hover:shadow-sm"
            >
                <!-- Icon -->
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base"
                    :style="{ backgroundColor: entity.color + '20' }"
                >
                    <span v-if="entity.emoji" class="text-lg leading-none">{{ entity.emoji }}</span>
                    <component v-else :is="entityTypeIcons[entity.type] ?? Tag" class="h-4.5 w-4.5" :style="{ color: entity.color }" />
                </div>

                <!-- Name + type -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="truncate text-sm font-semibold">{{ entity.name }}</p>
                        <span
                            class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                            :style="{ backgroundColor: entity.color + '15', color: entity.color }"
                        >{{ entity.type }}</span>
                    </div>
                    <p v-if="entity.description" class="mt-0.5 truncate text-xs text-muted-foreground">{{ entity.description }}</p>
                </div>

                <!-- Stats -->
                <div class="shrink-0 text-right">
                    <p class="text-sm font-bold tabular-nums">{{ fmt(entity.total_spent ?? 0) }}</p>
                    <p class="text-xs text-muted-foreground">{{ entity.transactions_count ?? 0 }} txns</p>
                </div>

                <!-- Actions (hover) -->
                <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                    <button
                        @click="openEdit(entity)"
                        class="rounded-lg px-2 py-1 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    >Edit</button>
                    <button
                        @click="deleteEntity(entity.id)"
                        class="rounded-lg px-2 py-1 text-xs text-red-400/60 transition-colors hover:bg-red-500/10 hover:text-red-400"
                    >Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-sidebar-border bg-card p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-base font-semibold">{{ editingEntity ? 'Edit Entity' : 'New Entity' }}</h2>
                    <button @click="showModal = false" class="text-muted-foreground hover:text-foreground">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="My Honda, Downtown Office, Alice…"
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Type</label>
                            <select
                                v-model="form.type"
                                @change="onTypeChange"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option v-for="et in entityTypes" :key="et.value" :value="et.value">{{ et.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Emoji <span class="text-muted-foreground">(optional)</span></label>
                            <input
                                v-model="form.emoji"
                                type="text"
                                placeholder="🚗 🏢 👶"
                                maxlength="4"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Color</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colorOptions"
                                :key="c"
                                type="button"
                                @click="form.color = c"
                                class="h-6 w-6 rounded-full transition-transform hover:scale-110"
                                :style="{ backgroundColor: c }"
                                :class="form.color === c ? 'ring-2 ring-offset-2 ring-offset-card ring-white/50' : ''"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Description <span class="text-muted-foreground">(optional)</span></label>
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Brief description…"
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="showModal = false"
                            class="h-10 flex-1 rounded-lg border border-input text-sm transition-colors hover:bg-accent">Cancel</button>
                        <button type="submit" :disabled="form.processing"
                            class="h-10 flex-1 rounded-lg bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60">
                            {{ form.processing ? 'Saving…' : (editingEntity ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
