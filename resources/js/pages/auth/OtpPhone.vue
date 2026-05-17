<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { MessageCircle, Phone } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { send } from '@/routes/otp';

defineOptions({
    layout: {
        title: 'Login with WhatsApp',
        description: 'Enter your WhatsApp number to receive a one-time code',
    },
});
</script>

<template>
    <Head title="WhatsApp Login" />

    <div class="mb-6 flex justify-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-green-500/20">
            <MessageCircle class="h-7 w-7 text-green-500" />
        </div>
    </div>

    <Form v-bind="send.form()" v-slot="{ errors, processing }" class="flex flex-col gap-5">
        <div class="grid gap-2">
            <Label for="phone">WhatsApp Number</Label>
            <div class="relative">
                <Phone class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    id="phone"
                    name="phone"
                    type="tel"
                    required
                    autofocus
                    class="pl-9"
                    placeholder="+92 300 1234567"
                    autocomplete="tel"
                />
            </div>
            <p class="text-muted-foreground text-xs">Include country code (e.g. +92 for Pakistan)</p>
            <InputError :message="errors.phone" />
        </div>

        <Button type="submit" class="w-full bg-green-600 hover:bg-green-500" :disabled="processing">
            <Spinner v-if="processing" />
            Send OTP via WhatsApp
        </Button>
    </Form>

    <div class="mt-6 text-center text-sm text-muted-foreground">
        Prefer email?
        <TextLink :href="login()">Log in with password</TextLink>
    </div>
</template>
