<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground"
    >
        <AppLogoIcon class="size-5" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-bold tracking-tight">ExpenseBro</span>
        <span class="truncate text-[10px] text-muted-foreground"
            >Track smarter</span
        >
    </div>
</template>
