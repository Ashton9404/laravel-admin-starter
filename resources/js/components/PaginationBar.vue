<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    meta: { type: Object, required: true },
});

const emit = defineEmits(['change']);
const { t } = useI18n();

function go(page) {
    emit('change', page);
}
</script>

<template>
    <nav v-if="meta.last_page > 1" class="flex items-center justify-between gap-4" aria-label="Pagination">
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
            {{ t('users.pagination.showing', { from: meta.from ?? 0, to: meta.to ?? 0, total: meta.total }) }}
        </p>

        <div class="flex items-center gap-2">
            <button
                type="button"
                :disabled="meta.current_page <= 1"
                class="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm transition
                       hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40
                       dark:border-neutral-700 dark:hover:bg-neutral-800"
                @click="go(meta.current_page - 1)"
            >
                {{ t('users.pagination.previous') }}
            </button>

            <span class="text-sm tabular-nums text-neutral-500 dark:text-neutral-400">
                {{ meta.current_page }} / {{ meta.last_page }}
            </span>

            <button
                type="button"
                :disabled="meta.current_page >= meta.last_page"
                class="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm transition
                       hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40
                       dark:border-neutral-700 dark:hover:bg-neutral-800"
                @click="go(meta.current_page + 1)"
            >
                {{ t('users.pagination.next') }}
            </button>
        </div>
    </nav>
</template>
