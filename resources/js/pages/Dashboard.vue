<script setup>
import { onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from '@/bootstrap';
import AppLayout from '@/layouts/AppLayout.vue';

const auth = useAuthStore();
const health = ref('checking…');

const stack = [
    'Laravel 13',
    'PHP 8.4',
    'Sanctum',
    'Vue 3',
    'Vite',
    'Pinia',
    'Tailwind CSS 4',
    'MySQL 8',
    'Redis',
    'Docker',
];

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/ping');
        health.value = `${data.message} · ${data.laravel} · PHP ${data.php}`;
    } catch (error) {
        health.value = `API unreachable: ${error.message}`;
    }
});
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-8">
            <header class="flex flex-col gap-2">
                <p class="text-sm font-medium tracking-widest text-indigo-600 uppercase dark:text-indigo-400">
                    Dashboard
                </p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Welcome back, {{ auth.user?.name }}.
                </h1>
                <p class="text-neutral-600 dark:text-neutral-400">
                    Signed in as {{ auth.user?.email }} · email
                    {{ auth.isVerified ? 'verified' : 'not verified yet' }}.
                </p>
            </header>

            <section
                class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <h2 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">API handshake</h2>
                <p class="mt-2 font-mono text-sm">{{ health }}</p>
            </section>

            <section class="flex flex-wrap gap-2">
                <span
                    v-for="item in stack"
                    :key="item"
                    class="rounded-full border border-neutral-200 px-3 py-1 text-sm dark:border-neutral-800"
                >
                    {{ item }}
                </span>
            </section>
        </div>
    </AppLayout>
</template>
