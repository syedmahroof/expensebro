<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Globe } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { currency as onboardingCurrency } from '@/routes/onboarding';

const page = usePage<{
    needsCurrencySetup: boolean;
    currencies: Record<string, string>;
}>();

const dismissed = ref(false);
const show = computed(() => page.props.needsCurrencySetup && !dismissed.value);
const currencies = computed(() => page.props.currencies ?? {});

const form = useForm({
    default_currency: 'USD',
});

function submit() {
    form.post(onboardingCurrency().url, {
        preserveScroll: true,
        onSuccess: () => {
            dismissed.value = true;
        },
    });
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-sidebar-border bg-card p-6 shadow-2xl"
            >
                <div class="mb-2 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10"
                    >
                        <Globe class="h-5 w-5 text-primary" />
                    </div>
                    <h2 class="text-base font-semibold">
                        Choose your currency
                    </h2>
                </div>
                <p class="mb-5 text-sm text-muted-foreground">
                    All your balances and reports will use this currency. You
                    can change it anytime in Preferences.
                </p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <select
                            v-model="form.default_currency"
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:ring-1 focus:ring-ring"
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

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="h-10 w-full rounded-lg bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving...' : 'Continue' }}
                    </button>
                </form>
            </div>
        </div>
    </Teleport>
</template>
