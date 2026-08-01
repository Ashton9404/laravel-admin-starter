<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { formatBytes, useMedia } from '@/composables/useMedia';
import AppLayout from '@/layouts/AppLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import PaginationBar from '@/components/PaginationBar.vue';
import TextInput from '@/components/TextInput.vue';

const auth = useAuthStore();
const { t, locale } = useI18n();
const { items, meta, loading, uploading, error, load, upload, remove } = useMedia();

const search = ref('');
const fileInput = ref(null);
const dragging = ref(false);
const preview = ref(null);
const deleting = ref(null);
const copied = ref(null);

let timer = null;

function onSearch() {
    clearTimeout(timer);
    timer = setTimeout(() => load({ search: search.value }), 300);
}

onMounted(() => load());

async function onFiles(files) {
    await upload(Array.from(files ?? []));
}

function onDrop(event) {
    dragging.value = false;
    onFiles(event.dataTransfer?.files);
}

async function copyUrl(item) {
    // Absolute, because the URL is going to be pasted somewhere else entirely.
    await navigator.clipboard.writeText(new URL(item.url, window.location.origin).href);
    copied.value = item.id;
    setTimeout(() => (copied.value = null), 1500);
}

async function confirmDelete() {
    await remove(deleting.value.id);
    deleting.value = null;
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString(locale.value, { dateStyle: 'medium' }) : '—';
}
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ t('media.title') }}</h1>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ t('media.subtitle', { total: meta.total }) }}
                    </p>
                </div>

                <button
                    v-if="auth.can('media.upload')"
                    type="button"
                    :disabled="uploading"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition
                           hover:bg-indigo-500 disabled:opacity-60"
                    @click="fileInput.click()"
                >
                    {{ uploading ? t('media.uploading') : t('media.upload') }}
                </button>

                <input ref="fileInput" type="file" multiple class="hidden" @change="onFiles($event.target.files)" />
            </header>

            <AlertMessage :message="error" variant="error" />

            <TextInput v-model="search" :placeholder="t('media.searchPlaceholder')" @input="onSearch" />

            <!-- Drop anywhere on the grid, not just on a small target. -->
            <div
                class="rounded-xl border-2 border-dashed p-4 transition"
                :class="
                    dragging
                        ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20'
                        : 'border-neutral-200 dark:border-neutral-800'
                "
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <p v-if="loading" class="py-8 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    {{ t('common.loading') }}
                </p>

                <p
                    v-else-if="items.length === 0"
                    class="py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                >
                    {{ t('media.dropHint') }}
                </p>

                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <figure
                        v-for="item in items"
                        :key="item.id"
                        class="overflow-hidden rounded-xl border border-neutral-200 bg-white
                               dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <button
                            v-if="item.is_image"
                            type="button"
                            class="block w-full"
                            @click="preview = item"
                        >
                            <img :src="item.url" :alt="item.name" class="h-32 w-full object-cover" />
                        </button>
                        <div
                            v-else
                            class="flex h-32 items-center justify-center bg-neutral-50 text-3xl dark:bg-neutral-950"
                        >
                            📄
                        </div>

                        <figcaption class="flex flex-col gap-1 p-3">
                            <p class="truncate text-sm font-medium" :title="item.name">{{ item.name }}</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ formatBytes(item.size) }} · {{ formatDate(item.created_at) }}
                            </p>

                            <div class="mt-1 flex gap-3 text-xs">
                                <button
                                    type="button"
                                    class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
                                    @click="copyUrl(item)"
                                >
                                    {{ copied === item.id ? t('media.copied') : t('media.copyUrl') }}
                                </button>
                                <button
                                    type="button"
                                    class="text-[#d03b3b] underline underline-offset-4"
                                    @click="deleting = item"
                                >
                                    {{ t('common.delete') }}
                                </button>
                            </div>
                        </figcaption>
                    </figure>
                </div>
            </div>

            <PaginationBar :meta="meta" @change="(page) => load({ search, page })" />
        </div>

        <ModalDialog v-if="preview" :title="preview.name" wide @close="preview = null">
            <img :src="preview.url" :alt="preview.name" class="max-h-[70vh] w-full rounded-lg object-contain" />
        </ModalDialog>

        <ModalDialog v-if="deleting" :title="t('media.deleteDialog.title')" @close="deleting = null">
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ t('media.deleteDialog.body', { name: deleting.name }) }}
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
                    class="rounded-lg bg-[#d03b3b] px-4 py-2 text-sm font-medium text-white transition hover:opacity-90"
                    @click="confirmDelete"
                >
                    {{ t('common.delete') }}
                </button>
            </div>
        </ModalDialog>
    </AppLayout>
</template>
