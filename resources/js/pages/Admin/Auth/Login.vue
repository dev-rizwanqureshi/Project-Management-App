<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/UI/button';
import { Checkbox } from '@/Components/UI/checkbox';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { Spinner } from '@/Components/UI/spinner';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({
    layout: GuestLayout,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('admin.login.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Admin Login" />

    <div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-md items-center">
        <form
            class="w-full rounded-lg border border-border bg-card p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-normal">
                    Admin login
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Access the Riraa platform management dashboard.
                </p>
            </div>

            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="admin-email">Email address</Label>
                    <Input
                        id="admin-email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="owner@riraa.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="admin-password">Password</Label>
                    <Input
                        id="admin-password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <Label
                    class="flex items-center gap-3 text-sm"
                    for="admin-remember"
                >
                    <Checkbox id="admin-remember" v-model="form.remember" />
                    <span>Remember me</span>
                </Label>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    Log in
                </Button>
            </div>
        </form>
    </div>
</template>
