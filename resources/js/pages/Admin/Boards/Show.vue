<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
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

type BoardSummary = {
    id: number;
    name: string;
    workspace: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
    created_at: string | null;
    updated_at: string | null;
};

type StatCard = {
    label: string;
    value: number;
};

type CardRow = {
    id: number;
    title: string;
    list_name: string | null;
    is_completed: boolean;
    is_archived: boolean;
    is_restricted: boolean;
    creator: { id: number; name: string; email: string } | null;
    assignees_count: number;
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
    view_tickets: boolean;
    restrict_boards: boolean;
    restrict_tickets: boolean;
};

const props = defineProps<{
    board: BoardSummary;
    stats: StatCard[];
    cards: Pagination<CardRow>;
    filters: Filters;
    sort: SortState;
    can: PageAccess;
}>();

const search = ref(props.filters.search);
const perPage = ref(String(props.filters.per_page));
const restriction = ref(props.filters.restriction ?? 'active');
const restrictionProcessingId = ref<number | null>(null);
const icons = [TicketCheck, Users, CheckCircle2];

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

const statusLabel = (card: CardRow) => {
    if (card.is_archived) {
        return 'Archived';
    }

    return card.is_completed ? 'Completed' : 'Open';
};

const sortHref = (field: string) =>
    route('admin.boards.show', {
        board: props.board.id,
        search: search.value,
        per_page: perPage.value,
        restriction: props.can.restrict_tickets ? restriction.value : undefined,
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
        route('admin.boards.show', props.board.id),
        {
            search: search.value,
            per_page: perPage.value,
            restriction: props.can.restrict_tickets
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

const updateRestriction = (card: CardRow) => {
    restrictionProcessingId.value = card.id;

    router.put(
        route('admin.cards.restriction.update', card.id),
        {
            restricted: !card.is_restricted,
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
    <Head :title="`${board.name} Tickets`" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Button
                    v-if="board.workspace"
                    as-child
                    variant="outline"
                    class="mb-4"
                >
                    <Link
                        :href="
                            route('admin.boards.index', {
                                workspace_id: board.workspace.id,
                            })
                        "
                    >
                        <ArrowLeft class="size-4" />
                        Workspace
                    </Link>
                </Button>

                <p class="text-sm font-medium text-primary">Board tickets</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                    {{ board.name }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    <Link
                        v-if="board.workspace"
                        :href="
                            route('admin.boards.index', {
                                workspace_id: board.workspace.id,
                            })
                        "
                        class="cursor-pointer hover:text-primary"
                    >
                        {{ board.workspace.name }}
                    </Link>
                    <span v-else>No workspace</span> · Created by
                    <Link
                        v-if="board.creator"
                        :href="route('admin.users.show', board.creator.id)"
                        class="cursor-pointer hover:text-primary"
                    >
                        {{ board.creator.name }}
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
            v-if="can.view_tickets"
            class="rounded-lg border border-border bg-card shadow-sm"
        >
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="ticket-search"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Search tickets
                    </label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="ticket-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Ticket title, description, creator"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <div v-if="can.restrict_tickets" class="grid gap-1">
                        <label
                            for="board-ticket-restriction"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Visibility
                        </label>
                        <select
                            id="board-ticket-restriction"
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
                            for="ticket-per-page"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Per page
                        </label>
                        <select
                            id="ticket-per-page"
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
                                <Link :href="sortHref('title')"
                                    >Ticket {{ sortLabel('title') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">List</th>
                            <th class="px-5 py-3 font-semibold">Created By</th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('assignees_count')"
                                    >Assigned Users
                                    {{ sortLabel('assignees_count') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('is_completed')"
                                    >Status
                                    {{ sortLabel('is_completed') }}</Link
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
                                v-if="can.restrict_tickets"
                                class="px-5 py-3 font-semibold"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="card in cards.data"
                            :key="card.id"
                            class="border-b border-border last:border-0"
                            :class="
                                card.is_restricted ? 'riraa-restricted-row' : ''
                            "
                        >
                            <td class="px-5 py-4 font-medium">
                                {{ card.title }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ card.list_name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    v-if="card.creator"
                                    :href="
                                        route(
                                            'admin.users.show',
                                            card.creator.id,
                                        )
                                    "
                                    class="hover:text-primary"
                                >
                                    {{ card.creator.name }}
                                </Link>
                                <span v-else>-</span>
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
                            <td v-if="can.restrict_tickets" class="px-5 py-4">
                                <RestrictionButton
                                    :restricted="card.is_restricted"
                                    :processing="
                                        restrictionProcessingId === card.id
                                    "
                                    @toggle="updateRestriction(card)"
                                />
                            </td>
                        </tr>
                        <tr v-if="cards.data.length === 0">
                            <td
                                :colspan="can.restrict_tickets ? 8 : 7"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No tickets found for this board.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="cards" />
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-border bg-card p-6 text-sm text-muted-foreground shadow-sm"
        >
            This admin role cannot view ticket listings.
        </div>
    </section>
</template>
