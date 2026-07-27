<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FolderKanban,
    Search,
    TicketCheck,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import RestrictionButton from '@/Components/Admin/RestrictionButton.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';

type WorkspaceSummary = {
    id: number;
    name: string;
    company: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
    created_at: string | null;
    updated_at: string | null;
};

type StatCard = {
    label: string;
    value: number;
};

type BoardRow = {
    id: number;
    name: string;
    tickets_count: number;
    users_count: number;
    is_restricted: boolean;
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

type Filters = {
    search: string;
    per_page: number;
    restriction?: 'active' | 'restricted' | 'all';
};

type SortState = {
    field: string;
    direction: 'asc' | 'desc';
};

type PageAccess = {
    view_boards: boolean;
    restrict_workspaces: boolean;
    restrict_boards: boolean;
};

const props = defineProps<{
    workspace: WorkspaceSummary;
    stats: StatCard[];
    boards: Pagination<BoardRow>;
    filters: Filters;
    sort: SortState;
    can: PageAccess;
}>();

const search = ref(props.filters.search);
const perPage = ref(String(props.filters.per_page));
const restriction = ref(props.filters.restriction ?? 'active');
const restrictionProcessingId = ref<number | null>(null);
const icons = [FolderKanban, TicketCheck, Users];

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

const sortHref = (field: string) =>
    route('admin.workspaces.show', {
        workspace: props.workspace.id,
        search: search.value,
        per_page: perPage.value,
        restriction: props.can.restrict_boards ? restriction.value : undefined,
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
        route('admin.workspaces.show', props.workspace.id),
        {
            search: search.value,
            per_page: perPage.value,
            restriction: props.can.restrict_boards
                ? restriction.value
                : undefined,
            sort: props.sort.field,
            direction: props.sort.direction,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const updateRestriction = (board: BoardRow) => {
    restrictionProcessingId.value = board.id;

    router.put(
        route('admin.boards.restriction.update', board.id),
        {
            restricted: !board.is_restricted,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                restrictionProcessingId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head :title="`${workspace.name} Boards`" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Button
                    v-if="workspace.creator"
                    as-child
                    variant="outline"
                    class="mb-4"
                >
                    <Link
                        :href="route('admin.users.show', workspace.creator.id)"
                    >
                        <ArrowLeft class="size-4" />
                        User
                    </Link>
                </Button>

                <p class="text-sm font-medium text-primary">Workspace boards</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                    {{ workspace.name }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    <Link
                        v-if="workspace.company"
                        :href="
                            route('admin.workspaces.index', {
                                company_id: workspace.company.id,
                            })
                        "
                        class="cursor-pointer hover:text-primary"
                    >
                        {{ workspace.company.name }}
                    </Link>
                    <span v-else>No company</span> · Created by
                    <Link
                        v-if="workspace.creator"
                        :href="route('admin.users.show', workspace.creator.id)"
                        class="cursor-pointer hover:text-primary"
                    >
                        {{ workspace.creator.name }}
                    </Link>
                    <span v-else>Unknown</span>
                </p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <article
                v-for="(stat, index) in stats"
                :key="stat.label"
                class="rounded-lg border border-border bg-card p-5 shadow-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            {{ stat.label }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold tracking-normal">
                            {{ stat.value }}
                        </p>
                    </div>
                    <div
                        class="flex size-11 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <component :is="icons[index] ?? Users" class="size-5" />
                    </div>
                </div>
            </article>
        </div>

        <div
            v-if="can.view_boards"
            class="rounded-lg border border-border bg-card shadow-sm"
        >
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="board-search"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Search boards
                    </label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="board-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Board name or description"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <div v-if="can.restrict_boards" class="grid gap-1">
                        <label
                            for="workspace-board-restriction"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Visibility
                        </label>
                        <select
                            id="workspace-board-restriction"
                            v-model="restriction"
                            class="riraa-select"
                            @change="applyFilters"
                        >
                            <option value="active">Active</option>
                            <option value="restricted">Restricted</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div class="grid gap-1">
                        <label
                            for="board-per-page"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Per page
                        </label>
                        <select
                            id="board-per-page"
                            v-model="perPage"
                            class="riraa-select"
                            @change="applyFilters"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-primary text-primary-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('name')"
                                    >Board {{ sortLabel('name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('tickets_count')"
                                    >Tickets
                                    {{ sortLabel('tickets_count') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('users_count')"
                                    >Users {{ sortLabel('users_count') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('created_at')"
                                    >Created {{ sortLabel('created_at') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('updated_at')"
                                    >Updated {{ sortLabel('updated_at') }}</Link
                                >
                            </th>
                            <th
                                v-if="can.restrict_boards"
                                class="px-5 py-3 font-semibold"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="board in boards.data"
                            :key="board.id"
                            class="border-b border-border last:border-0"
                            :class="
                                board.is_restricted
                                    ? 'riraa-restricted-row'
                                    : ''
                            "
                        >
                            <td class="px-5 py-4 font-medium">
                                <Link
                                    :href="
                                        route('admin.cards.index', {
                                            board_id: board.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ board.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    :href="
                                        route('admin.cards.index', {
                                            board_id: board.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ board.tickets_count }}
                                </Link>
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
                            <td v-if="can.restrict_boards" class="px-5 py-4">
                                <RestrictionButton
                                    :restricted="board.is_restricted"
                                    :processing="
                                        restrictionProcessingId === board.id
                                    "
                                    @toggle="updateRestriction(board)"
                                />
                            </td>
                        </tr>
                        <tr v-if="boards.data.length === 0">
                            <td
                                :colspan="can.restrict_boards ? 6 : 5"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No boards found for this workspace.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="boards" />
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-border bg-card p-6 text-sm text-muted-foreground shadow-sm"
        >
            This admin role cannot view board listings.
        </div>
    </section>
</template>
