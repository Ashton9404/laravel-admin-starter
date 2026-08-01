<script setup>
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const loggingOut = ref(false);

async function logout() {
    loggingOut.value = true;

    try {
        await auth.logout();
        await router.push({ name: 'login' });
    } finally {
        loggingOut.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
        <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <nav class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-3">
                <div class="flex items-center gap-6">
                    <RouterLink :to="{ name: 'dashboard' }" class="text-sm font-semibold tracking-tight">
                        Laravel Admin Starter
                    </RouterLink>

                    <RouterLink
                        v-if="auth.can('users.view')"
                        :to="{ name: 'users.index' }"
                        class="text-sm text-neutral-600 transition hover:text-neutral-900
                               dark:text-neutral-400 dark:hover:text-neutral-100"
                        active-class="text-neutral-900 dark:text-neutral-100"
                    >
                        Users
                    </RouterLink>
                </div>

                <div class="flex items-center gap-4">
                    <span class="hidden text-sm text-neutral-600 sm:inline dark:text-neutral-400">
                        {{ auth.user?.name }}
                    </span>

                    <button
                        type="button"
                        :disabled="loggingOut"
                        class="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm transition
                               hover:bg-neutral-100 disabled:opacity-60
                               dark:border-neutral-700 dark:hover:bg-neutral-800"
                        @click="logout"
                    >
                        {{ loggingOut ? 'Signing out…' : 'Sign out' }}
                    </button>
                </div>
            </nav>
        </header>

        <div
            v-if="auth.isAuthenticated && !auth.isVerified"
            class="border-b border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950"
        >
            <p class="mx-auto max-w-5xl px-6 py-2 text-sm text-amber-800 dark:text-amber-300">
                Your email address is not verified.
                <RouterLink :to="{ name: 'verification.notice' }" class="underline underline-offset-4">
                    Verify it now
                </RouterLink>
            </p>
        </div>

        <main class="mx-auto max-w-5xl px-6 py-10">
            <slot />
        </main>
    </div>
</template>
