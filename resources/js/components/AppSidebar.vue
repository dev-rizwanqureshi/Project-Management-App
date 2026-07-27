<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    FolderKanban,
    LayoutGrid,
    ShieldCheck,
    TicketCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLogo from '@/Components/AppLogo.vue';
import NavFooter from '@/Components/NavFooter.vue';
import NavMain from '@/Components/NavMain.vue';
import NavUser from '@/Components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
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
const canViewWorkspaces = computed(() =>
    page.props.auth.permissions.includes('workspaces.view'),
);
const canViewBoards = computed(() =>
    page.props.auth.permissions.includes('boards.view'),
);
const canViewCards = computed(() =>
    page.props.auth.permissions.includes('cards.view'),
);
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(canViewUsers.value
        ? [
              {
                  title: 'Users',
                  href: route('users.index'),
                  icon: Users,
              },
          ]
        : []),
    ...(canViewWorkspaces.value
        ? [
              {
                  title: 'Workspaces',
                  href: route('workspaces.index'),
                  icon: FolderKanban,
              },
          ]
        : []),
    ...(canViewBoards.value
        ? [
              {
                  title: 'Boards',
                  href: route('boards.index'),
                  icon: FolderKanban,
              },
          ]
        : []),
    ...(canViewCards.value
        ? [
              {
                  title: 'Tickets',
                  href: route('cards.index'),
                  icon: TicketCheck,
              },
          ]
        : []),
    ...(canManageRoles.value
        ? [
              {
                  title: 'Roles & Permissions',
                  href: route('roles.index'),
                  icon: ShieldCheck,
              },
          ]
        : []),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
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

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
