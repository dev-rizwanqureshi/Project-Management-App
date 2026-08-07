<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import AppLogoIcon from '@/Components/AppLogoIcon.vue';

defineOptions({ inheritAttrs: false });

withDefaults(
    defineProps<{
        iconClass?: HTMLAttributes['class'];
    }>(),
    {
        iconClass: 'size-[21px] fill-current',
    },
);
</script>

<template>
    <span
        v-bind="$attrs"
        class="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-[#f04b67] text-white shadow-sm"
    >
        <AppLogoIcon :class="iconClass" />
    </span>
</template>
