<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Building2,
    FolderKanban,
    LayoutDashboard,
    LogOut,
    Settings as SettingsIcon,
    ShieldCheck,
    TicketCheck,
    UserCog,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';

import AppLogoIcon from '@/Components/AppLogoIcon.vue';
import { Button } from '@/Components/UI/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { Admin } from '@/types/auth';

type AdminNavItem = {
    title: string;
    href: string;
    icon: Component;
    show: boolean;
    exact?: boolean;
    activeHref?: string;
};

const page = usePage();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const admin = computed<Admin | null>(() => page.props.adminAuth.admin);
const appName = computed(() => String(page.props.name ?? 'Riraa'));
const permissions = computed(() => page.props.adminAuth.permissions);

const roleName = computed(() => {
    const role = String(admin.value?.role ?? 'admin');

    return role
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
});

const initials = computed(() => {
    const name = admin.value?.name ?? 'Admin';

    return name
        .split(' ')
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

const navItems = computed<AdminNavItem[]>(() => [
    {
        title: 'Dashboard',
        href: route('admin.dashboard'),
        icon: LayoutDashboard,
        show: permissions.value.includes('admin.dashboard.view'),
        exact: true,
    },
    {
        title: 'Admin Roles',
        href: route('admin.roles.index'),
        icon: ShieldCheck,
        show: permissions.value.includes('admin.roles.manage'),
    },
    {
        title: 'Management Users',
        href: route('admin.admins.index'),
        icon: UserCog,
        show: permissions.value.includes('admin.admins.view'),
    },
    {
        title: 'Companies',
        href: route('admin.companies.index'),
        icon: Building2,
        show: permissions.value.includes('admin.companies.view'),
    },
    {
        title: 'Users',
        href: route('admin.users.index'),
        icon: Users,
        show: permissions.value.includes('admin.users.view'),
    },
    {
        title: 'Workspaces',
        href: route('admin.workspaces.index'),
        icon: FolderKanban,
        show: permissions.value.includes('admin.workspaces.view'),
    },
    {
        title: 'Boards',
        href: route('admin.boards.index'),
        icon: FolderKanban,
        show: permissions.value.includes('admin.boards.view'),
    },
    {
        title: 'Tickets',
        href: route('admin.cards.index'),
        icon: TicketCheck,
        show: permissions.value.includes('admin.cards.view'),
    },
    {
        title: 'Reports',
        href: route('admin.dashboard'),
        icon: BarChart3,
        show: permissions.value.includes('admin.reports.view'),
        activeHref: '/admin/reports',
    },
]);

const settingsHref = computed(() => route('admin.settings.edit'));
const isSettingsActive = computed(() =>
    isCurrentOrParentUrl(settingsHref.value),
);

const isActiveNavItem = (item: AdminNavItem) => {
    const activeHref = item.activeHref ?? item.href;

    return item.exact
        ? isCurrentUrl(activeHref)
        : isCurrentOrParentUrl(activeHref);
};

const logout = () => {
    router.post(route('admin.logout'));
};
</script>

<template>
    <Head />

    <div class="flex min-h-screen bg-background text-foreground">
        <aside
            class="hidden min-h-screen w-72 shrink-0 flex-col border-r border-border bg-card px-4 py-5 lg:flex"
        >
            <div>
                <Link
                    :href="route('admin.dashboard')"
                    class="mb-8 flex items-center gap-3 rounded-md px-2"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-sm"
                    >
                        <AppLogoIcon class="size-7 fill-current" />
                    </div>
                    <span class="truncate text-lg font-semibold">
                        {{ appName }}
                    </span>
                </Link>

                <nav class="grid gap-1">
                    <Link
                        v-for="item in navItems.filter(
                            (navItem) => navItem.show,
                        )"
                        :key="item.title"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            isActiveNavItem(item)
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                        "
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.title }}
                    </Link>
                </nav>
            </div>

            <div class="mt-auto border-t border-border pt-4">
                <div class="mb-3 flex items-center gap-3 rounded-md px-2">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-sm font-semibold text-primary"
                    >
                        {{ initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ admin?.name ?? 'Admin' }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ roleName }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-1">
                    <Link
                        :href="settingsHref"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            isSettingsActive
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                        "
                    >
                        <SettingsIcon class="size-4" />
                        Settings
                    </Link>
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        @click="logout"
                    >
                        <LogOut class="size-4" />
                        Logout
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="flex min-h-16 items-center justify-between gap-4 border-b border-border bg-card px-4 sm:px-6"
            >
                <div>
                    <p class="text-sm font-medium text-primary">
                        Admin management
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Logged in as {{ admin?.name ?? 'Admin' }} -
                        {{ roleName }}
                    </p>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <Button as-child variant="outline" size="icon">
                        <Link :href="settingsHref">
                            <SettingsIcon class="size-4" />
                            <span class="sr-only">Settings</span>
                        </Link>
                    </Button>
                    <Button type="button" variant="outline" @click="logout">
                        <LogOut class="size-4" />
                        Logout
                    </Button>
                </div>
            </header>

            <main class="min-w-0 flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
