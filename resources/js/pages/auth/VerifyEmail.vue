<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { MailOpen } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verify your Email',
        description:
            'We sent a 6-digit code to your email address.',
    },
});

defineProps<{
    status?: string;
}>();

const form = useForm({
    code: '',
});

const submitOtp = () => {
    form.post('/email/verify-otp');
};
</script>

<template>
    <Head title="Verify Email OTP" />

    <div class="mb-6 flex justify-center">
        <div
            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/20"
        >
            <MailOpen class="h-7 w-7 text-primary" />
        </div>
    </div>

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-6 text-center text-sm font-medium text-green-600"
    >
        A new OTP has been sent to the email address you provided.
    </div>

    <form @submit.prevent="submitOtp" class="flex flex-col gap-5">
        <div class="grid gap-2">
            <Label for="code">6-Digit OTP</Label>
            <input
                id="code"
                v-model="form.code"
                name="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                required
                autofocus
                autocomplete="one-time-code"
                placeholder="000000"
                class="h-12 w-full rounded-lg border border-input bg-background text-center text-2xl font-bold tracking-[0.5em] transition outline-none focus:ring-1 focus:ring-ring"
            />
            <InputError :message="form.errors.code" />
        </div>

        <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
        >
            <Spinner v-if="form.processing" />
            Verify Email
        </Button>
    </form>

    <div class="mt-6 flex flex-col items-center gap-4 text-sm text-muted-foreground">
        <div>
            Didn't receive it?
            <form @submit.prevent="useForm().post(send.path())" class="inline">
                <button type="submit" class="font-medium text-primary hover:underline" :disabled="useForm().processing">
                    Resend OTP
                </button>
            </form>
        </div>

        <TextLink :href="logout()" as="button">
            Log out
        </TextLink>
    </div>
</template>
