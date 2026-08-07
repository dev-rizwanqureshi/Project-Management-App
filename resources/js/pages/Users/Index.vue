<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { MailPlus, Search, Users } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import type { ProjectContext } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
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
    users: Pagination<UserRow>;
    filters: Filters;
    sort: SortState;
}>();

const page = usePage();
const search = ref(props.filters.search);
const perPage = ref(String(props.filters.per_page));
const showInviteForm = ref(false);
const inviteForm = useForm({
    email: '',
    scope: 'company',
    workspace_id: null as number | null,
    board_id: null as number | null,
    role: 'member',
});

const projectContext = computed(
    () => page.props.projectContext as ProjectContext | null,
);
const canInvite = computed(() =>
    page.props.auth.permissions.includes('users.manage'),
);
const workspaces = computed(() => projectContext.value?.workspaces ?? []);
const boards = computed(
    () =>
        workspaces.value.find(
            (workspace) => workspace.id === inviteForm.workspace_id,
        )?.boards ?? [],
);

watch(
    () => inviteForm.scope,
    (scope) => {
        if (scope === 'company') {
            inviteForm.workspace_id = null;
            inviteForm.board_id = null;
            inviteForm.role = 'member';
        }

        if (scope === 'workspace') {
            inviteForm.board_id = null;
            inviteForm.role = 'member';
        }

        if (scope === 'board') {
            inviteForm.role = 'member';
        }
    },
);

watch(
    () => inviteForm.workspace_id,
    () => {
        if (!boards.value.some((board) => board.id === inviteForm.board_id)) {
            inviteForm.board_id = null;
        }
    },
);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(
              new Date(value),
          )
        : '-';

const sortHref = (field: string) =>
    route('users.index', {
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
        route('users.index'),
        {
            search: search.value,
            per_page: perPage.value,
            sort: props.sort.field,
            direction: props.sort.direction,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const submitInvitation = () => {
    inviteForm.post(route('invitations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            inviteForm.reset();
            inviteForm.scope = 'company';
            showInviteForm.value = false;
        },
    });
};
</script>

<template>
    <Head title="Users" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">Company listing</p>
                <h1
                    class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
                >
                    <Users class="size-6 text-primary" />
                    Users
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    View company users and the work they created.
                </p>
            </div>
            <Button
                v-if="canInvite"
                type="button"
                variant="outline"
                @click="showInviteForm = !showInviteForm"
            >
                <MailPlus class="size-4" />
                {{ showInviteForm ? 'Close invite' : 'Invite user' }}
            </Button>
        </div>

        <form
            v-if="showInviteForm"
            class="grid gap-4 rounded-lg border border-primary/20 bg-primary/5 p-4 shadow-sm sm:grid-cols-2"
            @submit.prevent="submitInvitation"
        >
            <div class="grid gap-2 sm:col-span-2">
                <Label for="invite-email">Email address</Label>
                <Input
                    id="invite-email"
                    v-model="inviteForm.email"
                    type="email"
                    required
                    placeholder="teammate@example.com"
                />
                <InputError :message="inviteForm.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="invite-scope">Invite to</Label>
                <select
                    id="invite-scope"
                    v-model="inviteForm.scope"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="company">Company</option>
                    <option value="workspace">Workspace</option>
                    <option value="board">Board</option>
                </select>
                <InputError :message="inviteForm.errors.scope" />
            </div>

            <div class="grid gap-2">
                <Label for="invite-role">Role</Label>
                <select
                    id="invite-role"
                    v-model="inviteForm.role"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option v-if="inviteForm.scope === 'company'" value="admin">
                        Company admin
                    </option>
                    <option value="member">Member</option>
                    <option value="guest">Viewer</option>
                </select>
                <InputError :message="inviteForm.errors.role" />
            </div>

            <div v-if="inviteForm.scope !== 'company'" class="grid gap-2">
                <Label for="invite-workspace">Workspace</Label>
                <select
                    id="invite-workspace"
                    v-model="inviteForm.workspace_id"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    required
                >
                    <option :value="null">Choose a workspace</option>
                    <option
                        v-for="workspace in workspaces"
                        :key="workspace.id"
                        :value="workspace.id"
                    >
                        {{ workspace.name }}
                    </option>
                </select>
                <InputError :message="inviteForm.errors.workspace_id" />
            </div>

            <div v-if="inviteForm.scope === 'board'" class="grid gap-2">
                <Label for="invite-board">Board</Label>
                <select
                    id="invite-board"
                    v-model="inviteForm.board_id"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    required
                >
                    <option :value="null">Choose a board</option>
                    <option
                        v-for="board in boards"
                        :key="board.id"
                        :value="board.id"
                    >
                        {{ board.name }}
                    </option>
                </select>
                <InputError :message="inviteForm.errors.board_id" />
            </div>

            <div class="flex items-end sm:col-span-2">
                <Button type="submit" :disabled="inviteForm.processing">
                    <MailPlus class="size-4" />
                    {{
                        inviteForm.processing ? 'Sending...' : 'Send invitation'
                    }}
                </Button>
            </div>
        </form>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="users-search"
                        class="text-sm font-medium text-muted-foreground"
                        >Search</label
                    >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="users-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Name, email, role"
                            @keydown.enter.prevent="applyFilters"
                        />
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="grid gap-1">
                        <label
                            for="users-per-page"
                            class="text-sm font-medium text-muted-foreground"
                            >Per page</label
                        >
                        <select
                            id="users-per-page"
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
                                    >Name {{ sortLabel('name') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('email')"
                                    >Email {{ sortLabel('email') }}</a
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <a :href="sortHref('role')"
                                    >Role {{ sortLabel('role') }}</a
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
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-5 py-4 font-medium">
                                {{ user.name }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ user.email }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ user.role }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ user.workspaces_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ user.boards_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ user.tickets_count }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(user.updated_at) }}
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="8"
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
