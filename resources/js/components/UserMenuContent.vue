<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Building2, LogOut, Settings } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/Components/UI/dropdown-menu';
import UserInfo from '@/Components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const props = defineProps<Props>();

const company = computed(() => props.user.company);
const companyRole = computed(() => {
    const role = props.user.company_membership?.role ?? props.user.role;

    return role
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
});

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <div class="px-2 py-2 text-sm">
        <div class="flex items-start gap-2">
            <Building2 class="mt-0.5 size-4 shrink-0 text-primary" />
            <div class="min-w-0">
                <p class="text-xs font-medium text-muted-foreground">Company</p>
                <p class="truncate font-medium">
                    {{ company?.name ?? 'No active company' }}
                </p>
                <p
                    v-if="company?.email || company?.industry"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{ company.email ?? company.industry }}
                </p>
                <p
                    v-if="company?.city || company?.country"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{
                        [company.city, company.country]
                            .filter(Boolean)
                            .join(', ')
                    }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ companyRole }} access
                </p>
            </div>
        </div>
    </div>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
