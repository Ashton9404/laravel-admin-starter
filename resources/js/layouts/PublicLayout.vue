<script setup>
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';

const auth = useAuthStore();
const { t } = useI18n();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
        <header class="border-b border-neutral-200 dark:border-neutral-800">
            <nav class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-4">
                <div class="flex items-center gap-6">
                    <RouterLink :to="{ name: 'home' }" class="text-base font-semibold tracking-tight">
                        {{ t('app.name') }}
                    </RouterLink>

                    <RouterLink
                        :to="{ name: 'public.products' }"
                        class="text-sm text-neutral-600 transition hover:text-neutral-900
                               dark:text-neutral-400 dark:hover:text-neutral-100"
                    >
                        {{ t('nav.products') }}
                    </RouterLink>
                </div>

                <div class="flex items-center gap-4">
                    <LocaleSwitcher />

                    <!-- Signed-in staff get a way back into the panel; everyone
                         else gets the sign-in link. No reason to show both. -->
                    <RouterLink
                        v-if="auth.isAuthenticated"
                        :to="{ name: 'dashboard' }"
                        class="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm transition
                               hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800"
                    >
                        {{ t('public.adminPanel') }}
                    </RouterLink>
                    <RouterLink
                        v-else
                        :to="{ name: 'login' }"
                        class="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm transition
                               hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800"
                    >
                        {{ t('auth.login.submit') }}
                    </RouterLink>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-6 py-12">
            <slot />
        </main>

        <footer class="border-t border-neutral-200 dark:border-neutral-800">
            <p class="mx-auto max-w-5xl px-6 py-6 text-sm text-neutral-500 dark:text-neutral-400">
                {{ t('public.footer') }}
            </p>
        </footer>
    </div>
</template>
