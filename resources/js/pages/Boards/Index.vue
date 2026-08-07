<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    FolderKanban,
    LayoutDashboard,
    LockKeyhole,
    Plus,
    Search,
    TicketCheck,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import PaginationControls from '@/Components/Admin/PaginationControls.vue';
import { Button } from '@/Components/UI/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/UI/dialog';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { useProjectContext } from '@/composables/useProjectContext';

type BoardRow = {
    id: number;
    name: string;
    description: string | null;
    background: string | null;
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

type Filters = { search: string; per_page: number; workspace_id: number };
type SortState = { field: string; direction: 'asc' | 'desc' };

const props = defineProps<{
    boards: Pagination<BoardRow>;
    filters: Filters;
    sort: SortState;
}>();

const page = usePage();
const { context, selectedWorkspace } = useProjectContext();
const canManage = computed(() =>
    page.props.auth.permissions.includes('boards.manage'),
);
const search = ref(props.filters.search);
const createOpen = ref(
    canManage.value &&
        new URLSearchParams(page.url.split('?')[1] ?? '').get('create') === '1',
);
const backgrounds = [
    '#ede9fe',
    '#dbeafe',
    '#cffafe',
    '#d1fae5',
    '#fef3c7',
    '#ffe4e6',
];
const form = useForm({
    workspace_id:
        props.filters.workspace_id || selectedWorkspace.value?.id || 0,
    name: '',
    description: '',
    background: backgrounds[0],
});

const pageTitle = computed(() => selectedWorkspace.value?.name ?? 'All boards');

const applyFilters = () => {
    router.get(
        route('boards.index'),
        {
            search: search.value,
            workspace_id: selectedWorkspace.value?.id,
            per_page: props.filters.per_page,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const openCreate = () => {
    form.workspace_id = selectedWorkspace.value?.id ?? 0;
    createOpen.value = true;
};

const createBoard = () => {
    form.post(route('boards.store'), {
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head :title="`${pageTitle} boards`" />

    <section class="flex h-full flex-1 flex-col">
        <div
            class="flex flex-wrap items-start justify-between gap-4 border-b border-border px-4 py-6 sm:px-6"
        >
            <div class="min-w-0">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <FolderKanban class="size-4" />
                    <span>{{ context?.company.name }}</span>
                    <span>/</span>
                    <span class="font-medium text-primary">{{
                        pageTitle
                    }}</span>
                </div>
                <h1
                    class="mt-2 flex items-center gap-2 text-2xl font-semibold tracking-normal"
                >
                    <LayoutDashboard class="size-6 text-primary" />
                    Boards
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    Choose a board to plan, prioritize, and move work forward.
                </p>
            </div>
            <Button
                v-if="canManage && selectedWorkspace"
                type="button"
                @click="openCreate"
            >
                <Plus class="size-4" />
                New board
            </Button>
        </div>

        <div
            class="flex flex-wrap items-center gap-3 border-b border-border px-4 py-4 sm:px-6"
        >
            <form
                class="min-w-56 flex-1 sm:max-w-md"
                @submit.prevent="applyFilters"
            >
                <label for="board-search" class="sr-only">Search boards</label>
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                    />
                    <Input
                        id="board-search"
                        v-model="search"
                        class="h-10 bg-card pl-9"
                        placeholder="Search boards"
                        @keydown.enter.prevent="applyFilters"
                    />
                </div>
            </form>
        </div>

        <div class="flex-1 p-4 sm:p-6">
            <div
                v-if="boards.data.length"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <Link
                    v-for="board in boards.data"
                    :key="board.id"
                    :href="route('boards.show', board.id)"
                    class="group flex min-h-52 flex-col overflow-hidden rounded-lg border border-border bg-card shadow-xs transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <div
                        class="flex h-16 items-end border-b border-border px-5 pb-3"
                        :style="{
                            backgroundColor: board.background ?? '#f5f3ff',
                        }"
                    >
                        <span
                            class="rounded-md bg-background/90 px-2 py-1 text-xs font-medium text-foreground shadow-xs"
                        >
                            {{ board.workspace?.name }}
                        </span>
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2
                                    class="truncate text-base font-semibold tracking-normal"
                                >
                                    {{ board.name }}
                                </h2>
                                <p
                                    class="mt-1 line-clamp-2 min-h-10 text-sm text-muted-foreground"
                                >
                                    {{
                                        board.description ||
                                        'A focused board for planning and delivery.'
                                    }}
                                </p>
                            </div>
                            <ArrowRight
                                class="mt-1 size-4 shrink-0 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary"
                            />
                        </div>
                        <div
                            class="mt-auto flex items-center gap-4 border-t border-border pt-4 text-xs text-muted-foreground"
                        >
                            <span class="flex items-center gap-1.5">
                                <TicketCheck class="size-3.5" />
                                {{ board.tickets_count }} tickets
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Users class="size-3.5" />
                                {{ board.users_count }} members
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <div
                v-else
                class="flex min-h-80 flex-col items-center justify-center rounded-lg border border-dashed border-border bg-card px-6 text-center"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <LayoutDashboard class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-semibold">No boards here yet</h2>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        search
                            ? 'Try a different search.'
                            : 'Create a board and start turning plans into tickets.'
                    }}
                </p>
                <Button
                    v-if="canManage && selectedWorkspace && !search"
                    type="button"
                    class="mt-4"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    New board
                </Button>
            </div>

            <div
                v-if="boards.last_page > 1"
                class="mt-6 border-t border-border pt-4"
            >
                <PaginationControls :pagination="boards" />
            </div>
        </div>
    </section>

    <Dialog v-model:open="createOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Create board</DialogTitle>
                <DialogDescription>
                    Start with a practical Backlog, To do, In progress, and Done
                    workflow.
                </DialogDescription>
            </DialogHeader>
            <form class="grid gap-5" @submit.prevent="createBoard">
                <div class="grid gap-2">
                    <Label for="board-workspace">Workspace</Label>
                    <select
                        id="board-workspace"
                        v-model="form.workspace_id"
                        class="riraa-select w-full"
                    >
                        <option
                            v-for="workspace in context?.workspaces ?? []"
                            :key="workspace.id"
                            :value="workspace.id"
                        >
                            {{ workspace.name }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="board-name">Name</Label>
                    <Input
                        id="board-name"
                        v-model="form.name"
                        autofocus
                        placeholder="Product roadmap"
                        :aria-invalid="Boolean(form.errors.name)"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="board-description">Description</Label>
                    <textarea
                        id="board-description"
                        v-model="form.description"
                        rows="3"
                        class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                        placeholder="What outcome is this board tracking?"
                    />
                </div>
                <fieldset class="grid gap-2">
                    <legend class="text-sm font-medium">Board color</legend>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="background in backgrounds"
                            :key="background"
                            type="button"
                            class="size-8 rounded-md border-2 transition focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :class="
                                form.background === background
                                    ? 'border-foreground'
                                    : 'border-border'
                            "
                            :style="{ backgroundColor: background }"
                            :aria-label="`Choose ${background}`"
                            @click="form.background = background"
                        />
                    </div>
                </fieldset>
                <div
                    class="flex items-center gap-2 rounded-md bg-muted px-3 py-2 text-xs text-muted-foreground"
                >
                    <LockKeyhole class="size-3.5" />
                    Visible to company members with board access.
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="createOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="
                            form.processing ||
                            !form.name.trim() ||
                            !form.workspace_id
                        "
                    >
                        <Plus class="size-4" />
                        Create board
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
