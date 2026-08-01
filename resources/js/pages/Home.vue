<script setup>
import { onMounted, ref } from 'vue';
import axios from '@/bootstrap';

const health = ref('checking…');

const stack = [
    'Laravel 13',
    'PHP 8.4',
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
    <main class="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
        <div class="mx-auto flex max-w-3xl flex-col gap-8 px-6 py-24">
            <header class="flex flex-col gap-3">
                <p class="text-sm font-medium uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                    Laravel Admin Starter
                </p>
                <h1 class="text-4xl font-semibold tracking-tight">
                    The scaffolding is alive.
                </h1>
                <p class="text-neutral-600 dark:text-neutral-400">
                    Laravel serves the shell, Vue Router owns the URL, and Vite is hot-reloading this file.
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
    </main>
</template>
