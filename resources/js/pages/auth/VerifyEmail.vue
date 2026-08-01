<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { errorMessage } from '@/bootstrap';
import GuestLayout from '@/layouts/GuestLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';

const auth = useAuthStore();
const router = useRouter();
const { t } = useI18n();

const status = ref('');
const failure = ref('');
const loading = ref(false);
const signingOut = ref(false);

const subtitle = computed(() =>
    t('auth.verify.subtitle', { email: auth.user?.email ?? t('auth.verify.subtitleFallback') }),
);

onMounted(() => {
    if (auth.isVerified) {
        router.replace({ name: 'dashboard' });
    }
});

async function resend() {
    loading.value = true;
    status.value = '';
    failure.value = '';

    try {
        status.value = await auth.resendVerificationEmail();
    } catch (error) {
        failure.value = errorMessage(error, t('auth.verify.throttled'));
    } finally {
        loading.value = false;
    }
}

async function signOut() {
    signingOut.value = true;

    try {
        await auth.logout();
        await router.push({ name: 'login' });
    } finally {
        signingOut.value = false;
    }
}
</script>

<template>
    <GuestLayout :title="t('auth.verify.title')" :subtitle="subtitle">
        <div class="flex flex-col gap-4">
            <AlertMessage :message="status" variant="success" />
            <AlertMessage :message="failure" variant="error" />

            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ t('auth.verify.body') }}
            </p>

            <PrimaryButton :loading="loading" @click.prevent="resend">
                {{ loading ? t('auth.verify.resending') : t('auth.verify.resend') }}
            </PrimaryButton>
        </div>

        <template #footer>
            <button
                type="button"
                :disabled="signingOut"
                class="text-indigo-600 underline underline-offset-4 disabled:opacity-60 dark:text-indigo-400"
                @click="signOut"
            >
                {{ signingOut ? t('nav.signingOut') : t('nav.signOut') }}
            </button>
        </template>
    </GuestLayout>
</template>
