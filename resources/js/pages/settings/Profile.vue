<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Building2, Pencil, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import DeleteUser from '@/Components/DeleteUser.vue';
import Heading from '@/Components/Heading.vue';
import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/UI/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/Components/UI/dialog';
import { Input } from '@/Components/UI/input';
import { Label } from '@/Components/UI/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { User } from '@/types/auth';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const isProfileOpen = ref(false);
const isCompanyOpen = ref(false);

const user = computed<User>(() => {
    const authUser = page.props.auth.user;

    if (!authUser) {
        throw new Error('Authenticated user is required for profile settings.');
    }

    return authUser;
});

const company = computed(() => user.value.company);
const canUpdateCompany = computed(() => Boolean(page.props.canUpdateCompany));
const companyRole = computed(() => {
    const role = user.value.company_membership?.role ?? user.value.role;

    return role
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
});

const profileForm = useForm({
    name: user.value.name,
    email: user.value.email,
});
const companyForm = useForm({
    name: company.value?.name ?? '',
    email: company.value?.email ?? '',
    phone: company.value?.phone ?? '',
    website: company.value?.website ?? '',
    industry: company.value?.industry ?? '',
    team_size: company.value?.team_size ?? '',
    address_line: company.value?.address_line ?? '',
    city: company.value?.city ?? '',
    state: company.value?.state ?? '',
    country: company.value?.country ?? '',
    postal_code: company.value?.postal_code ?? '',
    timezone: company.value?.timezone ?? '',
    description: company.value?.description ?? '',
});

const resetProfileForm = () => {
    profileForm.clearErrors();
    profileForm.name = user.value.name;
    profileForm.email = user.value.email;
};

const resetCompanyForm = () => {
    companyForm.clearErrors();
    companyForm.name = company.value?.name ?? '';
    companyForm.email = company.value?.email ?? '';
    companyForm.phone = company.value?.phone ?? '';
    companyForm.website = company.value?.website ?? '';
    companyForm.industry = company.value?.industry ?? '';
    companyForm.team_size = company.value?.team_size ?? '';
    companyForm.address_line = company.value?.address_line ?? '';
    companyForm.city = company.value?.city ?? '';
    companyForm.state = company.value?.state ?? '';
    companyForm.country = company.value?.country ?? '';
    companyForm.postal_code = company.value?.postal_code ?? '';
    companyForm.timezone = company.value?.timezone ?? '';
    companyForm.description = company.value?.description ?? '';
};

const updateProfileOpen = (open: boolean) => {
    isProfileOpen.value = open;

    if (open) {
        resetProfileForm();
    }
};

const updateCompanyOpen = (open: boolean) => {
    isCompanyOpen.value = open;

    if (open) {
        resetCompanyForm();
    }
};

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            isProfileOpen.value = false;
        },
    });
};

const updateCompany = () => {
    companyForm.patch(route('company.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            isCompanyOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <section
            class="rounded-lg border border-border bg-card p-4 text-sm shadow-sm"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <UserRound class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <Heading
                            variant="small"
                            title="Profile"
                            description="Your account details"
                        />

                        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Name
                                </dt>
                                <dd class="truncate font-medium">
                                    {{ user.name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Email
                                </dt>
                                <dd class="truncate font-medium">
                                    {{ user.email }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    @click="isProfileOpen = true"
                >
                    <Pencil class="size-4" />
                    Edit Profile
                </Button>
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>
        </section>

        <section
            class="rounded-lg border border-border bg-card p-4 text-sm shadow-sm"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                    >
                        <Building2 class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <Heading
                            variant="small"
                            title="Company"
                            description="Your current company membership"
                        />

                        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Company
                                </dt>
                                <dd class="truncate font-medium">
                                    {{ company?.name ?? 'No active company' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Role
                                </dt>
                                <dd class="font-medium">{{ companyRole }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <Button
                    v-if="canUpdateCompany && company"
                    type="button"
                    variant="outline"
                    @click="isCompanyOpen = true"
                >
                    <Pencil class="size-4" />
                    Edit Company
                </Button>
            </div>
        </section>
    </div>

    <Dialog :open="isProfileOpen" @update:open="updateProfileOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Edit Profile</DialogTitle>
                <DialogDescription>
                    Update your name and email address.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="updateProfile">
                <div class="grid gap-2">
                    <Label for="profile_name">Name</Label>
                    <Input
                        id="profile_name"
                        v-model="profileForm.name"
                        type="text"
                        required
                        autocomplete="name"
                        placeholder="Full name"
                        :aria-invalid="!!profileForm.errors.name"
                    />
                    <InputError :message="profileForm.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="profile_email">Email address</Label>
                    <Input
                        id="profile_email"
                        v-model="profileForm.email"
                        type="email"
                        required
                        autocomplete="username"
                        placeholder="Email address"
                        :aria-invalid="!!profileForm.errors.email"
                    />
                    <InputError :message="profileForm.errors.email" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isProfileOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="profileForm.processing"
                        data-test="update-profile-button"
                    >
                        Save Profile
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog :open="isCompanyOpen" @update:open="updateCompanyOpen">
        <DialogScrollContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Edit Company</DialogTitle>
                <DialogDescription>
                    Update the company details shown across Riraa.
                </DialogDescription>
            </DialogHeader>

            <form
                class="grid max-h-[70vh] gap-4 overflow-y-auto pr-1 sm:grid-cols-2"
                @submit.prevent="updateCompany"
            >
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="company_name">Company name</Label>
                    <Input
                        id="company_name"
                        v-model="companyForm.name"
                        type="text"
                        required
                        autocomplete="organization"
                        placeholder="Riraa"
                        :aria-invalid="!!companyForm.errors.name"
                    />
                    <InputError :message="companyForm.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_email">Company email</Label>
                    <Input
                        id="company_email"
                        v-model="companyForm.email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="hello@riraa.com"
                        :aria-invalid="!!companyForm.errors.email"
                    />
                    <InputError :message="companyForm.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_phone">Phone</Label>
                    <Input
                        id="company_phone"
                        v-model="companyForm.phone"
                        type="tel"
                        autocomplete="tel"
                        placeholder="+1 555 0100"
                        :aria-invalid="!!companyForm.errors.phone"
                    />
                    <InputError :message="companyForm.errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_website">Website</Label>
                    <Input
                        id="company_website"
                        v-model="companyForm.website"
                        type="url"
                        autocomplete="url"
                        placeholder="https://riraa.com"
                        :aria-invalid="!!companyForm.errors.website"
                    />
                    <InputError :message="companyForm.errors.website" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_industry">Industry</Label>
                    <Input
                        id="company_industry"
                        v-model="companyForm.industry"
                        type="text"
                        placeholder="Project management"
                        :aria-invalid="!!companyForm.errors.industry"
                    />
                    <InputError :message="companyForm.errors.industry" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_team_size">Team size</Label>
                    <Input
                        id="company_team_size"
                        v-model="companyForm.team_size"
                        type="text"
                        placeholder="11-50"
                        :aria-invalid="!!companyForm.errors.team_size"
                    />
                    <InputError :message="companyForm.errors.team_size" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_timezone">Timezone</Label>
                    <Input
                        id="company_timezone"
                        v-model="companyForm.timezone"
                        type="text"
                        placeholder="America/Chicago"
                        :aria-invalid="!!companyForm.errors.timezone"
                    />
                    <InputError :message="companyForm.errors.timezone" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="company_address">Address</Label>
                    <Input
                        id="company_address"
                        v-model="companyForm.address_line"
                        type="text"
                        autocomplete="street-address"
                        placeholder="100 Product Avenue"
                        :aria-invalid="!!companyForm.errors.address_line"
                    />
                    <InputError :message="companyForm.errors.address_line" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_city">City</Label>
                    <Input
                        id="company_city"
                        v-model="companyForm.city"
                        type="text"
                        autocomplete="address-level2"
                        placeholder="Austin"
                        :aria-invalid="!!companyForm.errors.city"
                    />
                    <InputError :message="companyForm.errors.city" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_state">State</Label>
                    <Input
                        id="company_state"
                        v-model="companyForm.state"
                        type="text"
                        autocomplete="address-level1"
                        placeholder="Texas"
                        :aria-invalid="!!companyForm.errors.state"
                    />
                    <InputError :message="companyForm.errors.state" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_country">Country</Label>
                    <Input
                        id="company_country"
                        v-model="companyForm.country"
                        type="text"
                        autocomplete="country-name"
                        placeholder="United States"
                        :aria-invalid="!!companyForm.errors.country"
                    />
                    <InputError :message="companyForm.errors.country" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_postal_code">Postal code</Label>
                    <Input
                        id="company_postal_code"
                        v-model="companyForm.postal_code"
                        type="text"
                        autocomplete="postal-code"
                        placeholder="78701"
                        :aria-invalid="!!companyForm.errors.postal_code"
                    />
                    <InputError :message="companyForm.errors.postal_code" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="company_description">Description</Label>
                    <textarea
                        id="company_description"
                        v-model="companyForm.description"
                        class="min-h-24 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        placeholder="What does this company do?"
                        :aria-invalid="!!companyForm.errors.description"
                    />
                    <InputError :message="companyForm.errors.description" />
                </div>

                <DialogFooter class="sm:col-span-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isCompanyOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="companyForm.processing"
                        data-test="update-company-button"
                    >
                        Save Company
                    </Button>
                </DialogFooter>
            </form>
        </DialogScrollContent>
    </Dialog>

    <DeleteUser />
</template>
