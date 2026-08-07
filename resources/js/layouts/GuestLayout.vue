<script setup lang="ts">
import RiraaMark from '@/Components/RiraaMark.vue';
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-2.5">
                    <RiraaMark />
                    <span class="text-[18px] font-semibold tracking-[-0.04em]">
                        riraa
                    </span>
                </div>
                <slot name="actions" />
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
