<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import RiraaMark from '@/Components/RiraaMark.vue';
import { welcome } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-primary p-10 text-primary-foreground lg:flex dark:border-r"
        >
            <Link
                :href="welcome()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <RiraaMark class="mr-3" />
                <span>riraa</span>
            </Link>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
