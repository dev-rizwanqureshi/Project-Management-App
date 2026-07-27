<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, CheckCircle2, MailPlus } from '@lucide/vue';
import { route } from 'ziggy-js';

import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/UI/button';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { Spinner } from '@/Components/UI/spinner';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Company setup',
                href: route('company.setup'),
            },
        ],
    },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    website: '',
    industry: '',
    team_size: '',
    address_line: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? '',
    description: '',
});

const submit = () => {
    form.post(route('company.setup.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Company setup" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
            <div
                class="rounded-lg border border-primary/20 bg-primary/5 p-6 shadow-sm"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <Building2 class="size-6" />
                </div>
                <p class="mt-5 text-sm font-medium text-primary">
                    Company required
                </p>
                <h1 class="mt-2 text-2xl font-semibold tracking-normal">
                    You don't have any company yet.
                </h1>
                <p
                    class="mt-3 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    Create a new company to start using Riraa, or ask a company
                    owner to invite you to their company.
                </p>

                <div class="mt-6 grid gap-3 text-sm">
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-0.5 size-4 text-primary" />
                        <span
                            >Creating a company makes you the company
                            owner.</span
                        >
                    </div>
                    <div class="flex items-start gap-3">
                        <MailPlus class="mt-0.5 size-4 text-primary" />
                        <span
                            >If you were invited, use the invite link from the
                            company owner.</span
                        >
                    </div>
                </div>
            </div>

            <form
                class="rounded-lg border border-border bg-card p-6 shadow-sm"
                @submit.prevent="submit"
            >
                <div class="mb-6">
                    <h2 class="text-xl font-semibold tracking-normal">
                        Create company
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Add the details your team will see across Riraa.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="name">Company name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            autofocus
                            autocomplete="organization"
                            placeholder="Riraa"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Company email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="hello@riraa.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            autocomplete="tel"
                            placeholder="+1 555 0100"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="website">Website</Label>
                        <Input
                            id="website"
                            v-model="form.website"
                            type="url"
                            autocomplete="url"
                            placeholder="https://riraa.com"
                        />
                        <InputError :message="form.errors.website" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="industry">Industry</Label>
                        <Input
                            id="industry"
                            v-model="form.industry"
                            type="text"
                            placeholder="Project management"
                        />
                        <InputError :message="form.errors.industry" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="team_size">Team size</Label>
                        <Input
                            id="team_size"
                            v-model="form.team_size"
                            type="text"
                            placeholder="11-50"
                        />
                        <InputError :message="form.errors.team_size" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="address_line">Address</Label>
                        <Input
                            id="address_line"
                            v-model="form.address_line"
                            type="text"
                            autocomplete="street-address"
                            placeholder="100 Product Avenue"
                        />
                        <InputError :message="form.errors.address_line" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="city">City</Label>
                        <Input
                            id="city"
                            v-model="form.city"
                            type="text"
                            autocomplete="address-level2"
                            placeholder="Austin"
                        />
                        <InputError :message="form.errors.city" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="state">State</Label>
                        <Input
                            id="state"
                            v-model="form.state"
                            type="text"
                            autocomplete="address-level1"
                            placeholder="Texas"
                        />
                        <InputError :message="form.errors.state" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="country">Country</Label>
                        <Input
                            id="country"
                            v-model="form.country"
                            type="text"
                            autocomplete="country-name"
                            placeholder="United States"
                        />
                        <InputError :message="form.errors.country" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="postal_code">Postal code</Label>
                        <Input
                            id="postal_code"
                            v-model="form.postal_code"
                            type="text"
                            autocomplete="postal-code"
                            placeholder="78701"
                        />
                        <InputError :message="form.errors.postal_code" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="timezone">Timezone</Label>
                        <Input
                            id="timezone"
                            v-model="form.timezone"
                            type="text"
                            placeholder="America/Chicago"
                        />
                        <InputError :message="form.errors.timezone" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="min-h-24 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="What does this company do?"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="flex justify-end sm:col-span-2">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="create-company-button"
                        >
                            <Spinner v-if="form.processing" />
                            Create Company
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</template>
