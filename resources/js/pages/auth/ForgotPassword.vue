<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { errorMessage, validationErrors } from '@/bootstrap';
import GuestLayout from '@/layouts/GuestLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';

const auth = useAuthStore();
const { t } = useI18n();

const email = ref('');
const errors = ref({});
const status = ref('');
const failure = ref('');
const loading = ref(false);

async function submit() {
    loading.value = true;
    errors.value = {};
    status.value = '';
    failure.value = '';

    try {
        // Comes back already translated: SetLocale middleware answers in the
        // language the SPA asked for.
        status.value = await auth.forgotPassword(email.value);
    } catch (error) {
        errors.value = validationErrors(error);

        if (Object.keys(errors.value).length === 0) {
            failure.value = errorMessage(error, t('common.somethingWentWrong'));
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <GuestLayout :title="t('auth.forgot.title')" :subtitle="t('auth.forgot.subtitle')">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <AlertMessage :message="status" variant="success" />
            <AlertMessage :message="failure" variant="error" />

            <div class="flex flex-col gap-1.5">
                <InputLabel for="email">{{ t('auth.email') }}</InputLabel>
                <TextInput id="email" v-model="email" type="email" autocomplete="email" :invalid="!!errors.email" />
                <InputError :message="errors.email" />
            </div>

            <PrimaryButton :loading="loading">
                {{ loading ? t('auth.forgot.submitting') : t('auth.forgot.submit') }}
            </PrimaryButton>
        </form>

        <template #footer>
            <RouterLink
                :to="{ name: 'login' }"
                class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
            >
                {{ t('auth.forgot.backToLogin') }}
            </RouterLink>
        </template>
    </GuestLayout>
</template>
