<script setup>
import { useI18n } from 'vue-i18n';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});

const { t } = useI18n();
</script>

<template>
    <main
        class="flex min-h-screen items-center justify-center bg-neutral-50 px-6 py-12 text-neutral-900
               dark:bg-neutral-950 dark:text-neutral-100"
    >
        <div class="w-full max-w-sm">
            <div class="flex items-start justify-between gap-4">
                <p class="text-xs font-medium tracking-widest text-indigo-600 uppercase dark:text-indigo-400">
                    {{ t('app.name') }}
                </p>

                <LocaleSwitcher />
            </div>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ title }}</h1>

            <p v-if="subtitle" class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                {{ subtitle }}
            </p>

            <div
                class="mt-6 rounded-xl border border-neutral-200 bg-white p-6
                       dark:border-neutral-800 dark:bg-neutral-900"
            >
                <slot />
            </div>

            <p v-if="$slots.footer" class="mt-4 text-center text-sm text-neutral-600 dark:text-neutral-400">
                <slot name="footer" />
            </p>
        </div>
    </main>
</template>
