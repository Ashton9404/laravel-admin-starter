<script setup>
defineProps({
    message: { type: String, default: '' },
    variant: {
        type: String,
        default: 'info',
        validator: (value) => ['info', 'success', 'error'].includes(value),
    },
});

const styles = {
    info: 'border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300',
    success:
        'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
    error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
};
</script>

<template>
    <div v-if="message" class="rounded-lg border px-3 py-2 text-sm" :class="styles[variant]" role="status">
        {{ message }}
    </div>
</template>
