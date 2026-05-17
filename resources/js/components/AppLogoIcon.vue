<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps<{ className?: HTMLAttributes['class'] }>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M9 3h22a4 4 0 0 1 4 4v27l-2.5-3L30 34l-2.5-3L25 34l-2.5-3L20 34l-2.5-3L15 34l-2.5-3L10 34l-2.5-3L5 34V7a4 4 0 0 1 4-4zm3 9h16v3H12v-3zm0 6h16v3H12v-3zm8 6h8v3h-8v-3z"
        />
    </svg>
</template>
