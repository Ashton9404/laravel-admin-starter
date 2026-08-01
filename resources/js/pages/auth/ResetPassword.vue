<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
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
const route = useRoute();
const router = useRouter();
const { t } = useI18n();

// Both values come from the emailed link built in AppServiceProvider.
const form = reactive({
    token: route.query.token ?? '',
    email: route.query.email ?? '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});
const failure = ref('');
const loading = ref(false);

async function submit() {
    loading.value = true;
    errors.value = {};
    failure.value = '';

    try {
        await auth.resetPassword({ ...form });
        await router.push({ name: 'login', query: { reset: '1' } });
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
    <GuestLayout :title="t('auth.reset.title')">
        <AlertMessage v-if="!form.token" :message="t('auth.reset.missingToken')" variant="error" />

        <form v-else class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <AlertMessage :message="failure" variant="error" />

            <div class="flex flex-col gap-1.5">
                <InputLabel for="email">{{ t('auth.email') }}</InputLabel>
                <TextInput id="email" v-model="form.email" type="email" :invalid="!!errors.email" />
                <InputError :message="errors.email" />
            </div>

            <div class="flex flex-col gap-1.5">
                <InputLabel for="password">{{ t('auth.reset.newPassword') }}</InputLabel>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    :invalid="!!errors.password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex flex-col gap-1.5">
                <InputLabel for="password_confirmation">{{ t('auth.reset.confirmNewPassword') }}</InputLabel>
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </div>

            <PrimaryButton :loading="loading">
                {{ loading ? t('common.saving') : t('auth.reset.submit') }}
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
