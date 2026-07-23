<script setup lang="ts">
defineProps<{
    open: boolean;
    title?: string;
    description?: string;
}>();

const emit = defineEmits<{
    close: [];
}>();
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
            role="presentation"
            @click.self="emit('close')"
        >
            <section
                role="dialog"
                aria-modal="true"
                :aria-label="title"
                class="bg-background text-foreground w-full max-w-lg rounded-lg border p-6 shadow-lg"
            >
                <header v-if="title || description" class="mb-4 space-y-1">
                    <h2 v-if="title" class="text-lg font-semibold">
                        {{ title }}
                    </h2>
                    <p v-if="description" class="text-muted-foreground text-sm">
                        {{ description }}
                    </p>
                </header>

                <slot />
            </section>
        </div>
    </Teleport>
</template>
