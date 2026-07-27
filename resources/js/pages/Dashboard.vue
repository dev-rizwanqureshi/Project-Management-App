<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Building2,
    FolderKanban,
    LogOut,
    ShieldCheck,
    TicketCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';

import { Button } from '@/Components/UI/button';
import { useAuthStore } from '@/stores/useAuthStore';

type StatCard = {
    label: string;
    value: number;
    helper: string;
};

type ChartItem = {
    label: string;
    value: number;
};

const props = defineProps<{
    stats: StatCard[];
    ticketChart: ChartItem[];
    roleChart: ChartItem[];
    canViewAnalytics: boolean;
    canManageRoles: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: route('dashboard'),
            },
        ],
    },
});

const authStore = useAuthStore();
const icons = [ShieldCheck, Building2, FolderKanban, Users, TicketCheck, Users];
const overviewLabel = computed(() =>
    props.canViewAnalytics ? 'Owner overview' : 'Dashboard',
);
const maxTicketValue = computed(() =>
    Math.max(1, ...props.ticketChart.map((item) => item.value)),
);
const maxRoleValue = computed(() =>
    Math.max(1, ...props.roleChart.map((item) => item.value)),
);

const logout = async () => {
    await authStore.logout();
    router.visit(route('login'));
};
</script>

<template>
    <Head title="Dashboard" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">
                    {{ overviewLabel }}
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                    Welcome, {{ authStore.user?.name ?? 'there' }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ authStore.user?.company?.name ?? 'Your company' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button v-if="canManageRoles" as-child variant="outline">
                    <Link :href="route('roles.index')">
                        <ShieldCheck class="size-4" />
                        Roles
                    </Link>
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="authStore.isLoading"
                    @click="logout"
                >
                    <LogOut class="size-4" />
                    Logout
                </Button>
            </div>
        </div>

        <div
            v-if="canViewAnalytics"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="(stat, index) in stats"
                :key="stat.label"
                class="rounded-lg border border-border bg-card p-5 shadow-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            {{ stat.label }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold tracking-normal">
                            {{ stat.value }}
                        </p>
                    </div>
                    <div
                        class="flex size-11 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <component :is="icons[index] ?? Users" class="size-5" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    {{ stat.helper }}
                </p>
            </article>
        </div>

        <div
            v-if="canViewAnalytics"
            class="grid gap-4 xl:grid-cols-[1.25fr_0.75fr]"
        >
            <article
                class="rounded-lg border border-border bg-card p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold tracking-normal">
                            Tickets by status
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Live card counts from Riraa boards.
                        </p>
                    </div>
                    <TicketCheck class="size-5 text-primary" />
                </div>

                <div class="mt-6 grid gap-4">
                    <div
                        v-for="item in ticketChart"
                        :key="item.label"
                        class="grid gap-2"
                    >
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium">{{ item.label }}</span>
                            <span class="text-muted-foreground">{{
                                item.value
                            }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-muted">
                            <div
                                class="h-3 rounded-full bg-primary"
                                :style="{
                                    width: `${Math.max(6, (item.value / maxTicketValue) * 100)}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </article>

            <article
                class="rounded-lg border border-border bg-card p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold tracking-normal">
                            Users by role
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Owner, admin, and staff distribution.
                        </p>
                    </div>
                    <Users class="size-5 text-primary" />
                </div>

                <div class="mt-6 flex h-56 items-end gap-4">
                    <div
                        v-for="item in roleChart"
                        :key="item.label"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <div
                            class="flex w-full items-end justify-center rounded-md bg-primary/10"
                            :style="{
                                height: `${Math.max(18, (item.value / maxRoleValue) * 180)}px`,
                            }"
                        >
                            <span
                                class="pb-2 text-sm font-semibold text-primary"
                            >
                                {{ item.value }}
                            </span>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ item.label }}
                        </span>
                    </div>
                </div>
            </article>
        </div>

        <article
            v-else
            class="rounded-lg border border-border bg-card p-6 shadow-sm"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex size-11 items-center justify-center rounded-md bg-primary/10 text-primary"
                >
                    <FolderKanban class="size-5" />
                </div>
                <div>
                    <h2 class="text-base font-semibold tracking-normal">
                        Riraa workspace
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ authStore.user?.role ?? 'member' }} access is active.
                    </p>
                </div>
            </div>
        </article>
    </section>
</template>
