<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { errorMessage, validationErrors } from '@/bootstrap';
import GuestLayout from '@/layouts/GuestLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({
    name: '',
    email: '',
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
        await auth.register({ ...form });
        // Registration logs the user in, but their email is not verified yet.
        await router.push({ name: 'verification.notice' });
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
    <GuestLayout title="Create an account" subtitle="It takes about ten seconds.">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <AlertMessage :message="failure" variant="error" />

            <div class="flex flex-col gap-1.5">
                <InputLabel for="name">Name</InputLabel>
                <TextInput id="name" v-model="form.name" autocomplete="name" :invalid="!!errors.name" />
                <InputError :message="errors.name" />
            </div>

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
                    autocomplete="new-password"
                    :invalid="!!errors.password"
                />
                <InputError :message="errors.password" />
                <p class="text-xs text-neutral-500 dark:text-neutral-500">
                    At least 8 characters, including a letter and a number.
                </p>
            </div>

            <div class="flex flex-col gap-1.5">
                <InputLabel for="password_confirmation">Confirm password</InputLabel>
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </div>

            <PrimaryButton :loading="loading">
                {{ loading ? 'Creating account…' : 'Create account' }}
            </PrimaryButton>
        </form>

        <template #footer>
            Already registered?
            <RouterLink
                :to="{ name: 'login' }"
                class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
            >
                Sign in
            </RouterLink>
        </template>
    </GuestLayout>
</template>
