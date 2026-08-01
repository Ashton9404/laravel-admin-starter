<script setup>
import { computed, reactive, ref } from 'vue';
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

const form = reactive({ email: '', password: '', remember: false });
const errors = ref({});
const failure = ref('');
const loading = ref(false);

const noticeIsError = computed(() => route.query.verified === '0');

const notice = computed(() => {
    if (route.query.reset === '1') {
        return t('auth.login.passwordReset');
    }

    switch (route.query.verified) {
        case '1':
            return t('auth.login.verified');
        case 'already':
            return t('auth.login.alreadyVerified');
        case '0':
            return t('auth.login.verificationExpired');
        default:
            return '';
    }
});

async function submit() {
    loading.value = true;
    errors.value = {};
    failure.value = '';

    try {
        await auth.login({ ...form });
        await router.push(route.query.redirect ?? { name: 'dashboard' });
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
    <GuestLayout :title="t('auth.login.title')" :subtitle="t('auth.login.subtitle')">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <AlertMessage :message="notice" :variant="noticeIsError ? 'error' : 'success'" />
            <AlertMessage :message="failure" variant="error" />

            <div class="flex flex-col gap-1.5">
                <InputLabel for="email">{{ t('auth.email') }}</InputLabel>
                <TextInput id="email" v-model="form.email" type="email" autocomplete="email" :invalid="!!errors.email" />
                <InputError :message="errors.email" />
            </div>

            <div class="flex flex-col gap-1.5">
                <InputLabel for="password">{{ t('auth.password') }}</InputLabel>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    :invalid="!!errors.password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-neutral-300 text-indigo-600 dark:border-neutral-700"
                    />
                    {{ t('auth.login.remember') }}
                </label>

                <RouterLink
                    :to="{ name: 'password.request' }"
                    class="text-sm text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
                >
                    {{ t('auth.login.forgot') }}
                </RouterLink>
            </div>

            <PrimaryButton :loading="loading">
                {{ loading ? t('auth.login.submitting') : t('auth.login.submit') }}
            </PrimaryButton>
        </form>

        <template #footer>
            {{ t('auth.login.noAccount') }}
            <RouterLink
                :to="{ name: 'register' }"
                class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
            >
                {{ t('auth.login.createOne') }}
            </RouterLink>
        </template>
    </GuestLayout>
</template>
