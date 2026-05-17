<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { phone as otpPhone, send } from '@/routes/otp';
import { submit } from '@/routes/otp/verify';

defineOptions({
    layout: {
        title: 'Enter your OTP',
        description: 'We sent a 6-digit code to your WhatsApp',
    },
});

const props = defineProps<{ phone: string }>();
</script>

<template>
    <Head title="Verify OTP" />

    <div class="mb-6 flex justify-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-green-500/20">
            <MessageCircle class="h-7 w-7 text-green-500" />
        </div>
    </div>

    <p class="mb-6 text-center text-sm text-muted-foreground">
        Code sent to <span class="font-semibold text-foreground">{{ phone }}</span>
    </p>

    <Form
        v-bind="submit.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <input type="hidden" name="phone" :value="phone" />

        <div class="grid gap-2">
            <Label for="code">6-Digit OTP</Label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                required
                autofocus
                autocomplete="one-time-code"
                placeholder="000000"
                class="h-12 w-full rounded-lg border border-input bg-background text-center text-2xl font-bold tracking-[0.5em] outline-none transition focus:ring-1 focus:ring-ring"
            />
            <InputError :message="errors.code" />
        </div>

        <Button type="submit" class="w-full bg-green-600 hover:bg-green-500" :disabled="processing">
            <Spinner v-if="processing" />
            Verify &amp; Login
        </Button>
    </Form>

    <div class="mt-6 text-center text-sm text-muted-foreground">
        Didn't receive it?
        <TextLink :href="otpPhone()">Resend OTP</TextLink>
    </div>
</template>
