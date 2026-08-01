<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios, { errorMessage } from '@/bootstrap';
import AppLayout from '@/layouts/AppLayout.vue';
import ActivityFeed from '@/components/ActivityFeed.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import PaginationBar from '@/components/PaginationBar.vue';
import SelectInput from '@/components/SelectInput.vue';

const { t } = useI18n();

const entries = ref([]);
const meta = ref({});
const loading = ref(true);
const error = ref('');

const event = ref('');
const subjectType = ref('');

// Mirrors Activity::EVENTS and the morph map. The API rejects anything else, so
// these lists exist to offer the valid choices, not to enforce them.
const events = ['created', 'updated', 'deleted', 'login', 'logout', 'login_failed', 'reordered'];
const subjectTypes = ['user', 'product', 'media'];

const eventOptions = computed(() => [
    { value: '', label: t('activity.allEvents') },
    ...events.map((name) => ({ value: name, label: t(`activity.labels.${name}`) })),
]);

const typeOptions = computed(() => [
    { value: '', label: t('activity.allTypes') },
    ...subjectTypes.map((name) => ({ value: name, label: t(`activity.types.${name}`) })),
]);

async function load(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/api/activity', {
            params: {
                page,
                event: event.value || undefined,
                subject_type: subjectType.value || undefined,
            },
        });

        entries.value = data.data;
        meta.value = data.meta;
    } catch (exception) {
        error.value = errorMessage(exception, t('activity.loadFailed'));
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-6">
            <header>
                <h1 class="text-2xl font-semibold tracking-tight">{{ t('activity.title') }}</h1>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ t('activity.subtitle') }}</p>
            </header>

            <AlertMessage :message="error" variant="error" />

            <div class="flex flex-wrap gap-3">
                <!-- SelectInput is w-full; the width belongs to the slot it sits in. -->
                <div class="w-48">
                    <SelectInput
                        v-model="event"
                        :options="eventOptions"
                        :aria-label="t('activity.allEvents')"
                        @change="load()"
                    />
                </div>
                <div class="w-48">
                    <SelectInput
                        v-model="subjectType"
                        :options="typeOptions"
                        :aria-label="t('activity.allTypes')"
                        @change="load()"
                    />
                </div>
            </div>

            <section
                class="rounded-xl border border-neutral-200 bg-white px-5 py-2 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <p v-if="loading" class="py-8 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    {{ t('common.loading') }}
                </p>

                <ActivityFeed v-else :entries="entries" detailed />
            </section>

            <PaginationBar :meta="meta" @change="load" />
        </div>
    </AppLayout>
</template>
