<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios, { errorMessage, validationErrors } from '@/bootstrap';
import { SUPPORTED_LOCALES } from '@/i18n';
import ModalDialog from '@/components/ModalDialog.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import SelectInput from '@/components/SelectInput.vue';
import TextInput from '@/components/TextInput.vue';

const props = defineProps({
    product: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const editing = props.product !== null;

const locales = Object.keys(SUPPORTED_LOCALES);
const activeLocale = ref(locales[0]);

const form = reactive({
    slug: props.product?.slug ?? '',
    status: props.product?.status ?? 'draft',
    translations: Object.fromEntries(
        locales.map((locale) => [
            locale,
            {
                name: props.product?.translations?.[locale]?.name ?? '',
                summary: props.product?.translations?.[locale]?.summary ?? '',
                body: props.product?.translations?.[locale]?.body ?? '',
            },
        ]),
    ),
});

const coverUrl = ref(props.product?.cover_url ?? null);
const errors = ref({});
const failure = ref('');
const saving = ref(false);
const uploading = ref(false);

const statusOptions = [
    { value: 'draft', label: t('products.status.draft') },
    { value: 'published', label: t('products.status.published') },
];

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Only auto-fill the slug while creating: rewriting it on an existing product
// would silently break every link already pointing at it.
function onNameInput() {
    if (!editing && activeLocale.value === locales[0]) {
        form.slug = slugify(form.translations[locales[0]].name);
    }
}

/**
 * Translations with no name are dropped rather than sent as blanks — an empty
 * row would satisfy "this product has a Chinese version" and then render as a
 * nameless entry on the public site.
 */
function payload() {
    const translations = Object.fromEntries(
        Object.entries(form.translations).filter(([, value]) => value.name.trim() !== ''),
    );

    return { slug: form.slug, status: form.status, translations };
}

async function submit() {
    saving.value = true;
    errors.value = {};
    failure.value = '';

    try {
        if (editing) {
            await axios.patch(`/api/products/${props.product.id}`, payload());
        } else {
            await axios.post('/api/products', payload());
        }

        emit('saved');
    } catch (error) {
        errors.value = validationErrors(error);

        if (Object.keys(errors.value).length === 0) {
            failure.value = errorMessage(error, t('common.somethingWentWrong'));
        }
    } finally {
        saving.value = false;
    }
}

async function uploadCover(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;
    failure.value = '';

    const data = new FormData();
    data.append('cover', file);

    try {
        const { data: response } = await axios.post(`/api/products/${props.product.id}/cover`, data);
        coverUrl.value = response.data.cover_url;
    } catch (error) {
        failure.value = validationErrors(error).cover ?? errorMessage(error);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

async function removeCover() {
    uploading.value = true;

    try {
        await axios.delete(`/api/products/${props.product.id}/cover`);
        coverUrl.value = null;
    } finally {
        uploading.value = false;
    }
}
</script>

<template>
    <ModalDialog
        :title="editing ? t('products.form.editTitle') : t('products.form.createTitle')"
        wide
        @close="emit('close')"
    >
        <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
            <AlertMessage :message="failure" variant="error" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <InputLabel for="product-slug">{{ t('products.form.slug') }}</InputLabel>
                    <TextInput id="product-slug" v-model="form.slug" :invalid="!!errors.slug" />
                    <InputError :message="errors.slug" />
                    <p class="text-xs text-neutral-500 dark:text-neutral-500">
                        {{ t('products.form.slugHint') }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <InputLabel for="product-status">{{ t('products.form.status') }}</InputLabel>
                    <SelectInput id="product-status" v-model="form.status" :options="statusOptions" />
                </div>
            </div>

            <!-- Language tabs: one product, one set of settings, N translations. -->
            <div class="flex flex-col gap-3">
                <div class="flex gap-1 border-b border-neutral-200 dark:border-neutral-800">
                    <button
                        v-for="(label, code) in SUPPORTED_LOCALES"
                        :key="code"
                        type="button"
                        class="-mb-px border-b-2 px-3 py-2 text-sm transition"
                        :class="
                            activeLocale === code
                                ? 'border-indigo-600 font-medium text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-transparent text-neutral-500 hover:text-neutral-800 dark:hover:text-neutral-200'
                        "
                        @click="activeLocale = code"
                    >
                        {{ label }}
                        <span v-if="!form.translations[code].name" class="ml-1 text-xs opacity-60">•</span>
                    </button>
                </div>

                <InputError :message="errors.translations" />

                <div v-for="code in locales" v-show="activeLocale === code" :key="code" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <InputLabel :for="`name-${code}`">{{ t('products.form.name') }}</InputLabel>
                        <TextInput
                            :id="`name-${code}`"
                            v-model="form.translations[code].name"
                            @input="onNameInput"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <InputLabel :for="`summary-${code}`">{{ t('products.form.summary') }}</InputLabel>
                        <TextInput :id="`summary-${code}`" v-model="form.translations[code].summary" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <InputLabel>{{ t('products.form.body') }}</InputLabel>
                        <RichTextEditor v-model="form.translations[code].body" />
                    </div>
                </div>
            </div>

            <div v-if="editing" class="flex flex-col gap-2">
                <InputLabel>{{ t('products.form.cover') }}</InputLabel>

                <div class="flex items-center gap-4">
                    <img
                        v-if="coverUrl"
                        :src="coverUrl"
                        alt=""
                        class="h-20 w-32 rounded-lg border border-neutral-200 object-cover dark:border-neutral-800"
                    />
                    <div
                        v-else
                        class="flex h-20 w-32 items-center justify-center rounded-lg border border-dashed
                               border-neutral-300 text-xs text-neutral-400 dark:border-neutral-700"
                    >
                        {{ t('products.form.noCover') }}
                    </div>

                    <div class="flex flex-col gap-2">
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            :disabled="uploading"
                            class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-neutral-100
                                   file:px-3 file:py-1.5 file:text-sm dark:file:bg-neutral-800"
                            @change="uploadCover"
                        />
                        <button
                            v-if="coverUrl"
                            type="button"
                            class="self-start text-xs text-[#d03b3b] underline underline-offset-4"
                            @click="removeCover"
                        >
                            {{ t('products.form.removeCover') }}
                        </button>
                    </div>
                </div>
            </div>

            <p v-else class="text-xs text-neutral-500 dark:text-neutral-500">
                {{ t('products.form.coverAfterSave') }}
            </p>

            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-neutral-300 px-4 py-2 text-sm transition
                           hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800"
                    @click="emit('close')"
                >
                    {{ t('common.cancel') }}
                </button>

                <div class="w-32">
                    <PrimaryButton :loading="saving">
                        {{ saving ? t('common.saving') : t('common.save') }}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </ModalDialog>
</template>
