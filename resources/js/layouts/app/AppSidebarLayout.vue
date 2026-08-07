<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppContent from '@/Components/AppContent.vue';
import AppShell from '@/Components/AppShell.vue';
import AppSidebar from '@/Components/AppSidebar.vue';
import AppSidebarHeader from '@/Components/AppSidebarHeader.vue';
import { Toaster } from '@/Components/UI/sonner';
import { useAuthStore } from '@/stores/useAuthStore';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const authStore = useAuthStore();
authStore.setUser(page.props.auth.user);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="riraa-app-page flex min-h-0 flex-1 flex-col">
                <slot />
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
