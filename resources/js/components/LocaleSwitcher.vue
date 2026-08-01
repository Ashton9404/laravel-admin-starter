<script setup>
import { useI18n } from 'vue-i18n';
import { SUPPORTED_LOCALES } from '@/i18n';
import { useAuthStore } from '@/stores/auth';

const { locale, t } = useI18n();
const auth = useAuthStore();

function onChange(event) {
    auth.changeLocale(event.target.value);
}
</script>

<template>
    <label class="flex items-center gap-2">
        <span class="sr-only">{{ t('common.language') }}</span>

        <select
            :value="locale"
            class="rounded-lg border border-neutral-300 bg-white px-2 py-1.5 text-sm transition
                   outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/40
                   dark:border-neutral-700 dark:bg-neutral-900"
            @change="onChange"
        >
            <option v-for="(label, code) in SUPPORTED_LOCALES" :key="code" :value="code">
                {{ label }}
            </option>
        </select>
    </label>
</template>
