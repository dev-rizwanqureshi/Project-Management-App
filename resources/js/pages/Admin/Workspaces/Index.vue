<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderKanban, Search } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import RestrictionButton from '@/Components/Admin/RestrictionButton.vue';
import { Input } from '@/Components/UI/input';

type Summary = {
    id: number;
    name: string;
};

type CreatorSummary = Summary & {
    email: string;
};

type WorkspaceRow = {
    id: number;
    name: string;
    company: Summary | null;
    creator: CreatorSummary | null;
    boards_count: number;
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
    company_id?: number | null;
    user_id?: number | null;
    restriction?: 'active' | 'restricted' | 'all';
};

type SortState = {
    field: string;
    direction: 'asc' | 'desc';
};

type PageAccess = {
    restrict_workspaces: boolean;
    view_boards: boolean;
};

const props = defineProps<{
    workspaces: Pagination<WorkspaceRow>;
    filters: Filters;
    sort: SortState;
    can: PageAccess;
}>();

const search = ref(props.filters.search);
const perPage = ref(String(props.filters.per_page));
const restriction = ref(props.filters.restriction ?? 'active');
const restrictionProcessingId = ref<number | null>(null);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

const filterParams = () => ({
    search: search.value,
    per_page: perPage.value,
    company_id: props.filters.company_id ?? undefined,
    user_id: props.filters.user_id ?? undefined,
    restriction: props.can.restrict_workspaces ? restriction.value : undefined,
});

const sortHref = (field: string) =>
    route('admin.workspaces.index', {
        ...filterParams(),
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
        route('admin.workspaces.index'),
        {
            ...filterParams(),
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

const updateRestriction = (workspace: WorkspaceRow) => {
    restrictionProcessingId.value = workspace.id;

    router.put(
        route('admin.workspaces.restriction.update', workspace.id),
        {
            restricted: !workspace.is_restricted,
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
    <Head title="Workspaces" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">Platform listing</p>
                <h1
                    class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
                >
                    <FolderKanban class="size-6 text-primary" />
                    Workspaces
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    View all workspaces with board, ticket, and user totals.
                </p>
            </div>
        </div>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="workspace-list-search"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Search
                    </label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="workspace-list-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Workspace, company, creator"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <div v-if="can.restrict_workspaces" class="grid gap-1">
                        <label
                            for="workspace-restriction"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Visibility
                        </label>
                        <select
                            id="workspace-restriction"
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
                            for="workspace-list-per-page"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Per page
                        </label>
                        <select
                            id="workspace-list-per-page"
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
                                    >Workspace {{ sortLabel('name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('company_name')"
                                    >Company
                                    {{ sortLabel('company_name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('creator_name')"
                                    >Created By
                                    {{ sortLabel('creator_name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('boards_count')"
                                    >Boards
                                    {{ sortLabel('boards_count') }}</Link
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
                                v-if="can.restrict_workspaces"
                                class="px-5 py-3 font-semibold"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="workspace in workspaces.data"
                            :key="workspace.id"
                            class="border-b border-border last:border-0"
                            :class="
                                workspace.is_restricted
                                    ? 'riraa-restricted-row'
                                    : ''
                            "
                        >
                            <td class="px-5 py-4 font-medium">
                                <Link
                                    :href="
                                        route('admin.boards.index', {
                                            workspace_id: workspace.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ workspace.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
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
                                <span v-else>-</span>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    v-if="workspace.creator"
                                    :href="
                                        route(
                                            'admin.users.show',
                                            workspace.creator.id,
                                        )
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ workspace.creator.name }}
                                </Link>
                                <span v-else>-</span>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    :href="
                                        route('admin.boards.index', {
                                            workspace_id: workspace.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ workspace.boards_count }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    :href="
                                        route('admin.cards.index', {
                                            workspace_id: workspace.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ workspace.tickets_count }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ workspace.users_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(workspace.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(workspace.updated_at) }}
                            </td>
                            <td
                                v-if="can.restrict_workspaces"
                                class="px-5 py-4"
                            >
                                <RestrictionButton
                                    :restricted="workspace.is_restricted"
                                    :processing="
                                        restrictionProcessingId === workspace.id
                                    "
                                    @toggle="updateRestriction(workspace)"
                                />
                            </td>
                        </tr>
                        <tr v-if="workspaces.data.length === 0">
                            <td
                                :colspan="can.restrict_workspaces ? 9 : 8"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No workspaces found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="workspaces" />
            </div>
        </div>
    </section>
</template>
