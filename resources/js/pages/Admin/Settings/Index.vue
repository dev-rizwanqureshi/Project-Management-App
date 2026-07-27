<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound, Settings, UserRound } from '@lucide/vue';
import { route } from 'ziggy-js';

import InputError from '@/Components/InputError.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';

type Account = {
    name: string;
    email: string;
    role: string;
};

const props = defineProps<{
    account: Account;
}>();

const profileForm = useForm({
    name: props.account.name,
    email: props.account.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.patch(route('admin.settings.profile.update'), {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put(route('admin.settings.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Admin Settings" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div>
            <p class="text-sm font-medium text-primary">Admin account</p>
            <h1
                class="mt-1 flex items-center gap-2 text-2xl font-semibold tracking-normal"
            >
                <Settings class="size-6 text-primary" />
                Settings
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ account.role }}
            </p>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <form
                class="rounded-lg border border-border bg-card p-5 shadow-sm"
                @submit.prevent="updateProfile"
            >
                <div class="mb-5 flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <UserRound class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold tracking-normal">
                            Profile
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Name and email for this admin account.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="admin-settings-name">Name</Label>
                        <Input
                            id="admin-settings-name"
                            v-model="profileForm.name"
                            autocomplete="name"
                        />
                        <InputError :message="profileForm.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="admin-settings-email">Email</Label>
                        <Input
                            id="admin-settings-email"
                            v-model="profileForm.email"
                            type="email"
                            autocomplete="email"
                        />
                        <InputError :message="profileForm.errors.email" />
                    </div>

                    <div>
                        <Button
                            type="submit"
                            :disabled="profileForm.processing"
                        >
                            Save Profile
                        </Button>
                    </div>
                </div>
            </form>

            <form
                class="rounded-lg border border-border bg-card p-5 shadow-sm"
                @submit.prevent="updatePassword"
            >
                <div class="mb-5 flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <KeyRound class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold tracking-normal">
                            Password
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Change the password used for admin login.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="admin-current-password">
                            Current password
                        </Label>
                        <PasswordInput
                            id="admin-current-password"
                            v-model="passwordForm.current_password"
                            autocomplete="current-password"
                        />
                        <InputError
                            :message="passwordForm.errors.current_password"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="admin-new-password">New password</Label>
                        <PasswordInput
                            id="admin-new-password"
                            v-model="passwordForm.password"
                            autocomplete="new-password"
                        />
                        <InputError :message="passwordForm.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="admin-password-confirmation">
                            Confirm password
                        </Label>
                        <PasswordInput
                            id="admin-password-confirmation"
                            v-model="passwordForm.password_confirmation"
                            autocomplete="new-password"
                        />
                        <InputError
                            :message="passwordForm.errors.password_confirmation"
                        />
                    </div>

                    <div>
                        <Button
                            type="submit"
                            :disabled="passwordForm.processing"
                        >
                            Update Password
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</template>
