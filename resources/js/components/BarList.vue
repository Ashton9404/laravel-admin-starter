<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: { type: Array, required: true },
});

// Three or four nominal categories with long-ish names: a directly labelled
// bar list beats a canvas chart here — nothing to clip, nothing to hover for,
// and every value is readable without a tooltip.
const max = computed(() => Math.max(1, ...props.items.map((item) => item.total)));

function width(total) {
    return `${Math.max(2, Math.round((total / max.value) * 100))}%`;
}
</script>

<template>
    <ul class="flex flex-col gap-3">
        <li v-for="item in items" :key="item.name" class="flex flex-col gap-1.5">
            <div class="flex items-baseline justify-between gap-4">
                <span class="text-sm text-neutral-700 dark:text-neutral-300">{{ item.label }}</span>
                <span class="text-sm font-medium tabular-nums">{{ item.total }}</span>
            </div>

            <div class="h-2 w-full rounded-full bg-neutral-100 dark:bg-neutral-800">
                <!-- One series, one colour. Shading each bar by its own value
                     would double-encode length as hue. -->
                <div class="h-2 rounded-full bg-[#2a78d6] dark:bg-[#3987e5]" :style="{ width: width(item.total) }" />
            </div>
        </li>
    </ul>
</template>
