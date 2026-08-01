<script setup>
import { onBeforeUnmount, onMounted } from 'vue';

defineProps({
    title: { type: String, required: true },
});

const emit = defineEmits(['close']);

function onKeydown(event) {
    if (event.key === 'Escape') {
        emit('close');
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.body.classList.add('overflow-hidden');
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="emit('close')" />

        <div
            class="relative w-full max-w-md rounded-xl border border-neutral-200 bg-white p-6 shadow-xl
                   dark:border-neutral-800 dark:bg-neutral-900"
            role="dialog"
            aria-modal="true"
        >
            <h2 class="text-lg font-semibold tracking-tight">{{ title }}</h2>

            <div class="mt-4">
                <slot />
            </div>
        </div>
    </div>
</template>
