<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import { Button } from '@/Components/UI/button';

type Pagination = {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

defineProps<{
    pagination: Pagination;
}>();

const visit = (url: string | null) => {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-muted-foreground">
            Showing {{ pagination.from ?? 0 }} to {{ pagination.to ?? 0 }} of
            {{ pagination.total }} entries
        </p>

        <div class="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                :disabled="!pagination.prev_page_url"
                @click="visit(pagination.prev_page_url)"
            >
                Previous
            </Button>
            <span class="text-muted-foreground">
                Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            <Button
                type="button"
                variant="outline"
                :disabled="!pagination.next_page_url"
                @click="visit(pagination.next_page_url)"
            >
                Next
            </Button>
        </div>
    </div>
</template>
