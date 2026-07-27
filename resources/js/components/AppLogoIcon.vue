<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M13 36.5V11.5h14.2c5.8 0 9.8 3.7 9.8 9.1 0 3.9-2.1 6.9-5.5 8.2l5.9 7.7h-7.7l-5.3-7.1h-5v7.1H13Zm6.4-12.6h7.1c2.5 0 4-1.2 4-3.3s-1.5-3.3-4-3.3h-7.1v6.6Zm-.1 12.6h-6.2L22.8 27h7.8l-11.3 9.5Z"
        />
    </svg>
</template>
