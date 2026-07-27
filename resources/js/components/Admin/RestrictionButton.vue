<script setup lang="ts">
import { Lock, LockOpen } from '@lucide/vue';

import { Button } from '@/Components/UI/button';

defineProps<{
    restricted: boolean;
    processing?: boolean;
}>();

defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <Button
        type="button"
        size="sm"
        :variant="restricted ? 'outline' : 'secondary'"
        :disabled="processing"
        @click="$emit('toggle')"
    >
        <LockOpen v-if="restricted" class="size-4" />
        <Lock v-else class="size-4" />
        {{ restricted ? 'Unrestrict' : 'Restrict' }}
    </Button>
</template>
