<script setup>
import { reactive, ref } from 'vue';
import axios, { errorMessage, validationErrors } from '@/bootstrap';
import { useAuthStore } from '@/stores/auth';
import ModalDialog from '@/components/ModalDialog.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';

const props = defineProps({
    // null = creating, an object = editing that user.
    user: { type: Object, default: null },
    roles: { type: Array, required: true },
});

const emit = defineEmits(['close', 'saved']);

const auth = useAuthStore();
const editing = props.user !== null;

const form = reactive({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: [...(props.user?.roles ?? [])],
});

const errors = ref({});
const failure = ref('');
const saving = ref(false);

function toggleRole(name) {
    const index = form.roles.indexOf(name);

    if (index === -1) {
        form.roles.push(name);
    } else {
        form.roles.splice(index, 1);
    }
}

async function submit() {
    saving.value = true;
    errors.value = {};
    failure.value = '';

    const payload = { ...form };

    // An empty password field on edit means "leave it alone", not "blank it".
    if (editing && !payload.password) {
        delete payload.password;
        delete payload.password_confirmation;
    }

    try {
        if (editing) {
            await axios.patch(`/api/users/${props.user.id}`, payload);
        } else {
            await axios.post('/api/users', payload);
        }

        emit('saved');
    } catch (error) {
        errors.value = validationErrors(error);

        if (Object.keys(errors.value).length === 0) {
            failure.value = errorMessage(error);
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <ModalDialog :title="editing ? 'Edit user' : 'New user'" @close="emit('close')">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <AlertMessage :message="failure" variant="error" />

            <div class="flex flex-col gap-1.5">
                <InputLabel for="user-name">Name</InputLabel>
                <TextInput id="user-name" v-model="form.name" :invalid="!!errors.name" />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-1.5">
                <InputLabel for="user-email">Email</InputLabel>
                <TextInput id="user-email" v-model="form.email" type="email" :invalid="!!errors.email" />
                <InputError :message="errors.email" />
                <p v-if="editing" class="text-xs text-neutral-500 dark:text-neutral-500">
                    Changing this clears the email verification.
                </p>
            </div>

            <div class="flex flex-col gap-1.5">
                <InputLabel for="user-password">
                    {{ editing ? 'New password (optional)' : 'Password' }}
                </InputLabel>
                <TextInput
                    id="user-password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    :invalid="!!errors.password"
                />
                <InputError :message="errors.password" />
            </div>

            <div v-if="form.password" class="flex flex-col gap-1.5">
                <InputLabel for="user-password-confirm">Confirm password</InputLabel>
                <TextInput
                    id="user-password-confirm"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </div>

            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Roles</legend>

                <label
                    v-for="role in roles"
                    :key="role.name"
                    class="flex items-center gap-2 text-sm"
                    :class="{ 'opacity-50': role.name === 'admin' && !auth.isAdmin }"
                >
                    <input
                        type="checkbox"
                        :checked="form.roles.includes(role.name)"
                        :disabled="role.name === 'admin' && !auth.isAdmin"
                        class="rounded border-neutral-300 text-indigo-600 dark:border-neutral-700"
                        @change="toggleRole(role.name)"
                    />
                    {{ role.label }}
                    <span v-if="role.name === 'admin' && !auth.isAdmin" class="text-xs text-neutral-500">
                        (administrators only)
                    </span>
                </label>

                <InputError :message="errors.roles" />
            </fieldset>

            <div class="mt-2 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-neutral-300 px-4 py-2 text-sm transition
                           hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800"
                    @click="emit('close')"
                >
                    Cancel
                </button>

                <div class="w-32">
                    <PrimaryButton :loading="saving">
                        {{ saving ? 'Saving…' : 'Save' }}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </ModalDialog>
</template>
