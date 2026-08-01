<script setup>
import { computed, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
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

const form = reactive({ email: '', password: '', remember: false });
const errors = ref({});
const failure = ref('');
const loading = ref(false);

const noticeIsError = computed(() => route.query.verified === '0');

const notice = computed(() => {
    if (route.query.reset === '1') {
        return 'Your password has been reset. Sign in with your new password.';
    }

    switch (route.query.verified) {
        case '1':
            return 'Your email address has been verified. You can sign in now.';
        case 'already':
            return 'That email address was already verified.';
        case '0':
            return 'That verification link is no longer valid. Sign in and request a new one.';
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
            failure.value = errorMessage(error);
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <GuestLayout title="Sign in" subtitle="Welcome back.">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <AlertMessage :message="notice" :variant="noticeIsError ? 'error' : 'success'" />
            <AlertMessage :message="failure" variant="error" />

            <div class="flex flex-col gap-1.5">
                <InputLabel for="email">Email</InputLabel>
                <TextInput id="email" v-model="form.email" type="email" autocomplete="email" :invalid="!!errors.email" />
                <InputError :message="errors.email" />
            </div>

            <div class="flex flex-col gap-1.5">
                <InputLabel for="password">Password</InputLabel>
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
                    Remember me
                </label>

                <RouterLink
                    :to="{ name: 'password.request' }"
                    class="text-sm text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
                >
                    Forgot password?
                </RouterLink>
            </div>

            <PrimaryButton :loading="loading">
                {{ loading ? 'Signing in…' : 'Sign in' }}
            </PrimaryButton>
        </form>

        <template #footer>
            No account?
            <RouterLink
                :to="{ name: 'register' }"
                class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
            >
                Create one
            </RouterLink>
        </template>
    </GuestLayout>
</template>
