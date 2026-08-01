<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const loggingOut = ref(false);
const menuOpen = ref(false);

/**
 * One list instead of five near-identical blocks of markup. Adding a section
 * now means adding a line here, which is also the only place the permission
 * that guards it is written down.
 *
 * The icons are inline paths rather than an icon package: five of them do not
 * justify a dependency, and a stroke path is readable enough on its own.
 */
const sections = [
    {
        name: 'dashboard',
        label: 'nav.dashboard',
        permission: null,
        icon: 'M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
    },
    {
        name: 'products.index',
        label: 'nav.products',
        permission: 'products.view',
        icon: 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
    },
    {
        name: 'media.index',
        label: 'nav.media',
        permission: 'media.view',
        icon: 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25z',
    },
    {
        name: 'users.index',
        label: 'nav.users',
        permission: 'users.view',
        icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    },
    {
        name: 'activity.index',
        label: 'nav.activity',
        permission: 'activity.view',
        icon: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
    },
];

const visibleSections = computed(() => sections.filter((section) => !section.permission || auth.can(section.permission)));

// On a phone the sidebar covers the page, so leaving it open after a tap would
// hide the very thing the tap asked for.
watch(() => route.fullPath, () => (menuOpen.value = false));

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
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-neutral-200 bg-white
                   transition-transform duration-200 lg:translate-x-0
                   dark:border-neutral-800 dark:bg-neutral-900"
            :class="menuOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <RouterLink
                :to="{ name: 'dashboard' }"
                class="flex h-14 shrink-0 items-center border-b border-neutral-200 px-5 text-sm font-semibold
                       tracking-tight dark:border-neutral-800"
            >
                {{ t('app.name') }}
            </RouterLink>

            <nav class="flex flex-1 flex-col gap-1 overflow-y-auto p-3">
                <RouterLink
                    v-for="section in visibleSections"
                    :key="section.name"
                    :to="{ name: section.name }"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-neutral-600 transition
                           hover:bg-neutral-100 hover:text-neutral-900
                           dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
                    active-class="bg-neutral-100 font-medium text-neutral-900
                                  dark:bg-neutral-800 dark:text-neutral-100"
                >
                    <svg
                        class="size-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="section.icon" />
                    </svg>
                    {{ t(section.label) }}
                </RouterLink>
            </nav>
        </aside>

        <!-- Tapping away closes the drawer, which is what everyone tries first. -->
        <div v-if="menuOpen" class="fixed inset-0 z-30 bg-neutral-950/50 lg:hidden" @click="menuOpen = false" />

        <div class="lg:pl-60">
            <header
                class="sticky top-0 z-20 flex h-14 items-center gap-4 border-b border-neutral-200 bg-white px-4
                       sm:px-6 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <button
                    type="button"
                    class="-ml-1 rounded-lg p-2 transition hover:bg-neutral-100 lg:hidden dark:hover:bg-neutral-800"
                    :aria-label="t('nav.menu')"
                    :aria-expanded="menuOpen"
                    @click="menuOpen = !menuOpen"
                >
                    <svg
                        class="size-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="ml-auto flex items-center gap-4">
                    <LocaleSwitcher />

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
                        {{ loggingOut ? t('nav.signingOut') : t('nav.signOut') }}
                    </button>
                </div>
            </header>

            <div
                v-if="auth.isAuthenticated && !auth.isVerified"
                class="border-b border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950"
            >
                <p class="px-6 py-2 text-sm text-amber-800 dark:text-amber-300">
                    {{ t('auth.verify.banner') }}
                    <RouterLink :to="{ name: 'verification.notice' }" class="underline underline-offset-4">
                        {{ t('auth.verify.bannerAction') }}
                    </RouterLink>
                </p>
            </div>

            <main class="mx-auto max-w-5xl px-6 py-10">
                <slot />
            </main>
        </div>
    </div>
</template>
