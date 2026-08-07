<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Building2, Search } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';

type CompanyRow = {
    id: number;
    name: string;
    email: string | null;
    users_count: number;
    workspaces_count: number;
    boards_count: number;
    tickets_count: number;
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
    companies: Pagination<CompanyRow>;
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
    route('companies.index', {
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
        route('companies.index'),
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
    <Head title="Companies" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div>
            <p class="text-sm font-medium text-primary">Company listing</p>
            <h1
                class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
            >
                <Building2 class="size-6 text-primary" />
                Companies
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Current company totals.
            </p>
        </div>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="company-search"
                        class="text-sm font-medium text-muted-foreground"
                        >Search</label
                    >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="company-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Name or email"
                            @keydown.enter.prevent="applyFilters"
                        />
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="grid gap-1">
                        <label
                            for="company-per-page"
                            class="text-sm font-medium text-muted-foreground"
                            >Per page</label
                        >
                        <select
                            id="company-per-page"
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
                                    >Company {{ sortLabel('name') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('email')"
                                    >Email {{ sortLabel('email') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('users_count')"
                                    >Users {{ sortLabel('users_count') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('workspaces_count')"
                                    >Workspaces
                                    {{ sortLabel('workspaces_count') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('boards_count')"
                                    >Boards {{ sortLabel('boards_count') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('tickets_count')"
                                    >Tickets {{ sortLabel('tickets_count') }}</a
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
                            v-for="company in companies.data"
                            :key="company.id"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-5 py-4 font-medium">
                                {{ company.name }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ company.email ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ company.users_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ company.workspaces_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ company.boards_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ company.tickets_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(company.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(company.updated_at) }}
                            </td>
                        </tr>
                        <tr v-if="companies.data.length === 0">
                            <td
                                colspan="8"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No companies found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="companies" />
            </div>
        </div>
    </section>
</template>
