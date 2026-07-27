<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Search, Users } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import RestrictionButton from '@/Components/Admin/RestrictionButton.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';

type CompanySummary = {
    id: number;
    name: string;
};

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    company: CompanySummary | null;
    workspaces_count: number;
    boards_count: number;
    tickets_count: number;
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
    restriction?: 'active' | 'restricted' | 'all';
};

type SortState = {
    field: string;
    direction: 'asc' | 'desc';
};

type PageAccess = {
    restrict_users: boolean;
};

const props = defineProps<{
    users: Pagination<UserRow>;
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
    restriction: props.can.restrict_users ? restriction.value : undefined,
});

const sortHref = (field: string) =>
    route('admin.users.index', {
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
        route('admin.users.index'),
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

const updateRestriction = (user: UserRow) => {
    restrictionProcessingId.value = user.id;

    router.put(
        route('admin.users.restriction.update', user.id),
        {
            restricted: !user.is_restricted,
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
    <Head title="Registered Users" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">Registered users</p>
                <h1
                    class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
                >
                    <Users class="size-6 text-primary" />
                    Users
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    View registered users and the work they created.
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
                        for="user-search"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Search
                    </label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="user-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Name, email, company, role"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <div v-if="can.restrict_users" class="grid gap-1">
                        <label
                            for="user-restriction"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Visibility
                        </label>
                        <select
                            id="user-restriction"
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
                            for="user-per-page"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Per page
                        </label>
                        <select
                            id="user-per-page"
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
                                    >Name {{ sortLabel('name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('email')"
                                    >Email {{ sortLabel('email') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('company_name')"
                                    >Company
                                    {{ sortLabel('company_name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('role')"
                                    >Role {{ sortLabel('role') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('workspaces_count')"
                                    >Workspaces
                                    {{ sortLabel('workspaces_count') }}</Link
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
                                <Link :href="sortHref('created_at')"
                                    >Created {{ sortLabel('created_at') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('updated_at')"
                                    >Updated {{ sortLabel('updated_at') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b border-border last:border-0"
                            :class="
                                user.is_restricted ? 'riraa-restricted-row' : ''
                            "
                        >
                            <td class="px-5 py-4 font-medium">
                                <Link
                                    :href="route('admin.users.show', user.id)"
                                    class="hover:text-primary"
                                >
                                    {{ user.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ user.email }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    v-if="user.company"
                                    :href="
                                        route('admin.workspaces.index', {
                                            company_id: user.company.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ user.company.name }}
                                </Link>
                                <span v-else>-</span>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ user.role }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    :href="
                                        route('admin.workspaces.index', {
                                            user_id: user.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ user.workspaces_count }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    :href="
                                        route('admin.boards.index', {
                                            user_id: user.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ user.boards_count }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <Link
                                    :href="
                                        route('admin.cards.index', {
                                            user_id: user.id,
                                        })
                                    "
                                    class="cursor-pointer hover:text-primary"
                                >
                                    {{ user.tickets_count }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(user.updated_at) }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.users.show',
                                                    user.id,
                                                )
                                            "
                                        >
                                            <Eye class="size-4" />
                                            View
                                        </Link>
                                    </Button>
                                    <RestrictionButton
                                        v-if="can.restrict_users"
                                        :restricted="user.is_restricted"
                                        :processing="
                                            restrictionProcessingId === user.id
                                        "
                                        @toggle="updateRestriction(user)"
                                    />
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="10"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="users" />
            </div>
        </div>
    </section>
</template>
