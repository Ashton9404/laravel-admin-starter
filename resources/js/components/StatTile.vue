<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    context: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ label }}</p>

        <!-- Proportional figures: tabular-nums makes standalone numbers look
             loose at display sizes. -->
        <p class="mt-1 text-3xl font-semibold tracking-tight">{{ value }}</p>

        <p v-if="context" class="mt-1 text-xs text-neutral-500 dark:text-neutral-500">
            {{ context }}
        </p>
    </div>
</template>
