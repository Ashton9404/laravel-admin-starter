<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    entries: { type: Array, default: () => [] },
    // The dashboard panel wants the short version; the log page wants it all.
    detailed: { type: Boolean, default: false },
});

const { t, locale } = useI18n();

/**
 * Every event gets its own sentence rather than a shared "{causer} {verb}
 * {subject}" template. Word order and grammar differ between the languages this
 * app ships in, and a template assembled from fragments only reads well in the
 * one it was written for.
 */
function sentence(entry) {
    return t(`activity.events.${entry.event}`, {
        causer: entry.causer.name ?? t('activity.someone'),
        subject: entry.subject?.label ?? t('activity.aRecord'),
        email: entry.properties?.email ?? '',
        count: entry.properties?.count ?? 0,
    });
}

function changedFields(entry) {
    return Object.keys(entry.properties?.changed ?? {});
}

function formatTime(iso) {
    return iso ? new Date(iso).toLocaleString(locale.value, { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}
</script>

<template>
    <p v-if="entries.length === 0" class="py-8 text-center text-sm text-neutral-500 dark:text-neutral-400">
        {{ t('activity.empty') }}
    </p>

    <ul v-else class="flex flex-col">
        <li
            v-for="entry in entries"
            :key="entry.id"
            class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t border-neutral-100
                   py-2.5 first:border-t-0 dark:border-neutral-800"
        >
            <div class="flex min-w-0 flex-col gap-0.5">
                <p class="text-sm">
                    <!-- Named, not coloured. A failed sign-in has to be legible
                         to someone who cannot tell red from grey. -->
                    <span
                        class="mr-2 rounded px-1.5 py-0.5 text-xs font-medium
                               bg-neutral-100 text-neutral-700
                               dark:bg-neutral-800 dark:text-neutral-300"
                    >
                        {{ t(`activity.labels.${entry.event}`) }}
                    </span>
                    {{ sentence(entry) }}
                </p>

                <p
                    v-if="detailed && changedFields(entry).length"
                    class="text-xs text-neutral-500 dark:text-neutral-400"
                >
                    {{ t('activity.changedFields', { fields: changedFields(entry).join(', ') }) }}
                </p>
            </div>

            <p class="shrink-0 text-xs text-neutral-500 tabular-nums dark:text-neutral-400">
                {{ formatTime(entry.created_at) }}
                <span v-if="detailed && entry.ip_address"> · {{ entry.ip_address }}</span>
            </p>
        </li>
    </ul>
</template>
