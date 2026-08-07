<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Circle,
    FolderKanban,
    LayoutDashboard,
    Plus,
} from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/UI/dropdown-menu';
import {
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/Components/UI/sidebar';
import { useProjectContext } from '@/composables/useProjectContext';

const page = usePage();
const { context, selectedWorkspace, selectedBoard, selectWorkspace } =
    useProjectContext();
const canManageWorkspaces = computed(() =>
    page.props.auth.permissions.includes('workspaces.manage'),
);
const canViewBoards = computed(() =>
    page.props.auth.permissions.includes('boards.view'),
);
const canManageBoards = computed(() =>
    page.props.auth.permissions.includes('boards.manage'),
);
</script>

<template>
    <SidebarGroup v-if="context" class="px-2 pt-1">
        <SidebarGroupLabel>Workspace</SidebarGroupLabel>
        <SidebarGroupAction
            v-if="canManageWorkspaces"
            as-child
            title="Create workspace"
        >
            <Link :href="route('workspaces.index', { create: 1 })">
                <Plus />
                <span class="sr-only">Create workspace</span>
            </Link>
        </SidebarGroupAction>

        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <SidebarMenuButton
                            size="lg"
                            class="border border-sidebar-border bg-background/70 shadow-xs data-[state=open]:bg-sidebar-accent"
                            :tooltip="
                                selectedWorkspace?.name ?? 'Choose workspace'
                            "
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-md text-white"
                                :style="{
                                    backgroundColor:
                                        selectedWorkspace?.color ?? '#7c3aed',
                                }"
                            >
                                <FolderKanban class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1 text-left">
                                <span
                                    class="block truncate text-sm font-semibold"
                                >
                                    {{
                                        selectedWorkspace?.name ??
                                        'No workspace'
                                    }}
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{ selectedWorkspace?.boards_count ?? 0 }}
                                    boards
                                </span>
                            </span>
                            <ChevronDown class="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="w-72"
                        align="start"
                        :side-offset="6"
                    >
                        <DropdownMenuLabel>Choose workspace</DropdownMenuLabel>
                        <DropdownMenuItem
                            v-for="workspace in context.workspaces"
                            :key="workspace.id"
                            class="py-2"
                            @select="selectWorkspace(workspace.id)"
                        >
                            <span
                                class="size-2.5 rounded-sm"
                                :style="{
                                    backgroundColor:
                                        workspace.color ?? '#7c3aed',
                                }"
                            />
                            <span class="min-w-0 flex-1 truncate">{{
                                workspace.name
                            }}</span>
                            <span class="text-xs text-muted-foreground">
                                {{ workspace.boards_count }}
                            </span>
                            <Check
                                v-if="selectedWorkspace?.id === workspace.id"
                                class="size-4 text-primary"
                            />
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child>
                            <Link
                                :href="
                                    route('workspaces.index', {
                                        ...(canManageWorkspaces
                                            ? { create: 1 }
                                            : {}),
                                    })
                                "
                            >
                                <Plus
                                    v-if="canManageWorkspaces"
                                    class="size-4"
                                />
                                <LayoutDashboard v-else class="size-4" />
                                {{
                                    canManageWorkspaces
                                        ? 'Create workspace'
                                        : 'View workspaces'
                                }}
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>

            <SidebarMenuItem v-if="canViewBoards && selectedWorkspace">
                <SidebarMenuButton
                    as-child
                    :is-active="$page.component === 'Boards/Index'"
                    tooltip="All boards"
                >
                    <Link
                        :href="
                            route('boards.index', {
                                workspace_id: selectedWorkspace.id,
                            })
                        "
                    >
                        <LayoutDashboard />
                        <span>All boards</span>
                    </Link>
                </SidebarMenuButton>
                <SidebarMenuAction
                    v-if="canManageBoards"
                    as-child
                    show-on-hover
                    title="Create board"
                >
                    <Link
                        :href="
                            route('boards.index', {
                                workspace_id: selectedWorkspace.id,
                                create: 1,
                            })
                        "
                    >
                        <Plus />
                        <span class="sr-only">Create board</span>
                    </Link>
                </SidebarMenuAction>
                <SidebarMenuSub v-if="selectedWorkspace.boards.length">
                    <SidebarMenuSubItem
                        v-for="board in selectedWorkspace.boards"
                        :key="board.id"
                    >
                        <SidebarMenuSubButton
                            as-child
                            :is-active="selectedBoard?.id === board.id"
                        >
                            <Link :href="route('boards.show', board.id)">
                                <Circle
                                    class="size-2.5 fill-current"
                                    :style="{
                                        color: board.background ?? '#7c3aed',
                                    }"
                                />
                                <span>{{ board.name }}</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                </SidebarMenuSub>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
