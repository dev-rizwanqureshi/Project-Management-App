<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/UI/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/UI/dialog';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { showSuccessAlert } from '@/lib/sweetAlert';

type AdminRoleItem = {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
    admins_count: number;
};

const props = defineProps<{
    roles: AdminRoleItem[];
}>();

const isCreateRoleOpen = ref(false);
const countsPerPage = ref('10');
const createRoleForm = useForm({
    name: '',
    permissions: ['admin.dashboard.view'],
});

const pagedRoles = computed(() =>
    props.roles.slice(0, Number(countsPerPage.value)),
);
const permissionHref = (role: AdminRoleItem) =>
    route('admin.roles.permissions.edit', role.id);

const updateCreateRoleOpen = (open: boolean) => {
    isCreateRoleOpen.value = open;

    if (!open) {
        createRoleForm.reset();
        createRoleForm.clearErrors();
    }
};

const createRole = () => {
    createRoleForm.post(route('admin.roles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createRoleForm.reset('name');
            isCreateRoleOpen.value = false;
            showSuccessAlert(
                'Role created',
                'The admin role was created successfully.',
            );
        },
    });
};
</script>

<template>
    <Head title="Admin Roles" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">Admin management</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                    Manage Admin Roles
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Create admin types and choose which admin pages they can
                    use.
                </p>
            </div>

            <Button type="button" @click="isCreateRoleOpen = true">
                <Plus class="size-4" />
                Add Role
            </Button>
        </div>

        <Dialog :open="isCreateRoleOpen" @update:open="updateCreateRoleOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create Admin Role</DialogTitle>
                    <DialogDescription>
                        Add a new management role, then choose its permissions.
                    </DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="createRole">
                    <div class="grid gap-2">
                        <Label for="admin-role-name">Role name</Label>
                        <Input
                            id="admin-role-name"
                            v-model="createRoleForm.name"
                            placeholder="Support manager"
                            :aria-invalid="!!createRoleForm.errors.name"
                        />
                        <InputError :message="createRoleForm.errors.name" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="updateCreateRoleOpen(false)"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="createRoleForm.processing"
                        >
                            <Plus class="size-4" />
                            Create Role
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-border p-4"
            >
                <div class="grid gap-1">
                    <label
                        for="admin-counts-per-page"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Counts per page
                    </label>
                    <select
                        id="admin-counts-per-page"
                        v-model="countsPerPage"
                        class="riraa-select"
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
                            <th class="px-5 py-3 font-semibold">Admins</th>
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
                                {{ role.admins_count }}
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
