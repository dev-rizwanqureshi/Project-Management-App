<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FolderKanban, Search } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';

type BoardRow = {
    id: number;
    name: string;
    workspace: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
    tickets_count: number;
    users_count: number;
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
    boards: Pagination<BoardRow>;
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

const sortHref = (field: string) =>
    route('boards.index', {
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
        route('boards.index'),
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
    <Head title="Boards" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div>
            <p class="text-sm font-medium text-primary">Company listing</p>
            <h1
                class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
            >
                <FolderKanban class="size-6 text-primary" />
                Boards
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                View boards with workspace, ticket, and user totals.
            </p>
        </div>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="board-search"
                        class="text-sm font-medium text-muted-foreground"
                        >Search</label
                    >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="board-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Board, workspace, creator"
                        />
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="grid gap-1">
                        <label
                            for="board-per-page"
                            class="text-sm font-medium text-muted-foreground"
                            >Per page</label
                        >
                        <select
                            id="board-per-page"
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
                                <a :href="sortHref('name')"
                                    >Board {{ sortLabel('name') }}</a
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
                                <a :href="sortHref('tickets_count')"
                                    >Tickets {{ sortLabel('tickets_count') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('users_count')"
                                    >Users {{ sortLabel('users_count') }}</a
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
                            v-for="board in boards.data"
                            :key="board.id"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-5 py-4 font-medium">
                                {{ board.name }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ board.workspace?.name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ board.creator?.name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ board.tickets_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ board.users_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(board.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(board.updated_at) }}
                            </td>
                        </tr>
                        <tr v-if="boards.data.length === 0">
                            <td
                                colspan="7"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No boards found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="boards" />
            </div>
        </div>
    </section>
</template>
