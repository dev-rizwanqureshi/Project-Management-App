<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Building2, CheckCircle2, Mail } from '@lucide/vue';
import { route } from 'ziggy-js';

import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { Spinner } from '@/Components/UI/spinner';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({
    layout: GuestLayout,
});

const props = defineProps<{
    invitation: {
        token: string;
        email: string;
        role_label: string;
        scope: string;
        company: { id: number; name: string };
        workspace: { id: number; name: string } | null;
        board: { id: number; name: string } | null;
        expires_at: string;
    };
    authenticated: boolean;
    email_matches: boolean;
    existing_account: boolean;
}>();

const acceptForm = useForm<{ invitation: string }>({ invitation: '' });
const registerForm = useForm({
    name: '',
    password: '',
    password_confirmation: '',
});

const acceptInvitation = () => {
    acceptForm.post(route('invitations.accept', props.invitation.token));
};

const registerAndAccept = () => {
    registerForm.post(route('invitations.register', props.invitation.token));
};
</script>

<template>
    <Head title="Accept invitation" />

    <div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-lg items-center">
        <section
            class="w-full rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
        >
            <div
                class="flex size-12 items-center justify-center rounded-md bg-primary text-primary-foreground"
            >
                <Mail class="size-6" />
            </div>
            <p class="mt-5 text-sm font-medium text-primary">
                Riraa invitation
            </p>
            <h1 class="mt-2 text-2xl font-semibold tracking-normal">
                Join {{ invitation.company.name }}
            </h1>
            <p class="mt-3 text-sm leading-6 text-muted-foreground">
                You have been invited as a {{ invitation.role_label }} to the
                {{ invitation.scope }}.
            </p>

            <div
                class="mt-5 grid gap-2 rounded-md border border-border bg-muted/30 p-4 text-sm"
            >
                <div class="flex items-center gap-2 font-medium">
                    <Building2 class="size-4 text-primary" />
                    {{ invitation.company.name }}
                </div>
                <div v-if="invitation.workspace" class="text-muted-foreground">
                    Workspace: {{ invitation.workspace.name }}
                </div>
                <div v-if="invitation.board" class="text-muted-foreground">
                    Board: {{ invitation.board.name }}
                </div>
                <div class="text-muted-foreground">
                    Invitation for {{ invitation.email }}
                </div>
            </div>

            <div
                v-if="authenticated && !email_matches"
                class="mt-5 rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
            >
                Sign in with {{ invitation.email }} to accept this invitation.
            </div>

            <div v-else-if="authenticated" class="mt-6">
                <Button
                    class="w-full"
                    :disabled="acceptForm.processing"
                    @click="acceptInvitation"
                >
                    <Spinner v-if="acceptForm.processing" />
                    <CheckCircle2 v-else class="size-4" />
                    Accept invitation
                </Button>
                <InputError :message="acceptForm.errors.invitation" />
            </div>

            <div v-else-if="existing_account" class="mt-6 grid gap-3">
                <p class="text-sm text-muted-foreground">
                    An account already exists for this email. Log in with that
                    account, then return to this invitation link.
                </p>
                <Button as-child class="w-full">
                    <Link
                        :href="
                            route('login', {
                                invitation: invitation.token,
                            })
                        "
                    >
                        Log in to accept
                        <ArrowRight class="size-4" />
                    </Link>
                </Button>
            </div>

            <form
                v-else
                class="mt-6 grid gap-4"
                @submit.prevent="registerAndAccept"
            >
                <div>
                    <h2 class="font-semibold">Create your account</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Your account will be added to this invitation
                        automatically.
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="invitation-name">Your name</Label>
                    <Input
                        id="invitation-name"
                        v-model="registerForm.name"
                        required
                        autocomplete="name"
                    />
                    <InputError :message="registerForm.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="invitation-password">Password</Label>
                    <Input
                        id="invitation-password"
                        v-model="registerForm.password"
                        required
                        type="password"
                        autocomplete="new-password"
                    />
                    <InputError :message="registerForm.errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="invitation-password-confirmation"
                        >Confirm password</Label
                    >
                    <Input
                        id="invitation-password-confirmation"
                        v-model="registerForm.password_confirmation"
                        required
                        type="password"
                        autocomplete="new-password"
                    />
                    <InputError
                        :message="registerForm.errors.password_confirmation"
                    />
                </div>
                <Button type="submit" :disabled="registerForm.processing">
                    <Spinner v-if="registerForm.processing" />
                    Create account and join
                </Button>
            </form>

            <p class="mt-6 text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')">Log in</TextLink>
            </p>
        </section>
    </div>
</template>
