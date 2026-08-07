<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    LayoutDashboard,
    ShieldCheck,
    TicketCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLogo from '@/Components/AppLogo.vue';
import NavMain from '@/Components/NavMain.vue';
import NavUser from '@/Components/NavUser.vue';
import ProjectNavigation from '@/Components/ProjectNavigation.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/Components/UI/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const canManageRoles = computed(() =>
    page.props.auth.permissions.includes('roles.manage'),
);
const canViewUsers = computed(() =>
    page.props.auth.permissions.includes('users.view'),
);
const canViewCards = computed(() =>
    page.props.auth.permissions.includes('cards.view'),
);
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'For you',
        href: dashboard(),
        icon: LayoutDashboard,
    },
]);
</script>

<template>
    <Sidebar collapsible="offcanvas" variant="sidebar" data-riraa-shell class="border-r border-[#e5e7eb]">
        <SidebarHeader class="border-b border-[#e5e7eb] px-3 py-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="bg-[#fbfbfc] px-2 py-3">
            <NavMain :items="mainNavItems" label="Workspace" />
            <SidebarSeparator class="my-3 bg-[#e5e7eb]" />
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Manage</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-if="canViewUsers">
                        <SidebarMenuButton as-child tooltip="People">
                            <Link :href="route('users.index')">
                                <Users />
                                <span>People</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-if="canViewCards">
                        <SidebarMenuButton as-child tooltip="All tickets">
                            <Link :href="route('cards.index')">
                                <TicketCheck />
                                <span>All tickets</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-if="canManageRoles">
                        <SidebarMenuButton as-child tooltip="Roles & permissions">
                            <Link :href="route('roles.index')">
                                <ShieldCheck />
                                <span>Roles &amp; permissions</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
            <SidebarSeparator class="my-3 bg-[#e5e7eb]" />
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Company</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child tooltip="Company overview">
                            <Link :href="dashboard()">
                                <BriefcaseBusiness />
                                <span>Company overview</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
            <ProjectNavigation />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<style scoped>
[data-riraa-shell] :deep([data-sidebar='sidebar']) {
    background: #fff;
    color: #525b67;
}

[data-riraa-shell] :deep([data-sidebar='content']) {
    background: #fbfbfc;
}

[data-riraa-shell] :deep([data-sidebar='group-label']) {
    color: #9aa1aa;
}

[data-riraa-shell] :deep([data-slot='sidebar-menu-button']) {
    color: #5c6672;
}

[data-riraa-shell] :deep([data-slot='sidebar-menu-button'][data-active='true']) {
    background: #edf2ff;
    color: #2563eb;
}

[data-riraa-shell] :deep([data-slot='sidebar-menu-sub-button'][data-active='true']) {
    color: #2563eb;
    background: #f1f5ff;
}
</style>
