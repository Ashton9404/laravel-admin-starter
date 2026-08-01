<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from '@/bootstrap';
import { usePageMeta } from '@/composables/usePageMeta';
import PublicLayout from '@/layouts/PublicLayout.vue';

const props = defineProps({
    slug: { type: String, required: true },
});

const { t, locale } = useI18n();

const product = ref(null);
const loading = ref(true);
const missing = ref(false);

usePageMeta(
    () => product.value?.name,
    () => product.value?.summary,
);

async function load() {
    loading.value = true;
    missing.value = false;

    try {
        const { data } = await axios.get(`/api/public/products/${encodeURIComponent(props.slug)}`);
        product.value = data.data;
    } catch (exception) {
        // A draft and a slug that never existed both arrive as 404, which is
        // the point: the page cannot tell the visitor which one it was.
        if (exception.response?.status === 404) {
            missing.value = true;
        } else {
            throw exception;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(load);

watch(() => [props.slug, locale.value], load);

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString(locale.value, { dateStyle: 'long' }) : '';
}
</script>

<template>
    <PublicLayout>
        <p v-if="loading" class="py-16 text-center text-sm text-neutral-500 dark:text-neutral-400">
            {{ t('common.loading') }}
        </p>

        <div v-else-if="missing" class="flex flex-col items-start gap-4 py-16">
            <h1 class="text-3xl font-semibold tracking-tight">{{ t('public.product.missingTitle') }}</h1>
            <p class="text-neutral-600 dark:text-neutral-400">{{ t('public.product.missingBody') }}</p>
            <RouterLink
                :to="{ name: 'public.products' }"
                class="text-sm text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
            >
                {{ t('public.product.backToList') }}
            </RouterLink>
        </div>

        <article v-else-if="product" class="flex flex-col gap-8">
            <RouterLink
                :to="{ name: 'public.products' }"
                class="text-sm text-neutral-500 underline underline-offset-4 dark:text-neutral-400"
            >
                ← {{ t('public.product.backToList') }}
            </RouterLink>

            <header class="flex flex-col gap-3">
                <h1 class="text-4xl font-semibold tracking-tight">{{ product.name }}</h1>
                <p v-if="product.summary" class="text-lg text-neutral-600 dark:text-neutral-400">
                    {{ product.summary }}
                </p>
                <p v-if="product.published_at" class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ formatDate(product.published_at) }}
                </p>
            </header>

            <img
                v-if="product.cover_url"
                :src="product.cover_url"
                :alt="product.name"
                class="w-full rounded-xl object-cover"
            />

            <!-- v-html is safe here only because App\Support\RichText sanitised
                 this on the way into the database. Sanitising on write is what
                 buys this line; without it this is a stored-XSS sink. -->
            <div v-if="product.body" class="prose-content" v-html="product.body" />
        </article>
    </PublicLayout>
</template>

<style scoped>
/*
 * Typography for editor output. Written by hand rather than pulling in the
 * typography plugin: the sanitiser's allow-list is 17 elements long, so this is
 * a bounded list, and every rule here corresponds to something the editor can
 * actually produce.
 */
.prose-content :deep(p),
.prose-content :deep(ul),
.prose-content :deep(ol),
.prose-content :deep(blockquote),
.prose-content :deep(pre) {
    margin-bottom: 1rem;
    line-height: 1.75;
}

.prose-content :deep(h2),
.prose-content :deep(h3),
.prose-content :deep(h4) {
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    font-weight: 600;
    line-height: 1.3;
}

.prose-content :deep(h2) {
    font-size: 1.5rem;
}

.prose-content :deep(h3) {
    font-size: 1.25rem;
}

.prose-content :deep(h4) {
    font-size: 1.125rem;
}

.prose-content :deep(ul),
.prose-content :deep(ol) {
    padding-left: 1.5rem;
}

.prose-content :deep(ul) {
    list-style: disc;
}

.prose-content :deep(ol) {
    list-style: decimal;
}

.prose-content :deep(li) {
    margin-bottom: 0.25rem;
}

.prose-content :deep(a) {
    color: oklch(51.1% 0.262 276.966);
    text-decoration: underline;
    text-underline-offset: 4px;
}

.prose-content :deep(blockquote) {
    border-left: 3px solid currentColor;
    padding-left: 1rem;
    opacity: 0.8;
}

.prose-content :deep(pre) {
    overflow-x: auto;
    border-radius: 0.5rem;
    background: oklch(96.7% 0.001 286.375);
    padding: 1rem;
    font-size: 0.875rem;
}

.prose-content :deep(code) {
    font-size: 0.875em;
}

.prose-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}

.prose-content :deep(hr) {
    margin: 2rem 0;
    border-color: oklch(92% 0.004 286.32);
}

@media (prefers-color-scheme: dark) {
    .prose-content :deep(a) {
        color: oklch(78.5% 0.132 281.288);
    }

    .prose-content :deep(pre) {
        background: oklch(21% 0.006 285.885);
    }

    .prose-content :deep(hr) {
        border-color: oklch(27.4% 0.006 286.033);
    }
}
</style>
