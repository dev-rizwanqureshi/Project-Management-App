<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    FolderKanban,
    LayoutDashboard,
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

type WorkspaceRow = {
    id: number;
    name: string;
    description: string | null;
    color: string | null;
    creator: { id: number; name: string; email: string } | null;
    boards_count: number;
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
    workspaces: Pagination<WorkspaceRow>;
    filters: Filters;
    sort: SortState;
}>();

const page = usePage();
const canManage = computed(() =>
    page.props.auth.permissions.includes('workspaces.manage'),
);
const companyName = computed(
    () => page.props.projectContext?.company.name ?? 'Company',
);
const search = ref(props.filters.search);
const createOpen = ref(
    canManage.value &&
        new URLSearchParams(page.url.split('?')[1] ?? '').get('create') === '1',
);
const colors = [
    '#7c3aed',
    '#2563eb',
    '#0891b2',
    '#059669',
    '#d97706',
    '#e11d48',
];
const form = useForm({
    name: '',
    description: '',
    color: colors[0],
});

const applyFilters = () => {
    router.get(
        route('workspaces.index'),
        { search: search.value, per_page: props.filters.per_page },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const createWorkspace = () => {
    form.post(route('workspaces.store'), {
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
        },
    });
};

const workspaceHref = (workspace: WorkspaceRow) =>
    route('boards.index', { workspace_id: workspace.id });

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(
              new Date(value),
          )
        : 'Recently';
</script>

<template>
    <Head title="Workspaces" />

    <section class="flex h-full flex-1 flex-col">
        <div
            class="flex flex-wrap items-start justify-between gap-4 border-b border-border px-4 py-6 sm:px-6"
        >
            <div class="min-w-0">
                <p class="text-sm font-medium text-primary">
                    {{ companyName }}
                </p>
                <h1
                    class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
                >
                    <FolderKanban class="size-6 text-primary" />
                    Workspaces
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    Organize teams and boards around a product, department, or
                    client.
                </p>
            </div>
            <Button v-if="canManage" type="button" @click="createOpen = true">
                <Plus class="size-4" />
                New workspace
            </Button>
        </div>

        <div class="border-b border-border px-4 py-4 sm:px-6">
            <form class="max-w-md" @submit.prevent="applyFilters">
                <label for="workspace-search" class="sr-only"
                    >Search workspaces</label
                >
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                    />
                    <Input
                        id="workspace-search"
                        v-model="search"
                        class="h-10 bg-card pl-9"
                        placeholder="Search workspaces"
                        @keydown.enter.prevent="applyFilters"
                    />
                </div>
            </form>
        </div>

        <div class="flex-1 p-4 sm:p-6">
            <div
                v-if="workspaces.data.length"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <Link
                    v-for="workspace in workspaces.data"
                    :key="workspace.id"
                    :href="workspaceHref(workspace)"
                    class="group relative flex min-h-56 flex-col overflow-hidden rounded-lg border border-border bg-card p-5 shadow-xs transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-1"
                        :style="{
                            backgroundColor: workspace.color ?? '#7c3aed',
                        }"
                    />
                    <div class="flex items-start justify-between gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-md text-white"
                            :style="{
                                backgroundColor: workspace.color ?? '#7c3aed',
                            }"
                        >
                            <FolderKanban class="size-5" />
                        </span>
                        <ArrowRight
                            class="size-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary"
                        />
                    </div>
                    <h2
                        class="mt-4 truncate text-base font-semibold tracking-normal"
                    >
                        {{ workspace.name }}
                    </h2>
                    <p
                        class="mt-1 line-clamp-2 min-h-10 text-sm text-muted-foreground"
                    >
                        {{
                            workspace.description ||
                            'A shared space for focused project work.'
                        }}
                    </p>
                    <div
                        class="mt-auto grid grid-cols-3 gap-2 border-t border-border pt-4"
                    >
                        <div>
                            <div
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <LayoutDashboard class="size-3.5" />
                                <span class="text-xs">Boards</span>
                            </div>
                            <p class="mt-1 text-sm font-semibold">
                                {{ workspace.boards_count }}
                            </p>
                        </div>
                        <div>
                            <div
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <TicketCheck class="size-3.5" />
                                <span class="text-xs">Tickets</span>
                            </div>
                            <p class="mt-1 text-sm font-semibold">
                                {{ workspace.tickets_count }}
                            </p>
                        </div>
                        <div>
                            <div
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <Users class="size-3.5" />
                                <span class="text-xs">People</span>
                            </div>
                            <p class="mt-1 text-sm font-semibold">
                                {{ workspace.users_count }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">
                        Updated {{ formatDate(workspace.updated_at) }}
                    </p>
                </Link>
            </div>

            <div
                v-else
                class="flex min-h-80 flex-col items-center justify-center rounded-lg border border-dashed border-border bg-card px-6 text-center"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <FolderKanban class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-semibold">
                    No workspaces found
                </h2>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        search
                            ? 'Try a different search.'
                            : 'Create a workspace to organize your first set of boards.'
                    }}
                </p>
                <Button
                    v-if="canManage && !search"
                    type="button"
                    class="mt-4"
                    @click="createOpen = true"
                >
                    <Plus class="size-4" />
                    New workspace
                </Button>
            </div>

            <div
                v-if="workspaces.last_page > 1"
                class="mt-6 border-t border-border pt-4"
            >
                <PaginationControls :pagination="workspaces" />
            </div>
        </div>
    </section>

    <Dialog v-model:open="createOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Create workspace</DialogTitle>
                <DialogDescription>
                    Give this team area a clear name and a recognizable color.
                </DialogDescription>
            </DialogHeader>
            <form class="grid gap-5" @submit.prevent="createWorkspace">
                <div class="grid gap-2">
                    <Label for="workspace-name">Name</Label>
                    <Input
                        id="workspace-name"
                        v-model="form.name"
                        autofocus
                        placeholder="Product development"
                        :aria-invalid="Boolean(form.errors.name)"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="workspace-description">Description</Label>
                    <textarea
                        id="workspace-description"
                        v-model="form.description"
                        rows="3"
                        class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                        placeholder="What work belongs here?"
                    />
                </div>
                <fieldset class="grid gap-2">
                    <legend class="text-sm font-medium">Color</legend>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="color in colors"
                            :key="color"
                            type="button"
                            class="size-8 rounded-md border-2 transition focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :class="
                                form.color === color
                                    ? 'border-foreground'
                                    : 'border-transparent'
                            "
                            :style="{ backgroundColor: color }"
                            :aria-label="`Choose ${color}`"
                            @click="form.color = color"
                        />
                    </div>
                </fieldset>
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
                        :disabled="form.processing || !form.name.trim()"
                    >
                        <Plus class="size-4" />
                        Create workspace
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
