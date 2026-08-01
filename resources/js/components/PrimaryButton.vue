<script setup>
defineProps({
    loading: { type: Boolean, default: false },
});
</script>

<template>
    <button
        type="submit"
        :disabled="loading"
        class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2
               text-sm font-medium text-white transition
               hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/40 focus:outline-none
               disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
