<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    FolderKanban,
    ShieldCheck,
    TicketCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';

import { Button } from '@/Components/UI/button';

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
    roleChart: ChartItem[];
}>();

const page = usePage();
const icons = [
    Users,
    ShieldCheck,
    Building2,
    Users,
    FolderKanban,
    FolderKanban,
    TicketCheck,
];
const maxRoleValue = computed(() =>
    Math.max(1, ...props.roleChart.map((item) => item.value)),
);
const canManageAdminRoles = computed(() =>
    page.props.adminAuth.permissions.includes('admin.roles.manage'),
);
</script>

<template>
    <Head title="Admin Dashboard" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">
                    Platform overview
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                    Admin Management Dashboard
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Manage platform admins, admin roles, and customer data.
                </p>
            </div>

            <Button v-if="canManageAdminRoles" as-child variant="outline">
                <Link :href="route('admin.roles.index')">
                    <ShieldCheck class="size-4" />
                    Admin Roles
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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

        <article class="rounded-lg border border-border bg-card p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold tracking-normal">
                        Admin users by role
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Owner, admin, support staff, and custom admin types.
                    </p>
                </div>
                <ShieldCheck class="size-5 text-primary" />
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
                        <span class="pb-2 text-sm font-semibold text-primary">
                            {{ item.value }}
                        </span>
                    </div>
                    <span class="text-xs text-muted-foreground">
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </article>
    </section>
</template>
