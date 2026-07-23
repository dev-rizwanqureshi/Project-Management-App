<script setup lang="ts">
import { Button } from '@/Components/UI/button';
import type { ButtonVariants } from '@/Components/UI/button';

const props = defineProps<{
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
}>();
</script>

<template>
    <Button
        :variant="props.variant"
        :size="props.size"
        :type="props.type ?? 'button'"
        :disabled="props.disabled"
    >
        <slot />
    </Button>
</template>
