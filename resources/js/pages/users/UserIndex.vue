<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import axios, { errorMessage } from '@/bootstrap';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import PaginationBar from '@/components/PaginationBar.vue';
import SelectInput from '@/components/SelectInput.vue';
import TextInput from '@/components/TextInput.vue';
import UserFormModal from '@/components/UserFormModal.vue';

const auth = useAuthStore();

const users = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const roles = ref([]);
const loading = ref(true);
const failure = ref('');

const filters = reactive({
    search: '',
    role: '',
    verified: '',
    sort: 'created_at',
    direction: 'desc',
    page: 1,
});

const editing = ref(null);
const creating = ref(false);
const deleting = ref(null);
const deletingBusy = ref(false);

const sortOptions = [
    { value: 'created_at', label: 'Newest first' },
    { value: 'name', label: 'Name' },
    { value: 'email', label: 'Email' },
];

async function load() {
    loading.value = true;
    failure.value = '';

    const params = { page: filters.page, sort: filters.sort, direction: filters.direction };

    if (filters.search) params.search = filters.search;
    if (filters.role) params.role = filters.role;
    if (filters.verified !== '') params.verified = filters.verified;

    try {
        const { data } = await axios.get('/api/users', { params });
        users.value = data.data;
        meta.value = data.meta;
    } catch (error) {
        failure.value = errorMessage(error, 'Could not load users.');
    } finally {
        loading.value = false;
    }
}

let searchTimer = null;

// Debounced: one request when typing stops, not one per keystroke.
watch(
    () => filters.search,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            filters.page = 1;
            load();
        }, 300);
    },
);

watch([() => filters.role, () => filters.verified, () => filters.sort], () => {
    filters.page = 1;
    load();
});

onMounted(async () => {
    const [rolesResponse] = await Promise.allSettled([axios.get('/api/roles'), load()]);

    if (rolesResponse.status === 'fulfilled') {
        roles.value = rolesResponse.value.data;
    }
});

function changePage(page) {
    filters.page = page;
    load();
}

function onSaved() {
    creating.value = false;
    editing.value = null;
    load();
}

async function confirmDelete() {
    deletingBusy.value = true;

    try {
        await axios.delete(`/api/users/${deleting.value.id}`);
        deleting.value = null;
        load();
    } catch (error) {
        failure.value = errorMessage(error, 'Could not delete that user.');
        deleting.value = null;
    } finally {
        deletingBusy.value = false;
    }
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' }) : '—';
}
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ meta.total }} account{{ meta.total === 1 ? '' : 's' }}
                    </p>
                </div>

                <button
                    v-if="auth.can('users.create')"
                    type="button"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition
                           hover:bg-indigo-500"
                    @click="creating = true"
                >
                    New user
                </button>
            </header>

            <AlertMessage :message="failure" variant="error" />

            <!-- One filter row above everything it scopes. -->
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <TextInput v-model="filters.search" placeholder="Search name or email" />

                <SelectInput
                    v-model="filters.role"
                    :options="[
                        { value: '', label: 'All roles' },
                        ...roles.map((role) => ({ value: role.name, label: role.label })),
                    ]"
                />

                <SelectInput
                    v-model="filters.verified"
                    :options="[
                        { value: '', label: 'Any verification' },
                        { value: '1', label: 'Verified' },
                        { value: '0', label: 'Not verified' },
                    ]"
                />

                <SelectInput v-model="filters.sort" :options="sortOptions" />
            </div>

            <div
                class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                :class="{ 'opacity-60': loading }"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-2xl text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 text-left text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                                <th scope="col" class="px-5 py-3 font-medium">Name</th>
                                <th scope="col" class="px-5 py-3 font-medium">Roles</th>
                                <th scope="col" class="px-5 py-3 font-medium">Verified</th>
                                <th scope="col" class="px-5 py-3 font-medium">Joined</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!loading && users.length === 0">
                                <td colspan="5" class="px-5 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                    No users match these filters.
                                </td>
                            </tr>

                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="border-b border-neutral-100 last:border-0 dark:border-neutral-800"
                            >
                                <td class="px-5 py-3">
                                    <span class="font-medium">{{ user.name }}</span>
                                    <span class="block text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ user.email }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-neutral-600 dark:text-neutral-400">
                                    {{ user.roles?.length ? user.roles.join(', ') : '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="user.email_verified" class="text-[#006300] dark:text-[#0ca30c]">
                                        ✓ Yes
                                    </span>
                                    <span v-else class="text-neutral-500 dark:text-neutral-400">✕ No</span>
                                </td>
                                <td class="px-5 py-3 tabular-nums">{{ formatDate(user.created_at) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-3">
                                        <button
                                            v-if="auth.can('users.update') || user.id === auth.user?.id"
                                            type="button"
                                            class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400"
                                            @click="editing = user"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            v-if="auth.can('users.delete') && user.id !== auth.user?.id"
                                            type="button"
                                            class="text-[#d03b3b] underline underline-offset-4"
                                            @click="deleting = user"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <PaginationBar :meta="meta" @change="changePage" />
        </div>

        <UserFormModal
            v-if="creating"
            :roles="roles"
            @close="creating = false"
            @saved="onSaved"
        />

        <UserFormModal
            v-if="editing"
            :user="editing"
            :roles="roles"
            @close="editing = null"
            @saved="onSaved"
        />

        <ModalDialog v-if="deleting" title="Delete user" @close="deleting = null">
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                Permanently delete <span class="font-medium">{{ deleting.name }}</span>
                ({{ deleting.email }})? This cannot be undone.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-neutral-300 px-4 py-2 text-sm transition
                           hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800"
                    @click="deleting = null"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    :disabled="deletingBusy"
                    class="rounded-lg bg-[#d03b3b] px-4 py-2 text-sm font-medium text-white transition
                           hover:opacity-90 disabled:opacity-60"
                    @click="confirmDelete"
                >
                    {{ deletingBusy ? 'Deleting…' : 'Delete' }}
                </button>
            </div>
        </ModalDialog>
    </AppLayout>
</template>
