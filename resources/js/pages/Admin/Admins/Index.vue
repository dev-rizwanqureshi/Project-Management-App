<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Search, ShieldCheck, UserCog } from '@lucide/vue';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

import PaginationControls from '@/Components/Admin/PaginationControls.vue';
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
};

type AdminRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_name: string;
    admin_role_id: number | null;
    is_owner: boolean;
    created_at: string | null;
    updated_at: string | null;
};

type Pagination<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Filters = {
    search: string;
    per_page: number;
};

type SortState = {
    field: string;
    direction: 'asc' | 'desc';
};

type PageAccess = {
    manage_admins: boolean;
};

const props = defineProps<{
    admins: Pagination<AdminRow>;
    roles: AdminRoleItem[];
    filters: Filters;
    sort: SortState;
    can: PageAccess;
}>();

const search = ref(props.filters.search);
const perPage = ref(String(props.filters.per_page));
const isCreateAdminOpen = ref(false);
const updatingAdminId = ref<number | null>(null);
const selectedRoles = ref<Record<number, string>>({});
const createAdminForm = useForm({
    name: '',
    email: '',
    password: '',
    admin_role_id: props.roles[0] ? String(props.roles[0].id) : '',
});

const syncSelectedRoles = () => {
    selectedRoles.value = Object.fromEntries(
        props.admins.data.map((admin) => [
            admin.id,
            admin.admin_role_id ? String(admin.admin_role_id) : '',
        ]),
    );
};

syncSelectedRoles();

watch(() => props.admins.data, syncSelectedRoles, { deep: true });
watch(
    () => props.roles,
    (roles) => {
        if (!createAdminForm.admin_role_id && roles[0]) {
            createAdminForm.admin_role_id = String(roles[0].id);
        }
    },
    { deep: true },
);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('en', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

const sortHref = (field: string) =>
    route('admin.admins.index', {
        search: search.value,
        per_page: perPage.value,
        sort: field,
        direction:
            props.sort.field === field && props.sort.direction === 'asc'
                ? 'desc'
                : 'asc',
    });

const sortLabel = (field: string) =>
    props.sort.field === field
        ? props.sort.direction === 'asc'
            ? '↑'
            : '↓'
        : '';

const applyFilters = () => {
    router.get(
        route('admin.admins.index'),
        {
            search: search.value,
            per_page: perPage.value,
            sort: props.sort.field,
            direction: props.sort.direction,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const updateCreateAdminOpen = (open: boolean) => {
    isCreateAdminOpen.value = open;

    if (!open) {
        createAdminForm.reset();
        createAdminForm.clearErrors();
    }
};

const createAdmin = () => {
    createAdminForm
        .transform((data) => ({
            ...data,
            password: data.password || undefined,
            admin_role_id: Number(data.admin_role_id),
        }))
        .post(route('admin.admins.store'), {
            preserveScroll: true,
            onSuccess: () => {
                createAdminForm.reset('name', 'email', 'password');
                isCreateAdminOpen.value = false;
                showSuccessAlert(
                    'Management user created',
                    'The admin account was created successfully.',
                );
            },
        });
};

const updateRole = (admin: AdminRow) => {
    const roleId = selectedRoles.value[admin.id];

    if (!roleId || roleId === String(admin.admin_role_id ?? '')) {
        return;
    }

    updatingAdminId.value = admin.id;
    router.put(
        route('admin.admins.update', admin.id),
        {
            admin_role_id: Number(roleId),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSuccessAlert(
                    'Role updated',
                    'The management user role was saved successfully.',
                );
            },
            onFinish: () => {
                updatingAdminId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Management Users" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary">Admin management</p>
                <h1
                    class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
                >
                    <UserCog class="size-6 text-primary" />
                    Management Users
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Create admin-side users and assign their management role.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="can.manage_admins"
                    type="button"
                    @click="isCreateAdminOpen = true"
                >
                    <Plus class="size-4" />
                    Add Management User
                </Button>
                <Button as-child variant="outline">
                    <Link :href="route('admin.roles.index')">
                        <ShieldCheck class="size-4" />
                        Admin Roles
                    </Link>
                </Button>
            </div>
        </div>

        <Dialog :open="isCreateAdminOpen" @update:open="updateCreateAdminOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create Management User</DialogTitle>
                    <DialogDescription>
                        Add a new admin-side user and assign a management role.
                    </DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="createAdmin">
                    <div class="grid gap-2">
                        <Label for="management-user-name">Name</Label>
                        <Input
                            id="management-user-name"
                            v-model="createAdminForm.name"
                            placeholder="Riraa Support"
                            :aria-invalid="!!createAdminForm.errors.name"
                        />
                        <InputError :message="createAdminForm.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="management-user-email">Email</Label>
                        <Input
                            id="management-user-email"
                            v-model="createAdminForm.email"
                            type="email"
                            placeholder="support@riraa.com"
                            :aria-invalid="!!createAdminForm.errors.email"
                        />
                        <InputError :message="createAdminForm.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="management-user-password">Password</Label>
                        <Input
                            id="management-user-password"
                            v-model="createAdminForm.password"
                            type="password"
                            placeholder="Leave blank to use password"
                            :aria-invalid="!!createAdminForm.errors.password"
                        />
                        <InputError
                            :message="createAdminForm.errors.password"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="management-user-role">Role</Label>
                        <select
                            id="management-user-role"
                            v-model="createAdminForm.admin_role_id"
                            class="riraa-select"
                            :aria-invalid="
                                !!createAdminForm.errors.admin_role_id
                            "
                        >
                            <option value="" disabled>Select role</option>
                            <option
                                v-for="role in roles"
                                :key="role.id"
                                :value="String(role.id)"
                            >
                                {{ role.name }}
                            </option>
                        </select>
                        <InputError
                            :message="createAdminForm.errors.admin_role_id"
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="updateCreateAdminOpen(false)"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="
                                createAdminForm.processing || roles.length === 0
                            "
                        >
                            <Plus class="size-4" />
                            Create User
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <div class="rounded-lg border border-border bg-card shadow-sm">
            <form
                class="flex flex-wrap items-end justify-between gap-3 border-b border-border p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label
                        for="admin-staff-search"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Search
                    </label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            id="admin-staff-search"
                            v-model="search"
                            class="w-72 pl-9"
                            placeholder="Name, email, role"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <div class="grid gap-1">
                        <label
                            for="admin-staff-per-page"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Per page
                        </label>
                        <select
                            id="admin-staff-per-page"
                            v-model="perPage"
                            class="riraa-select"
                            @change="applyFilters"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-primary text-primary-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('name')"
                                    >Name {{ sortLabel('name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('email')"
                                    >Email {{ sortLabel('email') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('role_name')"
                                    >Role {{ sortLabel('role_name') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('created_at')"
                                    >Created {{ sortLabel('created_at') }}</Link
                                >
                            </th>
                            <th class="px-5 py-3 font-semibold">
                                <Link :href="sortHref('updated_at')"
                                    >Updated {{ sortLabel('updated_at') }}</Link
                                >
                            </th>
                            <th
                                v-if="can.manage_admins"
                                class="px-5 py-3 font-semibold"
                            >
                                Assign Role
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="admin in admins.data"
                            :key="admin.id"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-5 py-4 font-medium">
                                <div class="flex items-center gap-2">
                                    <ShieldCheck class="size-4 text-primary" />
                                    {{ admin.name }}
                                </div>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ admin.email }}
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        admin.is_owner
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ admin.role_name }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(admin.created_at) }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(admin.updated_at) }}
                            </td>
                            <td v-if="can.manage_admins" class="px-5 py-4">
                                <div class="flex min-w-64 items-center gap-2">
                                    <select
                                        v-model="selectedRoles[admin.id]"
                                        class="riraa-select"
                                    >
                                        <option
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="String(role.id)"
                                        >
                                            {{ role.name }}
                                        </option>
                                    </select>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        :disabled="
                                            updatingAdminId === admin.id ||
                                            !selectedRoles[admin.id] ||
                                            selectedRoles[admin.id] ===
                                                String(
                                                    admin.admin_role_id ?? '',
                                                )
                                        "
                                        @click="updateRole(admin)"
                                    >
                                        Save
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="admins.data.length === 0">
                            <td
                                :colspan="can.manage_admins ? 6 : 5"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                No management users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                <PaginationControls :pagination="admins" />
            </div>
        </div>
    </section>
</template>
