<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import axios, { errorMessage } from '@/bootstrap';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import ProductFormModal from '@/components/ProductFormModal.vue';
import SelectInput from '@/components/SelectInput.vue';
import TextInput from '@/components/TextInput.vue';

const auth = useAuthStore();
const { t, locale } = useI18n();

const products = ref([]);
const loading = ref(true);
const failure = ref('');
const status = ref('');

const filters = reactive({ search: '' });

const creating = ref(false);
const editing = ref(null);
const deleting = ref(null);
const deletingBusy = ref(false);
const savingOrder = ref(false);
const orderSaved = ref(false);

const statusOptions = computed(() => [
    { value: '', label: t('products.allStatuses') },
    { value: 'draft', label: t('products.status.draft') },
    { value: 'published', label: t('products.status.published') },
]);

// Reordering only makes sense against the full, unfiltered catalogue: dragging
// row 3 above row 1 inside a filtered view would write positions that ignore
// everything currently hidden.
const canReorder = computed(
    () => auth.can('products.update') && !filters.search && !status.value,
);

async function load() {
    loading.value = true;
    failure.value = '';

    const params = { per_page: 100 };
    if (filters.search) params.search = filters.search;
    if (status.value) params.status = status.value;

    try {
        const { data } = await axios.get('/api/products', { params });
        products.value = data.data;
    } catch (error) {
        failure.value = errorMessage(error, t('products.loadFailed'));
    } finally {
        loading.value = false;
    }
}

let searchTimer = null;
watch(
    () => filters.search,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(load, 300);
    },
);
watch(status, load);

onMounted(load);

async function persistOrder() {
    savingOrder.value = true;
    orderSaved.value = false;

    try {
        await axios.post('/api/products/reorder', { ids: products.value.map((p) => p.id) });
        orderSaved.value = true;
        setTimeout(() => (orderSaved.value = false), 2000);
    } catch (error) {
        failure.value = errorMessage(error, t('products.reorderFailed'));
        // The server rejected it, so the list on screen no longer matches what
        // is stored — refetch rather than leave a lie on the page.
        load();
    } finally {
        savingOrder.value = false;
    }
}

function onSaved() {
    creating.value = false;
    editing.value = null;
    load();
}

async function confirmDelete() {
    deletingBusy.value = true;

    try {
        await axios.delete(`/api/products/${deleting.value.id}`);
        deleting.value = null;
        load();
    } catch (error) {
        failure.value = errorMessage(error, t('products.deleteFailed'));
        deleting.value = null;
    } finally {
        deletingBusy.value = false;
    }
}

function nameOf(product) {
    const translations = product.translations ?? {};

    return translations[locale.value]?.name ?? translations.en?.name ?? product.slug;
}
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ t('products.title') }}</h1>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ t('products.subtitle') }}
                    </p>
                </div>

                <button
                    v-if="auth.can('products.create')"
                    type="button"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition
                           hover:bg-indigo-500"
                    @click="creating = true"
                >
                    {{ t('products.new') }}
                </button>
            </header>

            <AlertMessage :message="failure" variant="error" />

            <div class="grid gap-3 sm:grid-cols-3">
                <TextInput v-model="filters.search" :placeholder="t('products.searchPlaceholder')" />
                <SelectInput v-model="status" :options="statusOptions" />

                <p class="self-center text-sm text-neutral-500 dark:text-neutral-400">
                    <span v-if="savingOrder">{{ t('products.savingOrder') }}</span>
                    <span v-else-if="orderSaved" class="text-[#006300] dark:text-[#0ca30c]">
                        ✓ {{ t('products.orderSaved') }}
                    </span>
                    <span v-else-if="!canReorder">{{ t('products.reorderHint') }}</span>
                    <span v-else>{{ t('products.dragHint') }}</span>
                </p>
            </div>

            <p v-if="loading" class="text-sm text-neutral-500 dark:text-neutral-400">{{ t('common.loading') }}</p>

            <p
                v-else-if="products.length === 0"
                class="rounded-xl border border-neutral-200 bg-white px-5 py-8 text-center text-sm
                       text-neutral-500 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400"
            >
                {{ t('products.empty') }}
            </p>

            <draggable
                v-else
                v-model="products"
                item-key="id"
                handle=".drag-handle"
                :disabled="!canReorder"
                ghost-class="opacity-40"
                class="flex flex-col gap-2"
                @end="persistOrder"
            >
                <template #item="{ element: product }">
                    <div
                        class="flex items-center gap-4 rounded-xl border border-neutral-200 bg-white p-3
                               dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <span
                            class="drag-handle select-none text-lg text-neutral-400"
                            :class="canReorder ? 'cursor-grab active:cursor-grabbing' : 'cursor-not-allowed opacity-30'"
                            :title="canReorder ? t('products.dragHint') : t('products.reorderHint')"
                        >
                            ⠿
                        </span>

                        <img
                            v-if="product.cover_url"
                            :src="product.cover_url"
                            alt=""
                            class="h-12 w-16 rounded-lg object-cover"
                        />
                        <div
                            v-else
                            class="h-12 w-16 rounded-lg border border-dashed border-neutral-300 dark:border-neutral-700"
                        />

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ nameOf(product) }}</p>
                            <p class="truncate text-xs text-neutral-500 dark:text-neutral-400">/{{ product.slug }}</p>
                        </div>

                        <span
                            class="rounded-full px-2 py-0.5 text-xs"
                            :class="
                                product.published
                                    ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                            "
                        >
                            {{ product.published ? t('products.status.published') : t('products.status.draft') }}
                        </span>

                        <div class="flex gap-3 text-sm">
                            <button
                                v-if="auth.can('products.update')"
                                type="button"
                                class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
                                @click="editing = product"
                            >
                                {{ t('common.edit') }}
                            </button>
                            <button
                                v-if="auth.can('products.delete')"
                                type="button"
                                class="text-[#d03b3b] underline underline-offset-4"
                                @click="deleting = product"
                            >
                                {{ t('common.delete') }}
                            </button>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>

        <ProductFormModal v-if="creating" @close="creating = false" @saved="onSaved" />
        <ProductFormModal v-if="editing" :product="editing" @close="editing = null" @saved="onSaved" />

        <ModalDialog v-if="deleting" :title="t('products.deleteDialog.title')" @close="deleting = null">
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ t('products.deleteDialog.body', { name: nameOf(deleting) }) }}
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-neutral-300 px-4 py-2 text-sm transition
                           hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800"
                    @click="deleting = null"
                >
                    {{ t('common.cancel') }}
                </button>
                <button
                    type="button"
                    :disabled="deletingBusy"
                    class="rounded-lg bg-[#d03b3b] px-4 py-2 text-sm font-medium text-white transition
                           hover:opacity-90 disabled:opacity-60"
                    @click="confirmDelete"
                >
                    {{ deletingBusy ? t('common.deleting') : t('common.delete') }}
                </button>
            </div>
        </ModalDialog>
    </AppLayout>
</template>
