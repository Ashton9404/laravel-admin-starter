<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios, { errorMessage } from '@/bootstrap';
import { usePageMeta } from '@/composables/usePageMeta';
import PublicLayout from '@/layouts/PublicLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import PaginationBar from '@/components/PaginationBar.vue';

const { t, locale } = useI18n();

const products = ref([]);
const meta = ref({});
const loading = ref(true);
const error = ref('');

usePageMeta(
    () => t('public.products.title'),
    () => t('public.products.subtitle'),
);

async function load(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/api/public/products', { params: { page } });
        products.value = data.data;
        meta.value = data.meta;
    } catch (exception) {
        error.value = errorMessage(exception, t('public.products.loadFailed'));
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());

// The API returns the translation for whatever Accept-Language it was sent, so
// switching language has to refetch rather than just re-render.
watch(locale, () => load(meta.value.current_page ?? 1));
</script>

<template>
    <PublicLayout>
        <div class="flex flex-col gap-8">
            <header class="flex flex-col gap-2">
                <h1 class="text-4xl font-semibold tracking-tight">{{ t('public.products.title') }}</h1>
                <p class="text-neutral-600 dark:text-neutral-400">{{ t('public.products.subtitle') }}</p>
            </header>

            <AlertMessage :message="error" variant="error" />

            <p v-if="loading" class="py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                {{ t('common.loading') }}
            </p>

            <p
                v-else-if="products.length === 0"
                class="py-16 text-center text-sm text-neutral-500 dark:text-neutral-400"
            >
                {{ t('public.products.empty') }}
            </p>

            <ul v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="product in products" :key="product.slug">
                    <RouterLink
                        :to="{ name: 'public.product', params: { slug: product.slug } }"
                        class="group flex h-full flex-col overflow-hidden rounded-xl border border-neutral-200
                               transition hover:border-neutral-300 hover:shadow-sm
                               dark:border-neutral-800 dark:hover:border-neutral-700"
                    >
                        <img
                            v-if="product.cover_url"
                            :src="product.cover_url"
                            :alt="product.name"
                            class="h-44 w-full object-cover"
                        />
                        <div
                            v-else
                            class="h-44 w-full bg-neutral-100 dark:bg-neutral-900"
                            aria-hidden="true"
                        />

                        <div class="flex flex-1 flex-col gap-2 p-5">
                            <h2 class="font-medium group-hover:underline underline-offset-4">
                                {{ product.name }}
                            </h2>
                            <p
                                v-if="product.summary"
                                class="text-sm text-neutral-600 dark:text-neutral-400"
                            >
                                {{ product.summary }}
                            </p>
                        </div>
                    </RouterLink>
                </li>
            </ul>

            <PaginationBar :meta="meta" @change="load" />
        </div>
    </PublicLayout>
</template>
