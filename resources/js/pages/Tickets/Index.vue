<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, TicketCheck } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';

type CardRow = {
    id: number;
    title: string;
    list_name: string | null;
    board: { id: number; name: string } | null;
    workspace: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
    assignees_count: number;
    is_completed: boolean;
    is_archived: boolean;
    created_at: string | null;
    updated_at: string | null;
};

type Pagination<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Filters = { search: string; per_page: number };
type SortState = { field: string; direction: 'asc' | 'desc' };

const props = defineProps<{
    cards: Pagination<CardRow>;
    filters: Filters;
    sort: SortState;
}>();

const search = ref(props.filters.search);
const perPage = ref(String(props.filters.per_page));

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(
              new Date(value),
          )
        : '-';

const statusLabel = (card: CardRow) => {
    if (card.is_archived) {
        return 'Archived';
    }

    return card.is_completed ? 'Completed' : 'Open';
};

const sortHref = (field: string) =>
    route('cards.index', {
        search: search.value,
        per_page: perPage.value,
        sort: field,
        direction:
            props.sort.field === field && props.sort.direction === 'asc'
                ? 'desc'
                : 'asc',
    });

const sortLabel = (field: string) =>
    props.sort.field === field
        ? props.sort.direction === 'asc'
            ? '↑'
            : '↓'
        : '';

const applyFilters = () => {
    router.get(
        route('cards.index'),
        {
            search: search.value,
            per_page: perPage.value,
            sort: props.sort.field,
            direction: props.sort.direction,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Tickets" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div>
            <p class="text-sm font-medium text-primary">Company listing</p>
            <h1
                class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
            >
                <TicketCheck class="size-6 text-primary" />
                Tickets
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                View company cards and assignments.
            </p>
        </div>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="ticket-search"
                        class="text-sm font-medium text-muted-foreground"
                        >Search</label
                    >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="ticket-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Ticket, board, workspace, creator"
                            @keydown.enter.prevent="applyFilters"
                        />
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="grid gap-1">
                        <label
                            for="ticket-per-page"
                            class="text-sm font-medium text-muted-foreground"
                            >Per page</label
                        >
                        <select
                            id="ticket-per-page"
                            v-model="perPage"
                            class="h-9 cursor-pointer rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <Button type="submit">Apply</Button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-primary text-primary-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('title')"
                                    >Ticket {{ sortLabel('title') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('list_name')"
                                    >List {{ sortLabel('list_name') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('board_name')"
                                    >Board {{ sortLabel('board_name') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('workspace_name')"
                                    >Workspace
                                    {{ sortLabel('workspace_name') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('creator_name')"
                                    >Created By
                                    {{ sortLabel('creator_name') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('assignees_count')"
                                    >Assigned
                                    {{ sortLabel('assignees_count') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('is_completed')"
                                    >Status {{ sortLabel('is_completed') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('created_at')"
                                    >Created {{ sortLabel('created_at') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('updated_at')"
                                    >Updated {{ sortLabel('updated_at') }}</a
                                >
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="card in cards.data"
                            :key="card.id"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-5 py-4 font-medium">
                                {{ card.title }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ card.list_name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ card.board?.name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ card.workspace?.name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ card.creator?.name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ card.assignees_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ statusLabel(card) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(card.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(card.updated_at) }}
                            </td>
                        </tr>
                        <tr v-if="cards.data.length === 0">
                            <td
                                colspan="9"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No tickets found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="cards" />
            </div>
        </div>
    </section>
</template>
