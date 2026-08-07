<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TicketDetail from '@/Components/TicketDetail.vue';
import type {
    TicketBoardContext,
    TicketDetail as TicketDetailType,
} from '@/types';

defineProps<{
    board: TicketBoardContext;
    ticket: TicketDetailType;
}>();
</script>

<template>
    <Head :title="`${ticket.key} ${ticket.title}`" />

    <section class="flex flex-1 bg-muted/25 p-3 sm:p-5">
        <div
            class="mx-auto flex min-h-[calc(100svh-7rem)] w-full max-w-6xl flex-col overflow-hidden rounded-lg border border-border bg-background shadow-sm"
        >
            <TicketDetail
                :ticket="ticket"
                :board="board"
                :lists="board.lists"
                mode="full"
            />
        </div>
    </section>
</template>
