<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMedia } from '@/composables/useMedia';
import ModalDialog from '@/components/ModalDialog.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import TextInput from '@/components/TextInput.vue';

const emit = defineEmits(['close', 'select']);

const { t } = useI18n();
const { items, loading, uploading, error, load, upload } = useMedia({ imagesOnly: true });

const search = ref('');
const fileInput = ref(null);

let timer = null;

function onSearch() {
    clearTimeout(timer);
    timer = setTimeout(() => load({ search: search.value }), 300);
}

onMounted(() => load());

async function onFiles(event) {
    const uploaded = await upload(Array.from(event.target.files ?? []));
    event.target.value = '';

    // Picking one file almost always means "use this one" — save the extra click.
    if (uploaded.length === 1) {
        emit('select', uploaded[0]);
    }
}
</script>

<template>
    <ModalDialog :title="t('media.picker.title')" wide @close="emit('close')">
        <div class="flex flex-col gap-4">
            <AlertMessage :message="error" variant="error" />

            <div class="flex flex-wrap items-center gap-3">
                <TextInput
                    v-model="search"
                    class="flex-1"
                    :placeholder="t('media.searchPlaceholder')"
                    @input="onSearch"
                />

                <button
                    type="button"
                    :disabled="uploading"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition
                           hover:bg-indigo-500 disabled:opacity-60"
                    @click="fileInput.click()"
                >
                    {{ uploading ? t('media.uploading') : t('media.upload') }}
                </button>

                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="onFiles"
                />
            </div>

            <p v-if="loading" class="text-sm text-neutral-500 dark:text-neutral-400">{{ t('common.loading') }}</p>

            <p
                v-else-if="items.length === 0"
                class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-400"
            >
                {{ t('media.empty') }}
            </p>

            <div v-else class="grid max-h-96 grid-cols-3 gap-3 overflow-y-auto sm:grid-cols-4">
                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    class="group overflow-hidden rounded-lg border border-neutral-200 text-left transition
                           hover:border-indigo-500 dark:border-neutral-800"
                    @click="emit('select', item)"
                >
                    <img :src="item.url" :alt="item.name" class="h-24 w-full object-cover" />
                    <p class="truncate px-2 py-1.5 text-xs text-neutral-600 dark:text-neutral-400">
                        {{ item.name }}
                    </p>
                </button>
            </div>
        </div>
    </ModalDialog>
</template>
