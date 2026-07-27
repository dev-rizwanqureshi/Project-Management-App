<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { route } from 'ziggy-js';

import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { Spinner } from '@/Components/UI/spinner';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useAuthStore } from '@/stores/useAuthStore';
import type {
    RegisterCompanyPayload,
    ValidationErrors,
} from '@/stores/useAuthStore';

defineOptions({
    layout: GuestLayout,
});

const authStore = useAuthStore();
const form = reactive<RegisterCompanyPayload>({
    company_name: '',
    company_email: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const errors = ref<ValidationErrors>({});
const statusMessage = ref<string | null>(null);

const firstError = (field: keyof RegisterCompanyPayload): string | undefined =>
    errors.value[field]?.[0];

const submit = async () => {
    errors.value = {};
    statusMessage.value = null;

    const result = await authStore.registerCompany({ ...form });

    if (result.success) {
        router.visit(route('dashboard'));

        return;
    }

    errors.value = result.errors ?? {};
    statusMessage.value = result.message ?? 'Registration failed';
};
</script>

<template>
    <Head title="Register" />

    <div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-xl items-center">
        <form
            class="w-full rounded-lg border border-border bg-card p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-normal">
                    Create your company
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Start with a company account and your owner profile.
                </p>
            </div>

            <div
                v-if="statusMessage"
                class="mb-4 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
            >
                {{ statusMessage }}
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="company_name">Company name</Label>
                    <Input
                        id="company_name"
                        v-model="form.company_name"
                        type="text"
                        required
                        autofocus
                        autocomplete="organization"
                        placeholder="Acme Projects"
                    />
                    <InputError :message="firstError('company_name')" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="company_email">Company email</Label>
                    <Input
                        id="company_email"
                        v-model="form.company_email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="hello@example.com"
                    />
                    <InputError :message="firstError('company_email')" />
                </div>

                <div class="grid gap-2">
                    <Label for="name">Your name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autocomplete="name"
                        placeholder="Full name"
                    />
                    <InputError :message="firstError('name')" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Your email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="you@example.com"
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
                        autocomplete="new-password"
                        placeholder="Password"
                    />
                    <InputError :message="firstError('password')" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                    <InputError
                        :message="firstError('password_confirmation')"
                    />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full sm:col-span-2"
                    :disabled="authStore.isLoading"
                    data-test="register-company-button"
                >
                    <Spinner v-if="authStore.isLoading" />
                    Create company
                </Button>
            </div>

            <div class="mt-6 text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')">Log in</TextLink>
            </div>
        </form>
    </div>
</template>
