<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { errorMessage } from '@/bootstrap';
import GuestLayout from '@/layouts/GuestLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';

const auth = useAuthStore();
const router = useRouter();

const status = ref('');
const failure = ref('');
const loading = ref(false);
const signingOut = ref(false);

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
        failure.value = errorMessage(error, 'Too many requests. Please wait a minute and try again.');
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
    <GuestLayout
        title="Verify your email"
        :subtitle="`We sent a verification link to ${auth.user?.email ?? 'your inbox'}.`"
    >
        <div class="flex flex-col gap-4">
            <AlertMessage :message="status" variant="success" />
            <AlertMessage :message="failure" variant="error" />

            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                Click the link in that email to finish setting up your account. If it never
                arrived, we can send another one.
            </p>

            <PrimaryButton :loading="loading" @click.prevent="resend">
                {{ loading ? 'Sending…' : 'Resend verification email' }}
            </PrimaryButton>
        </div>

        <template #footer>
            <button
                type="button"
                :disabled="signingOut"
                class="text-indigo-600 underline underline-offset-4 disabled:opacity-60 dark:text-indigo-400"
                @click="signOut"
            >
                {{ signingOut ? 'Signing out…' : 'Sign out' }}
            </button>
        </template>
    </GuestLayout>
</template>
