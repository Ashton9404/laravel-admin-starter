<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import axios, { errorMessage } from '@/bootstrap';
import AppLayout from '@/layouts/AppLayout.vue';
import AlertMessage from '@/components/AlertMessage.vue';
import BarList from '@/components/BarList.vue';
import StatTile from '@/components/StatTile.vue';
import TrendChart from '@/components/TrendChart.vue';

const auth = useAuthStore();
const { t, locale } = useI18n();

const stats = ref(null);
const loading = ref(true);
const failure = ref('');

const canSeeStats = auth.can('users.view');

onMounted(async () => {
    if (!canSeeStats) {
        loading.value = false;

        return;
    }

    try {
        const { data } = await axios.get('/api/dashboard');
        stats.value = data;
    } catch (error) {
        failure.value = errorMessage(error, t('dashboard.loadFailed'));
    } finally {
        loading.value = false;
    }
});

function verifiedShare() {
    if (!stats.value?.totals.users) {
        return '';
    }

    const share = Math.round((stats.value.totals.verified / stats.value.totals.users) * 100);

    return t('dashboard.shareOfAccounts', { share });
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString(locale.value, { dateStyle: 'medium' }) : '—';
}
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-8">
            <header class="flex flex-col gap-1">
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ t('dashboard.welcome', { name: auth.user?.name }) }}
                </h1>
                <p class="text-neutral-600 dark:text-neutral-400">
                    {{ t('dashboard.signedInAs', { email: auth.user?.email }) }}
                    <span v-if="auth.user?.roles?.length">· {{ auth.user.roles.join(', ') }}</span>
                </p>
            </header>

            <AlertMessage :message="failure" variant="error" />

            <!-- The API would return 403 anyway; this just avoids showing a
                 broken panel to someone who was never going to see the data. -->
            <section
                v-if="!canSeeStats"
                class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <h2 class="text-sm font-medium">{{ t('dashboard.noPermissionTitle') }}</h2>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ t('dashboard.noPermissionBody') }}
                </p>
            </section>

            <p v-else-if="loading" class="text-sm text-neutral-500 dark:text-neutral-400">
                {{ t('common.loading') }}
            </p>

            <template v-else-if="stats">
                <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatTile :label="t('dashboard.totalUsers')" :value="stats.totals.users" />
                    <StatTile
                        :label="t('dashboard.verifiedEmails')"
                        :value="stats.totals.verified"
                        :context="verifiedShare()"
                    />
                    <StatTile :label="t('dashboard.awaitingVerification')" :value="stats.totals.unverified" />
                    <StatTile
                        :label="t('dashboard.newThisWeek')"
                        :value="stats.totals.new_this_week"
                        :context="t('dashboard.lastSevenDays')"
                    />
                </section>

                <section class="grid gap-4 lg:grid-cols-3">
                    <div
                        class="rounded-xl border border-neutral-200 bg-white p-5 lg:col-span-2
                               dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <h2 class="text-sm font-medium">{{ t('dashboard.signUpsPerDay') }}</h2>
                        <p class="mt-0.5 mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            {{ t('dashboard.lastThirtyDays') }}
                        </p>

                        <TrendChart :points="stats.registrations" :label="t('dashboard.signUps')" />
                    </div>

                    <div
                        class="rounded-xl border border-neutral-200 bg-white p-5
                               dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <h2 class="text-sm font-medium">{{ t('dashboard.usersByRole') }}</h2>
                        <p class="mt-0.5 mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            {{ t('dashboard.usersByRoleHint') }}
                        </p>

                        <BarList :items="stats.users_by_role" />
                    </div>
                </section>

                <section
                    class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <h2 class="text-sm font-medium">{{ t('dashboard.recentSignUps') }}</h2>
                    <p class="mt-0.5 mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        {{ t('dashboard.recentSignUpsHint') }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-md text-sm">
                            <thead>
                                <tr class="text-left text-neutral-500 dark:text-neutral-400">
                                    <th scope="col" class="py-2 pr-4 font-medium">{{ t('users.columns.name') }}</th>
                                    <th scope="col" class="py-2 pr-4 font-medium">{{ t('users.columns.roles') }}</th>
                                    <th scope="col" class="py-2 pr-4 font-medium">{{ t('users.columns.verified') }}</th>
                                    <th scope="col" class="py-2 font-medium">{{ t('users.columns.joined') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="user in stats.recent_users"
                                    :key="user.id"
                                    class="border-t border-neutral-100 dark:border-neutral-800"
                                >
                                    <td class="py-2 pr-4">
                                        <span class="font-medium">{{ user.name }}</span>
                                        <span class="block text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ user.email }}
                                        </span>
                                    </td>
                                    <td class="py-2 pr-4 text-neutral-600 dark:text-neutral-400">
                                        {{ user.roles.length ? user.roles.join(', ') : '—' }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        <!-- Icon + word, never colour alone. -->
                                        <span v-if="user.verified" class="text-[#006300] dark:text-[#0ca30c]">
                                            ✓ {{ t('common.yes') }}
                                        </span>
                                        <span v-else class="text-neutral-500 dark:text-neutral-400">
                                            ✕ {{ t('common.no') }}
                                        </span>
                                    </td>
                                    <td class="py-2 tabular-nums">{{ formatDate(user.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
