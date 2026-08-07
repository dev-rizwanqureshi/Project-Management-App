<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, CircleHelp, Plus, Search } from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import { Button } from '@/Components/UI/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/UI/dropdown-menu';
import { SidebarTrigger } from '@/Components/UI/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const canManageWorkspaces = computed(() =>
    page.props.auth.permissions.includes('workspaces.manage'),
);
const canManageBoards = computed(() =>
    page.props.auth.permissions.includes('boards.manage'),
);
const createItems = computed(() => [
    ...(canManageWorkspaces.value
        ? [{ label: 'New workspace', href: route('workspaces.index', { create: 1 }) }]
        : []),
    ...(canManageBoards.value
        ? [{ label: 'New project board', href: route('boards.index', { create: 1 }) }]
        : []),
]);
</script>

<template>
    <header class="riraa-app-header flex h-[68px] shrink-0 items-center gap-3 border-b border-[#e7e8eb] bg-white px-4 transition-[width,height] ease-linear sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger class="size-9 rounded-lg text-[#6b7078] hover:bg-[#f3f4f6]" />
            <div class="riraa-top-context hidden min-w-0 items-center gap-2 text-sm md:flex">
                <span class="text-[#969ba3]">Company</span>
                <span class="text-[#d2d4d8]">/</span>
                <span class="max-w-[220px] truncate font-semibold text-[#30343b]">
                    {{ $page.props.projectContext?.company.name ?? 'Workspace' }}
                </span>
            </div>
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            <Link :href="route('cards.index')" class="riraa-search-link hidden h-10 w-[280px] shrink-0 items-center gap-2 rounded-xl border px-3 text-sm transition lg:flex">
                <Search class="size-4" />
                <span class="min-w-0 truncate whitespace-nowrap">Search tasks and projects</span>
                <kbd class="ml-auto shrink-0 whitespace-nowrap rounded border border-[#e0e2e6] bg-white px-1.5 py-0.5 text-[10px] text-[#9298a1]">⌘ K</kbd>
            </Link>

            <DropdownMenu v-if="createItems.length">
                <DropdownMenuTrigger as-child>
                    <Button size="sm" class="riraa-create-button h-10 rounded-full px-3.5 text-xs font-semibold shadow-none">
                        <Plus class="size-4" />
                        <span class="hidden sm:inline">Create</span>
                        <ChevronDown class="hidden size-3.5 sm:inline" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-52">
                    <DropdownMenuLabel>Create new</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem v-for="item in createItems" :key="item.label" as-child>
                        <Link :href="item.href"><Plus class="size-4" />{{ item.label }}</Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Button variant="ghost" size="icon" class="size-9 rounded-lg text-[#6b7078] hover:bg-[#f3f4f6]" title="Help">
                <CircleHelp class="size-[18px]" />
                <span class="sr-only">Help</span>
            </Button>
            <Button variant="ghost" size="icon" class="relative size-9 rounded-lg text-[#6b7078] hover:bg-[#f3f4f6]" title="Notifications">
                <Bell class="size-[18px]" />
                <span class="absolute right-1.5 top-1.5 size-1.5 rounded-full bg-[#ef5b62]" />
                <span class="sr-only">Notifications</span>
            </Button>
        </div>
    </header>
</template>

<style scoped>
.riraa-create-button {
    background: #f04b67 !important;
    background-image: none !important;
    color: #fff !important;
}

.riraa-create-button:hover {
    background: #db3856 !important;
}

.riraa-app-header {
    color: #30343b;
}

.riraa-app-header :deep(nav),
.riraa-app-header :deep(nav a),
.riraa-app-header :deep([data-slot='breadcrumb-separator']) {
    color: #7d848e;
}

.riraa-app-header :deep([data-slot='breadcrumb-page']) {
    color: #30343b;
}
</style>
