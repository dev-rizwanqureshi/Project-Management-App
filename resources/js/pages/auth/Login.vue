<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { route } from 'ziggy-js';

import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import { Button } from '@/Components/UI/button';
import { Checkbox } from '@/Components/UI/checkbox';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { Spinner } from '@/Components/UI/spinner';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useAuthStore } from '@/stores/useAuthStore';
import type { LoginCredentials, ValidationErrors } from '@/stores/useAuthStore';

defineOptions({
    layout: GuestLayout,
});

const props = defineProps<{
    invitationToken?: string | null;
}>();

const authStore = useAuthStore();
const form = reactive<LoginCredentials>({
    email: '',
    password: '',
    remember: false,
});
const errors = ref<ValidationErrors>({});
const statusMessage = ref<string | null>(null);

const firstError = (field: keyof LoginCredentials): string | undefined =>
    errors.value[field]?.[0];

const submit = async () => {
    errors.value = {};
    statusMessage.value = null;

    const result = await authStore.login({ ...form });

    if (result.success) {
        router.visit(
            props.invitationToken
                ? route('invitations.show', props.invitationToken)
                : route('dashboard'),
        );

        return;
    }

    errors.value = result.errors ?? {};
    statusMessage.value = result.message ?? 'Invalid credentials';
};
</script>

<template>
    <Head title="Log in" />

    <div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-md items-center">
        <form
            class="w-full rounded-lg border border-border bg-card p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-normal">Log in</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Access your project workspace.
                </p>
            </div>

            <div
                v-if="statusMessage"
                class="mb-4 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
            >
                {{ statusMessage }}
            </div>

            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="firstError('email')" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="firstError('password')" />
                </div>

                <Label class="flex items-center gap-3 text-sm" for="remember">
                    <Checkbox id="remember" v-model="form.remember" />
                    <span>Remember me</span>
                </Label>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :disabled="authStore.isLoading"
                    data-test="login-button"
                >
                    <Spinner v-if="authStore.isLoading" />
                    Log in
                </Button>
            </div>

            <div class="mt-6 text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="route('register')">Create one</TextLink>
            </div>
        </form>
    </div>
</template>
