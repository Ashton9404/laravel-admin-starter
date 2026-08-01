<script setup>
const model = defineModel({ type: String, default: '' });

defineProps({
    invalid: { type: Boolean, default: false },
});
</script>

<template>
    <input
        v-model="model"
        :aria-invalid="invalid"
        class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition outline-none
               bg-white text-neutral-900 placeholder:text-neutral-400
               focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500
               dark:bg-neutral-900 dark:text-neutral-100 dark:placeholder:text-neutral-500"
        :class="
            invalid
                ? 'border-red-500 dark:border-red-500'
                : 'border-neutral-300 dark:border-neutral-700'
        "
    />
</template>
