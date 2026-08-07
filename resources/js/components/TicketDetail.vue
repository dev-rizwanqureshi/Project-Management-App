<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    CheckSquare2,
    Clock3,
    Copy,
    FileText,
    History,
    Maximize2,
    Minimize2,
    Send,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';
import { useInitials } from '@/composables/useInitials';
import type {
    TicketBoardContext,
    TicketDetail,
    TicketListSummary,
} from '@/types';

const props = defineProps<{
    ticket: TicketDetail;
    board: TicketBoardContext;
    lists: TicketListSummary[];
    mode: 'drawer' | 'full';
}>();

const emit = defineEmits<{ close: [] }>();
const page = usePage();
const { getInitials } = useInitials();
const activeTab = ref<'comments' | 'history'>('comments');
const copied = ref(false);
const selectedListId = ref(props.ticket.list.id);
const canManageCards = computed(() =>
    page.props.auth.permissions.includes('cards.manage'),
);
const commentForm = useForm({ body: '' });

watch(
    () => props.ticket.list.id,
    (listId) => {
        selectedListId.value = listId;
    },
);

const listColor = (name: string) => {
    const normalized = name.toLocaleLowerCase();

    if (normalized.includes('backlog')) {
        return '#8b5cf6';
    }

    if (normalized.includes('progress') || normalized.includes('doing')) {
        return '#f59e0b';
    }

    if (normalized.includes('done') || normalized.includes('complete')) {
        return '#22c55e';
    }

    return '#3b82f6';
};

const formatDate = (value: string | null, includeTime = false) => {
    if (!value) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        ...(includeTime ? { timeStyle: 'short' as const } : {}),
    }).format(new Date(value));
};

const formatFileSize = (bytes: number | null) => {
    if (!bytes) {
        return 'File';
    }

    if (bytes < 1024 * 1024) {
        return `${Math.ceil(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const checklistCompleted = (
    items: TicketDetail['checklists'][number]['items'],
) => items.filter((item) => item.is_completed).length;

const checklistProgress = (
    items: TicketDetail['checklists'][number]['items'],
) => (items.length ? (checklistCompleted(items) / items.length) * 100 : 0);

const moveTicket = () => {
    router.patch(
        route('boards.cards.move', {
            board: props.board.id,
            card: props.ticket.id,
        }),
        { list_id: selectedListId.value },
        { preserveScroll: true },
    );
};

const submitComment = () => {
    commentForm.post(
        route('boards.cards.comments.store', {
            board: props.board.id,
            card: props.ticket.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        },
    );
};

const copyTicketLink = async () => {
    await navigator.clipboard.writeText(
        route('boards.cards.show', {
            board: props.board.id,
            card: props.ticket.id,
        }),
    );
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 1600);
};
</script>

<template>
    <article class="flex min-h-0 flex-1 flex-col bg-background">
        <header class="shrink-0 border-b border-border px-5 py-4 sm:px-6">
            <div
                class="flex items-center gap-2 text-xs text-muted-foreground sm:text-sm"
            >
                <span class="truncate">{{ board.name }}</span>
                <span>/</span>
                <span class="font-medium text-primary">{{ ticket.key }}</span>
                <div class="ml-auto flex items-center gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        class="size-8"
                        :title="copied ? 'Copied' : 'Copy ticket link'"
                        @click="copyTicketLink"
                    >
                        <Check v-if="copied" class="size-4 text-emerald-600" />
                        <Copy v-else class="size-4" />
                        <span class="sr-only">{{
                            copied ? 'Copied' : 'Copy ticket link'
                        }}</span>
                    </Button>
                    <Button
                        v-if="mode === 'drawer'"
                        variant="outline"
                        size="icon"
                        class="size-8"
                        as-child
                    >
                        <Link
                            :href="
                                route('boards.cards.show', {
                                    board: board.id,
                                    card: ticket.id,
                                    fullscreen: 1,
                                })
                            "
                            title="Open full screen"
                        >
                            <Maximize2 class="size-4" />
                            <span class="sr-only">Open full screen</span>
                        </Link>
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        size="icon"
                        class="size-8"
                        as-child
                    >
                        <Link
                            :href="
                                route('boards.cards.show', {
                                    board: board.id,
                                    card: ticket.id,
                                })
                            "
                            title="Return to board view"
                        >
                            <Minimize2 class="size-4" />
                            <span class="sr-only">Return to board view</span>
                        </Link>
                    </Button>
                    <Button
                        v-if="mode === 'drawer'"
                        type="button"
                        variant="outline"
                        size="icon"
                        class="size-8"
                        title="Close ticket"
                        @click="emit('close')"
                    >
                        <X class="size-4" />
                        <span class="sr-only">Close ticket</span>
                    </Button>
                </div>
            </div>

            <div class="mt-4 flex items-start gap-3">
                <component
                    :is="mode === 'full' ? 'h1' : 'h2'"
                    class="min-w-0 flex-1 text-xl font-semibold tracking-normal sm:text-2xl"
                >
                    {{ ticket.title }}
                </component>
                <span
                    v-if="ticket.labels[0]"
                    class="mt-1 rounded px-2 py-1 text-xs font-semibold text-white"
                    :style="{ backgroundColor: ticket.labels[0].color }"
                >
                    {{ ticket.labels[0].name || 'Label' }}
                </span>
            </div>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto">
            <section class="grid border-b border-border sm:grid-cols-2">
                <div
                    class="grid gap-4 border-b border-border p-5 sm:border-r sm:border-b-0 sm:p-6"
                >
                    <div
                        class="grid grid-cols-[5.25rem_1fr] items-center gap-3 text-sm"
                    >
                        <span class="text-muted-foreground">Status</span>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute top-3 left-3 z-10 size-2 rounded-full"
                                :style="{
                                    backgroundColor: listColor(
                                        lists.find(
                                            (list) =>
                                                list.id === selectedListId,
                                        )?.name ?? ticket.list.name,
                                    ),
                                }"
                            />
                            <select
                                v-model="selectedListId"
                                class="riraa-select w-full pl-8"
                                :disabled="!canManageCards"
                                @change="moveTicket"
                            >
                                <option
                                    v-for="list in lists"
                                    :key="list.id"
                                    :value="list.id"
                                >
                                    {{ list.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div
                        class="grid grid-cols-[5.25rem_1fr] items-start gap-3 text-sm"
                    >
                        <span class="pt-1.5 text-muted-foreground"
                            >Assignees</span
                        >
                        <div class="flex min-h-9 flex-wrap items-center gap-2">
                            <span
                                v-for="assignee in ticket.assignees"
                                :key="assignee.id"
                                class="inline-flex items-center gap-2 rounded-md border border-border bg-card px-2 py-1.5"
                            >
                                <span
                                    class="flex size-5 items-center justify-center rounded-full bg-muted text-[8px] font-semibold"
                                >
                                    {{ getInitials(assignee.name) }}
                                </span>
                                {{ assignee.name }}
                            </span>
                            <span
                                v-if="!ticket.assignees.length"
                                class="text-muted-foreground"
                                >Unassigned</span
                            >
                        </div>
                    </div>
                    <div
                        class="grid grid-cols-[5.25rem_1fr] items-center gap-3 text-sm"
                    >
                        <span class="text-muted-foreground">Reporter</span>
                        <span
                            v-if="ticket.creator"
                            class="flex items-center gap-2 font-medium"
                        >
                            <span
                                class="flex size-6 items-center justify-center rounded-full bg-muted text-[8px] font-semibold"
                            >
                                {{ getInitials(ticket.creator.name) }}
                            </span>
                            {{ ticket.creator.name }}
                        </span>
                        <span v-else class="text-muted-foreground"
                            >Unknown</span
                        >
                    </div>
                </div>
                <div class="grid gap-4 p-5 sm:p-6">
                    <div
                        class="grid grid-cols-[5rem_1fr] items-center gap-3 text-sm"
                    >
                        <span class="text-muted-foreground">Due date</span>
                        <span
                            class="flex h-9 items-center gap-2 rounded-md border border-border bg-card px-3 font-medium"
                        >
                            <CalendarDays
                                class="size-4 text-muted-foreground"
                            />
                            {{ formatDate(ticket.due_date) }}
                        </span>
                    </div>
                    <div
                        class="grid grid-cols-[5rem_1fr] items-start gap-3 text-sm"
                    >
                        <span class="pt-1 text-muted-foreground">Labels</span>
                        <div
                            class="flex min-h-8 flex-wrap items-center gap-1.5"
                        >
                            <span
                                v-for="label in ticket.labels"
                                :key="label.id"
                                class="rounded px-2 py-1 text-xs font-semibold text-white"
                                :style="{ backgroundColor: label.color }"
                            >
                                {{ label.name || 'Label' }}
                            </span>
                            <span
                                v-if="!ticket.labels.length"
                                class="text-muted-foreground"
                                >None</span
                            >
                        </div>
                    </div>
                    <div
                        class="grid grid-cols-[5rem_1fr] items-center gap-3 text-sm"
                    >
                        <span class="text-muted-foreground">Created</span>
                        <span
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Clock3 class="size-4" />
                            {{ formatDate(ticket.created_at) }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="border-b border-border p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Description</h3>
                <p
                    class="mt-2 text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
                >
                    {{
                        ticket.description ||
                        'No description has been added yet.'
                    }}
                </p>

                <div
                    v-for="checklist in ticket.checklists"
                    :key="checklist.id"
                    class="mt-5 rounded-md border border-border"
                >
                    <div
                        class="flex items-center gap-3 border-b border-border px-3 py-2.5"
                    >
                        <CheckSquare2 class="size-4 text-primary" />
                        <h4
                            class="min-w-0 flex-1 truncate text-sm font-semibold"
                        >
                            {{ checklist.title }}
                        </h4>
                        <div
                            class="h-1.5 w-24 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full bg-primary"
                                :style="{
                                    width: `${checklistProgress(checklist.items)}%`,
                                }"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ checklistCompleted(checklist.items) }} /
                            {{ checklist.items.length }}
                        </span>
                    </div>
                    <div class="divide-y divide-border">
                        <div
                            v-for="item in checklist.items"
                            :key="item.id"
                            class="flex items-start gap-2.5 px-3 py-2.5 text-sm"
                        >
                            <span
                                class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded border"
                                :class="
                                    item.is_completed
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-input'
                                "
                            >
                                <Check
                                    v-if="item.is_completed"
                                    class="size-3"
                                />
                            </span>
                            <span
                                :class="
                                    item.is_completed
                                        ? 'text-muted-foreground line-through'
                                        : ''
                                "
                            >
                                {{ item.title }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="ticket.attachments.length"
                class="border-b border-border p-5 sm:p-6"
            >
                <h3 class="text-sm font-semibold">Attachments</h3>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <a
                        v-for="attachment in ticket.attachments"
                        :key="attachment.id"
                        :href="attachment.download_url"
                        target="_blank"
                        rel="noreferrer"
                        class="flex items-center gap-3 rounded-md border border-border bg-card p-3"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-md bg-primary/10 text-primary"
                        >
                            <FileText class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{
                                attachment.file_name
                            }}</span>
                            <span class="block text-xs text-muted-foreground">{{
                                formatFileSize(attachment.file_size)
                            }}</span>
                        </span>
                    </a>
                </div>
            </section>

            <section class="p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Activity</h3>
                <div class="mt-3 flex border-b border-border">
                    <button
                        type="button"
                        class="border-b-2 px-1 pb-2 text-sm font-medium"
                        :class="
                            activeTab === 'comments'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground'
                        "
                        @click="activeTab = 'comments'"
                    >
                        Comments
                        {{
                            ticket.comments.length
                                ? `(${ticket.comments.length})`
                                : ''
                        }}
                    </button>
                    <button
                        type="button"
                        class="ml-5 border-b-2 px-1 pb-2 text-sm font-medium"
                        :class="
                            activeTab === 'history'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground'
                        "
                        @click="activeTab = 'history'"
                    >
                        History
                    </button>
                </div>

                <div
                    v-if="activeTab === 'comments'"
                    class="divide-y divide-border"
                >
                    <div
                        v-for="comment in ticket.comments"
                        :key="comment.id"
                        class="flex gap-3 py-4"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-[9px] font-semibold"
                        >
                            {{
                                comment.user
                                    ? getInitials(comment.user.name)
                                    : '?'
                            }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-wrap items-baseline gap-x-2 gap-y-1"
                            >
                                <span class="text-sm font-semibold">{{
                                    comment.user?.name ?? 'Former user'
                                }}</span>
                                <span class="text-xs text-muted-foreground">{{
                                    formatDate(comment.created_at, true)
                                }}</span>
                            </div>
                            <p
                                class="mt-1 text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
                            >
                                {{ comment.body }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="!ticket.comments.length"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No comments yet.
                    </div>
                </div>

                <div v-else class="divide-y divide-border">
                    <div
                        v-for="entry in ticket.activity"
                        :key="entry.id"
                        class="flex gap-3 py-4"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-[9px] font-semibold"
                        >
                            {{
                                entry.user ? getInitials(entry.user.name) : '?'
                            }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 text-sm">
                                <History class="size-4 text-muted-foreground" />
                                <span class="font-medium">{{
                                    entry.user?.name ?? 'Former user'
                                }}</span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ entry.description || entry.action }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ formatDate(entry.created_at, true) }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="!ticket.activity.length"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No history yet.
                    </div>
                </div>
            </section>
        </div>

        <form
            v-if="canManageCards"
            class="flex shrink-0 items-center gap-2 border-t border-border bg-card p-3 sm:p-4"
            @submit.prevent="submitComment"
        >
            <span
                class="hidden size-8 shrink-0 items-center justify-center rounded-full bg-muted text-[9px] font-semibold sm:flex"
            >
                {{
                    page.props.auth.user
                        ? getInitials(page.props.auth.user.name)
                        : '?'
                }}
            </span>
            <Input
                v-model="commentForm.body"
                class="h-10 flex-1 bg-background"
                placeholder="Write a comment..."
                aria-label="Write a comment"
            />
            <Button
                type="submit"
                class="h-10"
                :disabled="commentForm.processing || !commentForm.body.trim()"
            >
                <Send class="size-4" />
                <span class="hidden sm:inline">Send</span>
            </Button>
        </form>
    </article>
</template>
