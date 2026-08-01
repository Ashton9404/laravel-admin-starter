<script setup>
defineProps({
    for: { type: String, default: undefined },
});
</script>

<template>
    <label :for="$props.for" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
        <slot />
    </label>
</template>
