<script setup>
const model = defineModel({ type: [String, Number, null], default: '' });

defineProps({
    options: { type: Array, required: true },
});
</script>

<template>
    <select
        v-model="model"
        class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm
               transition outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/40
               dark:border-neutral-700 dark:bg-neutral-900"
    >
        <option v-for="option in options" :key="String(option.value)" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>
