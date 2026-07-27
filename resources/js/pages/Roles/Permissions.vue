<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, LockKeyhole, Save, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

import { Button } from '@/Components/UI/button';
import { showSuccessAlert } from '@/lib/sweetAlert';

type RoleItem = {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
    users_count: number;
    permission_slugs: string[];
    can_edit: boolean;
};

type PermissionItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
};

type PermissionGroup = {
    group: string;
    permissions: PermissionItem[];
};

const props = defineProps<{
    role: RoleItem;
    permissionGroups: PermissionGroup[];
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

const selectedPermissions = ref<string[]>([...props.role.permission_slugs]);
const isSaving = ref(false);
const permissionCount = computed(() =>
    props.permissionGroups.reduce(
        (count, group) => count + group.permissions.length,
        0,
    ),
);

const togglePermission = (slug: string, checked: boolean) => {
    selectedPermissions.value = checked
        ? [...new Set([...selectedPermissions.value, slug])]
        : selectedPermissions.value.filter((permission) => permission !== slug);
};

const handlePermissionChange = (slug: string, event: Event) => {
    togglePermission(slug, (event.target as HTMLInputElement).checked);
};

const savePermissions = () => {
    if (!props.role.can_edit) {
        return;
    }

    isSaving.value = true;
    router.put(
        route('roles.permissions.update', props.role.id),
        {
            permissions: selectedPermissions.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSuccessAlert(
                    'Permissions saved',
                    `${props.role.name} permissions were updated successfully.`,
                );
            },
            onFinish: () => {
                isSaving.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`${role.name} Permissions`" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Button as-child variant="outline" class="mb-4">
                    <Link :href="route('roles.index')">
                        <ArrowLeft class="size-4" />
                        Roles
                    </Link>
                </Button>

                <p class="text-sm font-medium text-primary">
                    Attach Permissions
                </p>
                <h1
                    class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
                >
                    <ShieldCheck class="size-6 text-primary" />
                    {{ role.name }}
                    <span
                        v-if="role.is_system"
                        class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                    >
                        default
                    </span>
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ role.name }} has {{ selectedPermissions.length }} of
                    {{ permissionCount }} permissions selected.
                </p>
            </div>

            <Button
                type="button"
                :disabled="!role.can_edit || isSaving"
                @click="savePermissions"
            >
                <Save v-if="role.can_edit" class="size-4" />
                <LockKeyhole v-else class="size-4" />
                {{ role.can_edit ? 'Save Permissions' : 'Owner Locked' }}
            </Button>
        </div>

        <div class="rounded-lg border border-border bg-card p-5 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="group in permissionGroups"
                    :key="group.group"
                    class="rounded-md border border-border bg-background"
                >
                    <h2
                        class="rounded-t-md bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground"
                    >
                        {{ group.group }}
                    </h2>
                    <div class="grid gap-3 p-4">
                        <label
                            v-for="permission in group.permissions"
                            :key="permission.slug"
                            class="flex items-start gap-3 text-sm"
                            :class="
                                role.can_edit
                                    ? 'cursor-pointer'
                                    : 'cursor-not-allowed opacity-70'
                            "
                        >
                            <input
                                type="checkbox"
                                class="mt-1 size-4 cursor-pointer rounded border-input accent-violet-600 disabled:cursor-not-allowed"
                                :checked="
                                    selectedPermissions.includes(
                                        permission.slug,
                                    )
                                "
                                :disabled="!role.can_edit"
                                @change="
                                    handlePermissionChange(
                                        permission.slug,
                                        $event,
                                    )
                                "
                            />
                            <span>
                                <span class="font-medium">
                                    {{ permission.name }}
                                </span>
                                <span
                                    v-if="permission.description"
                                    class="mt-0.5 block text-xs text-muted-foreground"
                                >
                                    {{ permission.description }}
                                </span>
                            </span>
                        </label>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
