<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Check, Globe, RefreshCw } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { edit as editPreferences, update as updatePreferences } from '@/routes/preferences';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Preferences', href: editPreferences() }],
    },
});

const props = defineProps<{
    currencies: Record<string, string>;
    locales: Record<string, string>;
}>();

const page = usePage<{
    auth: { user: { default_currency: string; locale: string } };
}>();
const user = computed(() => page.props.auth.user);

const saved = ref(false);

const form = useForm({
    default_currency: user.value?.default_currency ?? 'PKR',
    locale: user.value?.locale ?? 'en',
});

function submit() {
    form.submit(updatePreferences(), {
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => {
                saved.value = false;
            }, 3000);
        },
    });
}
</script>

<template>
    <Head title="Preferences" />

    <h1 class="sr-only">Preferences</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Preferences"
            description="Set your default currency and display language"
        />

        <form @submit.prevent="submit" class="max-w-md space-y-6">
            <!-- Default Currency -->
            <div
                class="space-y-4 rounded-xl border border-sidebar-border/50 bg-card p-5"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <RefreshCw class="h-4 w-4 text-primary" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">Default Currency</p>
                        <p class="text-xs text-muted-foreground">
                            All amounts are converted to this for reporting
                        </p>
                    </div>
                </div>

                <div>
                    <select
                        v-model="form.default_currency"
                        class="w-full rounded-lg border border-sidebar-border/50 bg-sidebar-accent/30 px-3 py-2.5 text-sm outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/20"
                    >
                        <option
                            v-for="(name, code) in currencies"
                            :key="code"
                            :value="code"
                        >
                            {{ code }} — {{ name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.default_currency"
                        class="mt-1 text-xs text-red-400"
                    >
                        {{ form.errors.default_currency }}
                    </p>
                </div>

                <div
                    class="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3 text-xs text-amber-400"
                >
                    When you add a transaction in a different currency, the
                    amount is automatically converted to
                    <strong>{{ form.default_currency }}</strong> using current
                    exchange rates.
                </div>
            </div>

            <!-- Language -->
            <div
                class="space-y-4 rounded-xl border border-sidebar-border/50 bg-card p-5"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <Globe class="h-4 w-4 text-primary" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">Display Language</p>
                        <p class="text-xs text-muted-foreground">
                            UI labels and messages language
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="(name, code) in locales"
                        :key="code"
                        type="button"
                        @click="form.locale = code"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-left text-sm transition-all"
                        :class="
                            form.locale === code
                                ? 'border-primary/60 bg-primary/10 text-primary'
                                : 'border-sidebar-border/50 hover:bg-accent'
                        "
                    >
                        <span class="flex-1 truncate text-xs">{{ name }}</span>
                        <Check
                            v-if="form.locale === code"
                            class="h-3.5 w-3.5 shrink-0 text-primary"
                        />
                    </button>
                </div>
                <p v-if="form.errors.locale" class="text-xs text-red-400">
                    {{ form.errors.locale }}
                </p>
            </div>

            <!-- Save -->
            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/20 transition-colors hover:bg-primary/90 disabled:opacity-60"
                >
                    {{ form.processing ? 'Saving…' : 'Save Preferences' }}
                </button>
                <Transition name="fade">
                    <span
                        v-if="saved"
                        class="flex items-center gap-1.5 text-sm text-emerald-400"
                    >
                        <Check class="h-4 w-4" /> Saved!
                    </span>
                </Transition>
            </div>
        </form>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
