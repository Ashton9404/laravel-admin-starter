<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Chart, currentTheme } from '@/charts';

const { t, locale } = useI18n();

const props = defineProps({
    points: { type: Array, required: true },
    label: { type: String, default: 'Value' },
});

const canvas = ref(null);
const showTable = ref(false);
let chart = null;
let media = null;

function formatDay(iso) {
    return new Date(`${iso}T00:00:00`).toLocaleDateString(locale.value, {
        month: 'short',
        day: 'numeric',
    });
}

function config() {
    const theme = currentTheme();

    return {
        type: 'line',
        data: {
            labels: props.points.map((point) => formatDay(point.date)),
            datasets: [
                {
                    label: props.label,
                    data: props.points.map((point) => point.total),
                    borderColor: theme.series,
                    backgroundColor: theme.fill,
                    borderWidth: 2,
                    fill: true,
                    tension: 0,
                    pointRadius: 0,
                    // Hidden until hover, then an 8px marker with a 2px surface
                    // ring so it reads as separate from the line underneath.
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: theme.series,
                    pointHoverBorderColor: theme.surface,
                    pointHoverBorderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // Crosshair behaviour: the whole column is the hit target, so the
            // reader never has to land on the dot itself.
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: theme.tooltipBg,
                    titleColor: theme.tooltipText,
                    bodyColor: theme.tooltipText,
                    displayColors: false,
                    padding: 10,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: theme.axis },
                    ticks: {
                        color: theme.muted,
                        maxRotation: 0,
                        autoSkipPadding: 24,
                        font: { size: 11 },
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: theme.grid, drawTicks: false },
                    border: { display: false },
                    ticks: { color: theme.muted, precision: 0, font: { size: 11 } },
                },
            },
        },
    };
}

function render() {
    chart?.destroy();
    chart = new Chart(canvas.value, config());
}

onMounted(() => {
    render();

    // Dark mode is a selected palette, not an automatic flip, so the chart has
    // to be rebuilt when the OS preference changes.
    media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener('change', render);
});

onBeforeUnmount(() => {
    media?.removeEventListener('change', render);
    chart?.destroy();
});

// Axis tick labels are locale-formatted, so a language switch has to redraw.
watch([() => props.points, locale], render, { deep: true });
</script>

<template>
    <div class="flex flex-col gap-3">
        <!-- Fixed plot height plus room for the axis band, so the card never
             grows a nested scrollbar. -->
        <div class="h-56">
            <canvas ref="canvas" :aria-label="label" role="img" />
        </div>

        <button
            type="button"
            class="self-start text-xs text-neutral-500 underline underline-offset-4 dark:text-neutral-400"
            @click="showTable = !showTable"
        >
            {{ showTable ? t('dashboard.hideTable') : t('dashboard.viewAsTable') }}
        </button>

        <!-- The table view is the WCAG-clean twin: every value the line encodes
             is reachable without hovering or seeing colour. -->
        <div v-if="showTable" class="max-h-56 overflow-y-auto rounded-lg border border-neutral-200 dark:border-neutral-800">
            <table class="w-full text-sm">
                <caption class="sr-only">{{ label }}</caption>
                <thead class="sticky top-0 bg-neutral-50 dark:bg-neutral-900">
                    <tr class="text-left text-neutral-500 dark:text-neutral-400">
                        <th scope="col" class="px-3 py-2 font-medium">{{ t('dashboard.date') }}</th>
                        <th scope="col" class="px-3 py-2 text-right font-medium">{{ label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="point in points" :key="point.date" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-3 py-1.5 tabular-nums">{{ point.date }}</td>
                        <td class="px-3 py-1.5 text-right tabular-nums">{{ point.total }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
