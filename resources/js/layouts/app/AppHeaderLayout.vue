<script setup lang="ts">
import AppContent from '@/Components/AppContent.vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppShell from '@/Components/AppShell.vue';
import { Toaster } from '@/Components/UI/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
