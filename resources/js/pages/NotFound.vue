<script setup>
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <main
        class="flex min-h-screen flex-col items-center justify-center gap-4 bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100"
    >
        <p class="text-6xl font-semibold tracking-tight">404</p>
        <p class="text-neutral-600 dark:text-neutral-400">{{ t('notFound.body') }}</p>
        <RouterLink to="/" class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400">
            {{ t('notFound.back') }}
        </RouterLink>
    </main>
</template>
