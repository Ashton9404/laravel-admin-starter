import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: () => import('@/pages/Dashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/auth/Register.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/forgot-password',
        name: 'password.request',
        component: () => import('@/pages/auth/ForgotPassword.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/reset-password',
        name: 'password.reset',
        component: () => import('@/pages/auth/ResetPassword.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/verify-email',
        name: 'verification.notice',
        component: () => import('@/pages/auth/VerifyEmail.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/pages/NotFound.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // The very first navigation has to wait for the session lookup, otherwise
    // a logged-in user gets bounced to /login on a hard refresh.
    await auth.initialise();

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
