<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    ChevronDown,
    Circle,
    Copy,
    Ellipsis,
    FileText,
    Filter,
    FolderKanban,
    GripVertical,
    LoaderCircle,
    MessageCircle,
    Paperclip,
    Plus,
    Search,
    Tags,
    Upload,
    UserRound,
    X,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import { route } from 'ziggy-js';
import TicketDetail from '@/Components/TicketDetail.vue';
import { Button } from '@/Components/UI/button';
import { Checkbox } from '@/Components/UI/checkbox';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/UI/dropdown-menu';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/UI/sheet';
import { useInitials } from '@/composables/useInitials';
import type { TicketDetail as TicketDetailType } from '@/types';

type Person = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
};

type BoardCard = {
    id: number;
    title: string;
    position: number;
    description: string | null;
    due_date: string | null;
    is_completed: boolean;
    comments_count: number;
    attachments_count: number;
    labels: { id: number; name: string | null; color: string }[];
    assignees: Person[];
};

type BoardList = {
    id: number;
    name: string;
    position: number;
    cards: BoardCard[];
};

type BoardDetail = {
    id: number;
    name: string;
    description: string | null;
    background: string | null;
    workspace: {
        id: number;
        name: string;
        slug: string;
        color: string | null;
    };
    members: Person[];
    labels: { id: number; name: string | null; color: string }[];
    lists: BoardList[];
    users_count: number;
};

const props = withDefaults(
    defineProps<{ board: BoardDetail; ticket?: TicketDetailType | null }>(),
    { ticket: null },
);
const page = usePage();
const { getInitials } = useInitials();
const canManageCards = computed(() =>
    page.props.auth.permissions.includes('cards.manage'),
);
const currentUserId = computed(() => page.props.auth.user?.id ?? 0);
const search = ref('');
const showMine = ref(false);
const ticketOpen = ref(false);
const createAnother = ref(false);
const draggingCardId = ref<number | null>(null);
const dragOverListId = ref<number | null>(null);
const dragOverCardId = ref<number | null>(null);
const copiedCardId = ref<number | null>(null);
const drawerOpen = computed({
    get: () => Boolean(props.ticket),
    set: (open) => {
        if (!open) {
            closeTicket();
        }
    },
});

const ticketForm = useForm({
    list_id: props.board.lists[0]?.id ?? 0,
    title: '',
    description: '',
    due_date: '',
    attachments: [] as File[],
    assignee_ids: currentUserId.value
        ? [currentUserId.value]
        : ([] as number[]),
    label_ids: [] as number[],
});

type PendingAttachment = {
    id: string;
    file: File;
    previewUrl: string | null;
};

const pendingAttachments = ref<PendingAttachment[]>([]);
const isDraggingFiles = ref(false);
const attachmentInput = ref<HTMLInputElement | null>(null);
const maxAttachments = 5;
const maxAttachmentSize = 10 * 1024 * 1024;

const formatAttachmentSize = (bytes: number) => {
    if (bytes < 1024 * 1024) {
        return `${Math.ceil(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const syncAttachments = () => {
    ticketForm.attachments = pendingAttachments.value.map(
        (attachment) => attachment.file,
    );
};

const addAttachments = (files: File[]) => {
    const availableSlots = maxAttachments - pendingAttachments.value.length;
    const filesToAdd = files
        .filter(
            (file) =>
                file.size <= maxAttachmentSize &&
                !pendingAttachments.value.some(
                    (attachment) =>
                        attachment.file.name === file.name &&
                        attachment.file.size === file.size &&
                        attachment.file.lastModified === file.lastModified,
                ),
        )
        .slice(0, Math.max(availableSlots, 0));

    pendingAttachments.value.push(
        ...filesToAdd.map((file) => ({
            id: `${file.name}-${file.size}-${file.lastModified}-${Math.random()}`,
            file,
            previewUrl: file.type.startsWith('image/')
                ? URL.createObjectURL(file)
                : null,
        })),
    );
    syncAttachments();
};

const handleFileInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    addAttachments(Array.from(input.files ?? []));
    input.value = '';
};

const openAttachmentPicker = () => attachmentInput.value?.click();

const handleDescriptionPaste = (event: ClipboardEvent) => {
    const imageFiles = Array.from(event.clipboardData?.items ?? [])
        .filter(
            (item) => item.kind === 'file' && item.type.startsWith('image/'),
        )
        .map((item) => item.getAsFile())
        .filter((file): file is File => Boolean(file));

    if (imageFiles.length) {
        event.preventDefault();
        addAttachments(imageFiles);
    }
};

const handleAttachmentDrop = (event: DragEvent) => {
    isDraggingFiles.value = false;
    addAttachments(Array.from(event.dataTransfer?.files ?? []));
};

const removeAttachment = (id: string) => {
    const attachment = pendingAttachments.value.find(
        (pendingAttachment) => pendingAttachment.id === id,
    );

    if (attachment?.previewUrl) {
        URL.revokeObjectURL(attachment.previewUrl);
    }

    pendingAttachments.value = pendingAttachments.value.filter(
        (pendingAttachment) => pendingAttachment.id !== id,
    );
    syncAttachments();
};

const clearAttachments = () => {
    pendingAttachments.value.forEach((attachment) => {
        if (attachment.previewUrl) {
            URL.revokeObjectURL(attachment.previewUrl);
        }
    });
    pendingAttachments.value = [];
    syncAttachments();
};

const selectedAssignees = computed(() =>
    props.board.members.filter((member) =>
        ticketForm.assignee_ids.includes(member.id),
    ),
);
const selectedLabels = computed(() =>
    props.board.labels.filter((label) =>
        ticketForm.label_ids.includes(label.id),
    ),
);

const filteredLists = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();

    return props.board.lists.map((list) => ({
        ...list,
        cards: list.cards.filter((card) => {
            const matchesSearch =
                !term ||
                card.title.toLocaleLowerCase().includes(term) ||
                card.description?.toLocaleLowerCase().includes(term) ||
                card.labels.some((label) =>
                    label.name?.toLocaleLowerCase().includes(term),
                );
            const matchesMine =
                !showMine.value ||
                card.assignees.some(
                    (assignee) => assignee.id === currentUserId.value,
                );

            return Boolean(matchesSearch && matchesMine);
        }),
    }));
});

const listColor = (name: string, position: number) => {
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

    if (normalized.includes('to do') || normalized.includes('todo')) {
        return '#3b82f6';
    }

    return ['#8b5cf6', '#3b82f6', '#f59e0b', '#22c55e'][position % 4];
};

const ticketKey = (id: number) => `RIR-${String(id).padStart(3, '0')}`;

const formatDueDate = (value: string | null) => {
    if (!value) {
        return null;
    }

    return new Intl.DateTimeFormat('en', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
};

const isOverdue = (value: string | null) =>
    Boolean(
        value && new Date(value).getTime() < new Date().setHours(0, 0, 0, 0),
    );

const openTicketDialog = (listId: number) => {
    ticketForm.clearErrors();
    clearAttachments();
    ticketForm.list_id = listId;
    ticketForm.title = '';
    ticketForm.description = '';
    ticketForm.due_date = '';
    ticketForm.assignee_ids = [];
    ticketForm.label_ids = [];
    createAnother.value = false;
    ticketOpen.value = true;
};

const closeCreateTicket = () => {
    ticketOpen.value = false;
    clearAttachments();
};

const createTicket = () => {
    ticketForm.post(route('boards.cards.store', props.board.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            if (createAnother.value) {
                ticketForm.title = '';
                ticketForm.description = '';
                ticketForm.due_date = '';
                clearAttachments();
                ticketForm.label_ids = [];
                void nextTick(() => {
                    document.getElementById('ticket-title')?.focus();
                });

                return;
            }

            ticketOpen.value = false;
            clearAttachments();
            ticketForm.reset();
        },
    });
};

const moveCard = (cardId: number, listId: number, position?: number) => {
    router.patch(
        route('boards.cards.move', { board: props.board.id, card: cardId }),
        { list_id: listId, ...(position ? { position } : {}) },
        { preserveScroll: true },
    );
};

const openTicket = (cardId: number) => {
    router.visit(
        route('boards.cards.show', {
            board: props.board.id,
            card: cardId,
        }),
        { preserveScroll: true },
    );
};

const copyCardLink = async (cardId: number) => {
    await navigator.clipboard.writeText(
        route('boards.cards.show', {
            board: props.board.id,
            card: cardId,
        }),
    );
    copiedCardId.value = cardId;
    window.setTimeout(() => {
        if (copiedCardId.value === cardId) {
            copiedCardId.value = null;
        }
    }, 1600);
};

const closeTicket = () => {
    router.visit(route('boards.show', props.board.id), {
        preserveScroll: true,
    });
};

const dropCard = (listId: number, targetCardId?: number) => {
    if (!draggingCardId.value) {
        return;
    }

    const targetCard = props.board.lists
        .flatMap((list) => list.cards)
        .find((card) => card.id === targetCardId);

    moveCard(draggingCardId.value, listId, targetCard?.position);
    draggingCardId.value = null;
    dragOverListId.value = null;
    dragOverCardId.value = null;
};

const toggleAssignee = (personId: number) => {
    ticketForm.assignee_ids = ticketForm.assignee_ids.includes(personId)
        ? ticketForm.assignee_ids.filter((id) => id !== personId)
        : [...ticketForm.assignee_ids, personId];
};

const toggleLabel = (labelId: number) => {
    ticketForm.label_ids = ticketForm.label_ids.includes(labelId)
        ? ticketForm.label_ids.filter((id) => id !== labelId)
        : [...ticketForm.label_ids, labelId];
};
</script>

<template>
    <Head :title="board.name" />

    <section class="flex min-h-0 flex-1 flex-col">
        <header
            class="relative flex flex-wrap items-start justify-between gap-4 border-b border-border bg-background px-4 py-5 sm:px-6"
        >
            <span
                class="absolute inset-x-0 top-0 h-1"
                :style="{ backgroundColor: board.background ?? '#7c3aed' }"
            />
            <div class="min-w-0">
                <div
                    class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    <span class="font-medium text-primary">{{
                        $page.props.projectContext?.company.name
                    }}</span>
                    <span>/</span>
                    <span>{{ board.workspace.name }}</span>
                </div>
                <h1
                    class="mt-2 flex items-center gap-2 text-2xl font-semibold tracking-normal text-foreground"
                >
                    <FolderKanban class="size-6 text-primary" />
                    {{ board.name }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    {{
                        board.description ||
                        'Plan, prioritize, and deliver work with your team.'
                    }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden items-center -space-x-2 sm:flex">
                    <div
                        v-for="member in board.members.slice(0, 4)"
                        :key="member.id"
                        class="flex size-8 items-center justify-center overflow-hidden rounded-full border-2 border-background bg-muted text-[10px] font-semibold text-foreground"
                        :title="member.name"
                    >
                        <img
                            v-if="member.avatar"
                            :src="member.avatar"
                            :alt="member.name"
                            class="size-full object-cover"
                        />
                        <span v-else>{{ getInitials(member.name) }}</span>
                    </div>
                    <div
                        v-if="board.members.length > 4"
                        class="flex size-8 items-center justify-center rounded-full border-2 border-background bg-muted text-[10px] font-semibold"
                    >
                        +{{ board.members.length - 4 }}
                    </div>
                </div>
                <Button
                    v-if="canManageCards"
                    type="button"
                    @click="openTicketDialog(board.lists[0]?.id ?? 0)"
                >
                    <Plus class="size-4" />
                    Add ticket
                </Button>
            </div>
        </header>

        <div
            class="flex flex-wrap items-center gap-3 border-b border-border bg-background px-4 py-3 sm:px-6"
        >
            <div class="relative min-w-56 flex-1 sm:max-w-sm">
                <Search
                    class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="h-10 pl-9"
                    placeholder="Search tickets"
                />
            </div>
            <Button
                type="button"
                :variant="showMine ? 'secondary' : 'outline'"
                class="h-10"
                @click="showMine = !showMine"
            >
                <Filter class="size-4" />
                My tickets
                <Check v-if="showMine" class="size-3.5" />
            </Button>
            <div
                class="ml-auto hidden items-center gap-2 text-xs text-muted-foreground md:flex"
            >
                <Circle class="size-2.5 fill-emerald-500 text-emerald-500" />
                {{
                    board.lists.reduce(
                        (total, list) => total + list.cards.length,
                        0,
                    )
                }}
                active tickets
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-x-auto bg-muted/25 p-4 sm:p-5">
            <div
                class="grid min-w-max auto-cols-[280px] grid-flow-col items-start gap-4 xl:auto-cols-[310px]"
            >
                <section
                    v-for="(list, index) in filteredLists"
                    :key="list.id"
                    class="flex max-h-[calc(100vh-15rem)] min-h-60 flex-col rounded-lg border border-border bg-background/75"
                    :class="
                        dragOverListId === list.id
                            ? 'ring-2 ring-primary/40'
                            : ''
                    "
                    @dragover.prevent="
                        dragOverListId = list.id;
                        dragOverCardId = null;
                    "
                    @dragleave="
                        dragOverListId = null;
                        dragOverCardId = null;
                    "
                    @drop.prevent="dropCard(list.id)"
                >
                    <div class="flex h-12 shrink-0 items-center gap-2 px-3">
                        <span
                            class="size-2.5 rounded-full"
                            :style="{
                                backgroundColor: listColor(list.name, index),
                            }"
                        />
                        <h2 class="truncate text-sm font-semibold">
                            {{ list.name }}
                        </h2>
                        <span
                            class="rounded-md border border-border bg-background px-1.5 py-0.5 text-xs text-muted-foreground"
                        >
                            {{ list.cards.length }}
                        </span>
                        <Button
                            v-if="canManageCards"
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="ml-auto size-7"
                            title="Add ticket"
                            @click="openTicketDialog(list.id)"
                        >
                            <Plus class="size-4" />
                            <span class="sr-only">Add ticket</span>
                        </Button>
                    </div>

                    <div
                        class="min-h-20 flex-1 space-y-2 overflow-y-auto px-2 pb-2"
                    >
                        <article
                            v-for="card in list.cards"
                            :key="card.id"
                            :draggable="canManageCards"
                            role="button"
                            tabindex="0"
                            class="group rounded-md border border-border bg-card p-3 shadow-xs transition hover:border-primary/30 hover:shadow-sm"
                            :class="
                                draggingCardId === card.id
                                    ? 'opacity-50'
                                    : dragOverCardId === card.id
                                      ? 'border-primary ring-2 ring-primary/30'
                                      : ''
                            "
                            @dragstart="draggingCardId = card.id"
                            @dragover.prevent.stop="
                                dragOverListId = list.id;
                                dragOverCardId = card.id;
                            "
                            @drop.prevent.stop="dropCard(list.id, card.id)"
                            @dragend="
                                draggingCardId = null;
                                dragOverListId = null;
                                dragOverCardId = null;
                            "
                            @click="openTicket(card.id)"
                            @keydown.enter.prevent="openTicket(card.id)"
                            @keydown.space.prevent="openTicket(card.id)"
                        >
                            <div
                                v-if="card.labels.length"
                                class="mb-2 flex flex-wrap gap-1"
                            >
                                <span
                                    v-for="label in card.labels"
                                    :key="label.id"
                                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold text-white"
                                    :style="{ backgroundColor: label.color }"
                                >
                                    {{ label.name || 'Label' }}
                                </span>
                            </div>
                            <div class="flex items-start gap-2">
                                <GripVertical
                                    v-if="canManageCards"
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground/60 opacity-0 transition group-hover:opacity-100"
                                />
                                <h3
                                    class="min-w-0 flex-1 text-left text-sm leading-5 font-medium"
                                >
                                    {{ card.title }}
                                </h3>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="-mt-1 -mr-1 size-7 shrink-0"
                                    :title="
                                        copiedCardId === card.id
                                            ? 'Copied'
                                            : 'Copy ticket link'
                                    "
                                    @click.stop="copyCardLink(card.id)"
                                >
                                    <Check
                                        v-if="copiedCardId === card.id"
                                        class="size-4 text-emerald-600"
                                    />
                                    <Copy v-else class="size-4" />
                                    <span class="sr-only">
                                        {{
                                            copiedCardId === card.id
                                                ? 'Copied'
                                                : 'Copy ticket link'
                                        }}
                                    </span>
                                </Button>
                                <DropdownMenu v-if="canManageCards">
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="-mt-1 -mr-1 size-7 shrink-0"
                                            @click.stop
                                        >
                                            <Ellipsis class="size-4" />
                                            <span class="sr-only"
                                                >Ticket actions</span
                                            >
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        class="w-48"
                                    >
                                        <DropdownMenuLabel
                                            >Move to</DropdownMenuLabel
                                        >
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            v-for="target in board.lists"
                                            :key="target.id"
                                            :disabled="target.id === list.id"
                                            @select="
                                                moveCard(card.id, target.id)
                                            "
                                        >
                                            <span
                                                class="size-2 rounded-full"
                                                :style="{
                                                    backgroundColor: listColor(
                                                        target.name,
                                                        target.position,
                                                    ),
                                                }"
                                            />
                                            {{ target.name }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <p
                                v-if="card.description"
                                class="mt-1 line-clamp-2 text-xs leading-5 text-muted-foreground"
                            >
                                {{ card.description }}
                            </p>
                            <div
                                class="mt-3 flex items-center gap-3 text-xs text-muted-foreground"
                            >
                                <span class="font-medium">{{
                                    ticketKey(card.id)
                                }}</span>
                                <span
                                    v-if="card.due_date"
                                    class="flex items-center gap-1"
                                    :class="
                                        isOverdue(card.due_date)
                                            ? 'text-destructive'
                                            : ''
                                    "
                                >
                                    <CalendarDays class="size-3.5" />
                                    {{ formatDueDate(card.due_date) }}
                                </span>
                                <span
                                    v-if="card.comments_count"
                                    class="flex items-center gap-1"
                                >
                                    <MessageCircle class="size-3.5" />
                                    {{ card.comments_count }}
                                </span>
                                <span
                                    v-if="card.attachments_count"
                                    class="flex items-center gap-1"
                                >
                                    <Paperclip class="size-3.5" />
                                    {{ card.attachments_count }}
                                </span>
                                <div
                                    class="ml-auto flex items-center -space-x-1.5"
                                >
                                    <span
                                        v-for="assignee in card.assignees.slice(
                                            0,
                                            3,
                                        )"
                                        :key="assignee.id"
                                        class="flex size-6 items-center justify-center overflow-hidden rounded-full border-2 border-card bg-muted text-[8px] font-semibold"
                                        :title="assignee.name"
                                    >
                                        <img
                                            v-if="assignee.avatar"
                                            :src="assignee.avatar"
                                            :alt="assignee.name"
                                            class="size-full object-cover"
                                        />
                                        <span v-else>{{
                                            getInitials(assignee.name)
                                        }}</span>
                                    </span>
                                </div>
                            </div>
                        </article>

                        <button
                            v-if="canManageCards"
                            type="button"
                            class="flex h-9 w-full items-center justify-center gap-2 rounded-md text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-primary"
                            @click="openTicketDialog(list.id)"
                        >
                            <Plus class="size-4" />
                            Add ticket
                        </button>

                        <div
                            v-if="!list.cards.length && !canManageCards"
                            class="flex h-24 items-center justify-center rounded-md border border-dashed border-border text-xs text-muted-foreground"
                        >
                            No tickets
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <Sheet v-model:open="drawerOpen">
        <SheetContent
            side="right"
            :show-close-button="false"
            class="w-full gap-0 p-0 sm:max-w-[42rem] lg:max-w-[44rem]"
        >
            <SheetHeader class="sr-only">
                <SheetTitle>{{ ticket?.title }}</SheetTitle>
                <SheetDescription>Ticket details</SheetDescription>
            </SheetHeader>
            <TicketDetail
                v-if="ticket"
                :ticket="ticket"
                :board="board"
                :lists="board.lists"
                mode="drawer"
                @close="closeTicket"
            />
        </SheetContent>
    </Sheet>

    <Sheet v-model:open="ticketOpen">
        <SheetContent
            side="right"
            :show-close-button="false"
            class="w-full gap-0 p-0 sm:max-w-[34rem]"
        >
            <SheetHeader class="sr-only">
                <SheetTitle>Create new ticket</SheetTitle>
                <SheetDescription>
                    Add a ticket to {{ board.name }}.
                </SheetDescription>
            </SheetHeader>

            <form
                class="flex min-h-0 flex-1 flex-col bg-background"
                @submit.prevent="createTicket"
            >
                <header
                    class="shrink-0 border-b border-border px-5 py-5 sm:px-6"
                >
                    <div class="flex items-center gap-3">
                        <span class="truncate text-sm font-medium text-primary">
                            {{ board.name }}
                        </span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="ml-auto size-8"
                            title="Close"
                            @click="closeCreateTicket"
                        >
                            <X class="size-4" />
                            <span class="sr-only">Close</span>
                        </Button>
                    </div>
                    <h2
                        class="mt-3 text-2xl font-semibold tracking-normal text-foreground"
                    >
                        Create new ticket
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Add the essential details now. You can complete the rest
                        later.
                    </p>
                </header>

                <div
                    class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6"
                >
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label for="ticket-title">Title</Label>
                            <span class="text-xs text-muted-foreground">
                                {{ ticketForm.title.length }} / 180
                            </span>
                        </div>
                        <Input
                            id="ticket-title"
                            v-model="ticketForm.title"
                            autofocus
                            maxlength="180"
                            class="h-11"
                            placeholder="What needs to be done?"
                            :aria-invalid="Boolean(ticketForm.errors.title)"
                        />
                        <p
                            v-if="ticketForm.errors.title"
                            class="text-xs text-destructive"
                        >
                            {{ ticketForm.errors.title }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="ticket-description">Description</Label>
                        <div
                            class="overflow-hidden rounded-md border border-input bg-background focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/50"
                            :class="
                                isDraggingFiles
                                    ? 'border-primary bg-primary/5 ring-3 ring-primary/20'
                                    : ''
                            "
                            @dragenter.prevent="isDraggingFiles = true"
                            @dragover.prevent="isDraggingFiles = true"
                            @dragleave.prevent="isDraggingFiles = false"
                            @drop.prevent="handleAttachmentDrop"
                        >
                            <textarea
                                id="ticket-description"
                                v-model="ticketForm.description"
                                rows="6"
                                maxlength="2000"
                                class="block w-full resize-none border-0 bg-transparent px-3 py-3 text-sm leading-6 outline-none placeholder:text-muted-foreground focus-visible:ring-0"
                                placeholder="Add details, requirements, or context..."
                                @paste="handleDescriptionPaste"
                            />
                            <div
                                class="flex flex-wrap items-center justify-between gap-2 border-t border-border bg-muted/20 px-3 py-2"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <Upload class="size-3.5" />
                                    Drop files here or paste an image
                                </span>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded px-2 py-1 text-xs font-medium text-primary transition-colors hover:bg-primary/10"
                                    @click="openAttachmentPicker"
                                >
                                    <Paperclip class="size-3.5" />
                                    Add file
                                </button>
                            </div>
                        </div>
                        <input
                            ref="attachmentInput"
                            type="file"
                            class="hidden"
                            multiple
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                            @change="handleFileInput"
                        />
                        <div
                            v-if="pendingAttachments.length"
                            class="grid gap-2"
                        >
                            <div
                                v-for="attachment in pendingAttachments"
                                :key="attachment.id"
                                class="flex items-center gap-2 rounded-md border border-border bg-muted/25 px-2.5 py-2"
                            >
                                <img
                                    v-if="attachment.previewUrl"
                                    :src="attachment.previewUrl"
                                    :alt="attachment.file.name"
                                    class="size-9 rounded object-cover"
                                />
                                <FileText
                                    v-else
                                    class="size-4 shrink-0 text-primary"
                                />
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-xs font-medium"
                                    >
                                        {{ attachment.file.name }}
                                    </span>
                                    <span
                                        class="block text-[11px] text-muted-foreground"
                                    >
                                        {{
                                            formatAttachmentSize(
                                                attachment.file.size,
                                            )
                                        }}
                                    </span>
                                </span>
                                <button
                                    type="button"
                                    class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                                    :aria-label="`Remove ${attachment.file.name}`"
                                    @click="removeAttachment(attachment.id)"
                                >
                                    <X class="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Up to {{ maxAttachments }} files, 10 MB each.
                        </p>
                        <p
                            v-if="
                                ticketForm.errors.attachments ||
                                ticketForm.errors['attachments.0']
                            "
                            class="text-xs text-destructive"
                        >
                            {{
                                ticketForm.errors.attachments ||
                                ticketForm.errors['attachments.0']
                            }}
                        </p>
                        <p
                            v-if="ticketForm.errors.description"
                            class="text-xs text-destructive"
                        >
                            {{ ticketForm.errors.description }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="ticket-status">Status</Label>
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute top-3.5 left-3 z-10 size-2 rounded-full"
                                    :style="{
                                        backgroundColor: listColor(
                                            board.lists.find(
                                                (list) =>
                                                    list.id ===
                                                    ticketForm.list_id,
                                            )?.name ?? '',
                                            board.lists.find(
                                                (list) =>
                                                    list.id ===
                                                    ticketForm.list_id,
                                            )?.position ?? 0,
                                        ),
                                    }"
                                />
                                <select
                                    id="ticket-status"
                                    v-model="ticketForm.list_id"
                                    class="riraa-select h-11 w-full pl-8"
                                >
                                    <option
                                        v-for="list in board.lists"
                                        :key="list.id"
                                        :value="list.id"
                                    >
                                        {{ list.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="ticket-due-date">Due date</Label>
                            <Input
                                id="ticket-due-date"
                                v-model="ticketForm.due_date"
                                type="date"
                                class="h-11"
                            />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label>Assignees</Label>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="h-11 justify-start px-3 font-normal"
                                    >
                                        <UserRound
                                            class="size-4 text-muted-foreground"
                                        />
                                        <span
                                            class="min-w-0 flex-1 truncate text-left"
                                        >
                                            {{
                                                selectedAssignees.length
                                                    ? selectedAssignees
                                                          .map(
                                                              (member) =>
                                                                  member.name,
                                                          )
                                                          .join(', ')
                                                    : 'Unassigned'
                                            }}
                                        </span>
                                        <ChevronDown
                                            class="size-4 text-muted-foreground"
                                        />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" class="w-64">
                                    <DropdownMenuLabel
                                        >Choose assignees</DropdownMenuLabel
                                    >
                                    <DropdownMenuSeparator />
                                    <DropdownMenuCheckboxItem
                                        v-for="member in board.members"
                                        :key="member.id"
                                        :model-value="
                                            ticketForm.assignee_ids.includes(
                                                member.id,
                                            )
                                        "
                                        @select.prevent
                                        @update:model-value="
                                            toggleAssignee(member.id)
                                        "
                                    >
                                        <span
                                            class="flex size-6 items-center justify-center rounded-full bg-muted text-[8px] font-semibold"
                                        >
                                            {{ getInitials(member.name) }}
                                        </span>
                                        <span class="truncate">{{
                                            member.name
                                        }}</span>
                                    </DropdownMenuCheckboxItem>
                                    <DropdownMenuItem
                                        v-if="!board.members.length"
                                        disabled
                                    >
                                        No board members
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <div class="grid gap-2">
                            <Label>Labels</Label>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="h-11 justify-start px-3 font-normal"
                                    >
                                        <Tags
                                            class="size-4 text-muted-foreground"
                                        />
                                        <span
                                            class="min-w-0 flex-1 truncate text-left"
                                        >
                                            {{
                                                selectedLabels.length
                                                    ? selectedLabels
                                                          .map(
                                                              (label) =>
                                                                  label.name ||
                                                                  'Label',
                                                          )
                                                          .join(', ')
                                                    : 'Add labels'
                                            }}
                                        </span>
                                        <ChevronDown
                                            class="size-4 text-muted-foreground"
                                        />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" class="w-64">
                                    <DropdownMenuLabel
                                        >Choose labels</DropdownMenuLabel
                                    >
                                    <DropdownMenuSeparator />
                                    <DropdownMenuCheckboxItem
                                        v-for="label in board.labels"
                                        :key="label.id"
                                        :model-value="
                                            ticketForm.label_ids.includes(
                                                label.id,
                                            )
                                        "
                                        @select.prevent
                                        @update:model-value="
                                            toggleLabel(label.id)
                                        "
                                    >
                                        <span
                                            class="size-2.5 rounded-full"
                                            :style="{
                                                backgroundColor: label.color,
                                            }"
                                        />
                                        <span class="truncate">{{
                                            label.name || 'Label'
                                        }}</span>
                                    </DropdownMenuCheckboxItem>
                                    <DropdownMenuItem
                                        v-if="!board.labels.length"
                                        disabled
                                    >
                                        No labels on this board
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <div
                        v-if="selectedLabels.length"
                        class="flex flex-wrap gap-1.5"
                    >
                        <button
                            v-for="label in selectedLabels"
                            :key="label.id"
                            type="button"
                            class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-white"
                            :style="{ backgroundColor: label.color }"
                            :aria-label="`Remove ${label.name || 'label'}`"
                            @click="toggleLabel(label.id)"
                        >
                            {{ label.name || 'Label' }}
                            <X class="size-3" />
                        </button>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-md border border-border bg-muted/35 px-3 py-3 text-sm"
                    >
                        <FolderKanban class="size-4 shrink-0 text-primary" />
                        <span class="min-w-0 flex-1 truncate">
                            {{ board.workspace.name }} / {{ board.name }}
                        </span>
                    </div>
                </div>

                <footer
                    class="grid shrink-0 grid-cols-2 items-center gap-3 border-t border-border bg-card px-5 py-4 sm:flex sm:px-6"
                >
                    <label
                        for="create-another-ticket"
                        class="col-span-2 flex cursor-pointer items-center gap-2 text-sm text-muted-foreground sm:mr-auto"
                    >
                        <Checkbox
                            id="create-another-ticket"
                            :model-value="createAnother"
                            @update:model-value="
                                createAnother = Boolean($event)
                            "
                        />
                        Create another
                    </label>
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full sm:w-auto"
                        @click="closeCreateTicket"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="
                            ticketForm.processing ||
                            !ticketForm.title.trim() ||
                            !ticketForm.list_id
                        "
                    >
                        <LoaderCircle
                            v-if="ticketForm.processing"
                            class="size-4 animate-spin"
                        />
                        <Plus v-else class="size-4" />
                        Create ticket
                    </Button>
                </footer>
            </form>
        </SheetContent>
    </Sheet>
</template>
