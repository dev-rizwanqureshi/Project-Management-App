<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();

const welcomeUrl = route('welcome');
const dashboardUrl = route('dashboard');
const loginUrl = route('login');
const registerUrl = route('register');
const user = computed(() => page.props.auth?.user ?? null);
</script>

<template>
    <Head title="Welcome" />

    <main
        class="min-h-screen border-t-4 border-emerald-500 bg-background text-foreground"
    >
        <section
            class="mx-auto grid min-h-screen max-w-6xl gap-10 px-6 py-8 lg:grid-cols-[1fr_420px] lg:items-center"
        >
            <div class="space-y-8">
                <header class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            Foundation check
                        </p>
                        <h1
                            class="mt-2 text-3xl font-semibold tracking-normal sm:text-4xl"
                        >
                            Project Management App
                        </h1>
                    </div>

                    <nav class="flex items-center gap-2">
                        <Link
                            v-if="user"
                            :href="dashboardUrl"
                            class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-muted"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="loginUrl"
                                class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-muted"
                            >
                                Log in
                            </Link>
                            <Link
                                :href="registerUrl"
                                class="rounded-md bg-foreground px-3 py-2 text-sm font-medium text-background hover:opacity-90"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </header>

                <div class="space-y-4">
                    <p
                        class="max-w-2xl text-lg leading-8 text-muted-foreground"
                    >
                        Laravel, Inertia, Vue 3, TypeScript, Pinia, Ziggy,
                        Axios, and Tailwind are ready for the next build step.
                    </p>

                    <Link
                        :href="welcomeUrl"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Reload with route('welcome')
                    </Link>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Starter board</h2>
                    <span
                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800"
                    >
                        Ready
                    </span>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <section class="rounded-md border bg-background p-3">
                        <h3
                            class="mb-3 text-xs font-semibold text-muted-foreground uppercase"
                        >
                            Backlog
                        </h3>
                        <div class="space-y-2">
                            <div class="rounded-md bg-muted p-3 text-sm">
                                Database design
                            </div>
                            <div class="rounded-md bg-muted p-3 text-sm">
                                Auth policies
                            </div>
                        </div>
                    </section>

                    <section class="rounded-md border bg-background p-3">
                        <h3
                            class="mb-3 text-xs font-semibold text-muted-foreground uppercase"
                        >
                            In progress
                        </h3>
                        <div class="rounded-md bg-muted p-3 text-sm">
                            App foundation
                        </div>
                    </section>

                    <section class="rounded-md border bg-background p-3">
                        <h3
                            class="mb-3 text-xs font-semibold text-muted-foreground uppercase"
                        >
                            Done
                        </h3>
                        <div class="rounded-md bg-muted p-3 text-sm">
                            Fresh Laravel install
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </main>
</template>
