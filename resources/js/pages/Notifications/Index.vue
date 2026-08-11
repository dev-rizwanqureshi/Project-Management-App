<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, LoaderCircle } from '@lucide/vue';
import axios from 'axios';
import { ref } from 'vue';
import { route } from 'ziggy-js';

import { Button } from '@/Components/UI/button';
import type { AppNotification } from '@/types';

const props = defineProps<{
    notifications: AppNotification[];
    notificationMeta: {
        total: number;
        has_more: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Notifications',
                href: route('notifications.index'),
            },
        ],
    },
});

const items = ref([...props.notifications]);
const hasMore = ref(props.notificationMeta.has_more);
const isLoadingMore = ref(false);
const loadError = ref('');

const formatDate = (value: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const loadMore = async () => {
    if (isLoadingMore.value || !hasMore.value) {
        return;
    }

    isLoadingMore.value = true;
    loadError.value = '';

    try {
        const response = await axios.get<{
            notifications: AppNotification[];
            meta: { total: number; has_more: boolean };
        }>(route('notifications.more'), {
            params: { offset: items.value.length },
        });

        items.value.push(...response.data.notifications);
        hasMore.value = response.data.meta.has_more;
    } catch {
        loadError.value =
            'Notifications could not be loaded. Please try again.';
    } finally {
        isLoadingMore.value = false;
    }
};
</script>

<template>
    <Head title="Notifications" />

    <section class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div>
            <p class="text-sm font-medium text-primary">Activity center</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                Notifications
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Review updates about your projects, tasks, and account.
            </p>
        </div>

        <div
            class="overflow-hidden rounded-lg border border-border bg-card shadow-sm"
        >
            <div
                class="flex items-center justify-between border-b border-border px-4 py-3 sm:px-5"
            >
                <h2 class="font-semibold">All notifications</h2>
                <span class="text-sm text-muted-foreground">
                    {{ notificationMeta.total }} total
                </span>
            </div>

            <div v-if="items.length" class="divide-y divide-border">
                <component
                    :is="notification.url ? Link : 'article'"
                    v-for="notification in items"
                    :key="notification.id"
                    :href="notification.url ?? undefined"
                    class="group flex items-start gap-4 px-4 py-4 transition-colors hover:bg-muted/50 sm:px-5"
                    :class="{ 'bg-primary/[0.035]': !notification.read_at }"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <Bell class="size-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-4">
                            <span class="font-medium text-foreground">
                                {{ notification.title }}
                            </span>
                            <span
                                v-if="!notification.read_at"
                                class="mt-2 size-2 shrink-0 rounded-full bg-primary"
                                aria-label="Unread"
                            />
                        </span>
                        <span
                            v-if="notification.body"
                            class="mt-1 block text-sm leading-6 text-muted-foreground"
                        >
                            {{ notification.body }}
                        </span>
                        <time
                            :datetime="notification.created_at ?? undefined"
                            class="mt-2 block text-xs text-muted-foreground"
                        >
                            {{ formatDate(notification.created_at) }}
                        </time>
                    </span>
                </component>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center px-6 py-16 text-center"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary"
                >
                    <Bell class="size-6" />
                </span>
                <h2 class="mt-4 font-semibold">No notifications yet</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    New project and account updates will appear here.
                </p>
            </div>

            <div
                v-if="hasMore || loadError"
                class="flex flex-col items-center gap-2 border-t border-border px-4 py-4"
            >
                <p v-if="loadError" class="text-sm text-destructive">
                    {{ loadError }}
                </p>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="isLoadingMore"
                    @click="loadMore"
                >
                    <LoaderCircle
                        v-if="isLoadingMore"
                        class="size-4 animate-spin"
                    />
                    {{ isLoadingMore ? 'Loading…' : 'View more' }}
                </Button>
            </div>
        </div>
    </section>
</template>
