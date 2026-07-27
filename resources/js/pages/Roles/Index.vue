<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';

type RoleItem = {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
    users_count: number;
    permission_slugs?: string[];
    can_edit?: boolean;
};

const props = defineProps<{
    roles: RoleItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles & Permissions',
                href: route('roles.index'),
            },
        ],
    },
});

const roleName = ref('');
const isSaving = ref(false);
const countsPerPage = ref('10');

const pagedRoles = computed(() =>
    props.roles.slice(0, Number(countsPerPage.value)),
);
const permissionHref = (role: RoleItem) =>
    route('roles.permissions.edit', role.id);

const createRole = () => {
    if (!roleName.value.trim()) {
        return;
    }

    isSaving.value = true;
    router.post(
        route('roles.store'),
        {
            name: roleName.value,
            permissions: ['dashboard.view'],
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                roleName.value = '';
            },
            onFinish: () => {
                isSaving.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">Owner settings</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                    Manage Roles
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Choose what each role can see and manage across Riraa.
                </p>
            </div>

            <form
                class="flex w-full gap-2 sm:w-auto"
                @submit.prevent="createRole"
            >
                <Input
                    v-model="roleName"
                    class="sm:w-56"
                    placeholder="New role name"
                />
                <Button type="submit" :disabled="isSaving">
                    <Plus class="size-4" />
                    Add Role
                </Button>
            </form>
        </div>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-border p-4"
            >
                <div class="grid gap-1">
                    <label
                        for="counts-per-page"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Counts per page
                    </label>
                    <select
                        id="counts-per-page"
                        v-model="countsPerPage"
                        class="h-9 cursor-pointer rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                    </select>
                </div>

                <div class="text-sm text-muted-foreground">
                    Showing {{ pagedRoles.length }} of {{ roles.length }} roles
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-primary text-primary-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Name</th>
                            <th class="px-5 py-3 font-semibold">Users</th>
                            <th class="px-5 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="role in pagedRoles"
                            :key="role.id"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-5 py-4 font-medium">
                                <Link
                                    :href="permissionHref(role)"
                                    class="flex items-center gap-2 hover:text-primary"
                                >
                                    <ShieldCheck class="size-4" />
                                    {{ role.name }}
                                    <span
                                        v-if="role.is_system"
                                        class="rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary"
                                    >
                                        default
                                    </span>
                                </Link>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ role.users_count }}
                            </td>
                            <td class="px-5 py-4">
                                <Link
                                    :href="permissionHref(role)"
                                    class="inline-flex items-center gap-2 font-medium text-primary hover:text-primary/80"
                                >
                                    Permissions
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
