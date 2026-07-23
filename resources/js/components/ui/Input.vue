<script setup lang="ts">
import { Input } from '@/Components/UI/input';

const model = defineModel<string | number>();

defineProps<{
    id?: string;
    name?: string;
    type?: string;
    placeholder?: string;
    autocomplete?: string;
    disabled?: boolean;
}>();
</script>

<template>
    <Input
        v-model="model"
        :id="id"
        :name="name"
        :type="type ?? 'text'"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :disabled="disabled"
    />
</template>
