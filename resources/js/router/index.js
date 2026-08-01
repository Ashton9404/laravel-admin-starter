import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

/**
 * The public website owns the root, and the admin panel lives under /admin.
 *
 * That split is the reason products can sit at /products where anyone would
 * expect them, and it makes the boundary legible in the URL: everything under
 * /admin needs an account, everything above it does not.
 */
const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('@/pages/public/ProductList.vue'),
    },
    {
        path: '/products',
        name: 'public.products',
        component: () => import('@/pages/public/ProductList.vue'),
    },
    {
        path: '/products/:slug',
        name: 'public.product',
        component: () => import('@/pages/public/ProductDetail.vue'),
        props: true,
    },
    {
        path: '/admin',
        name: 'dashboard',
        component: () => import('@/pages/Dashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/users',
        name: 'users.index',
        component: () => import('@/pages/users/UserIndex.vue'),
        meta: { requiresAuth: true, permission: 'users.view' },
    },
    {
        path: '/admin/products',
        name: 'products.index',
        component: () => import('@/pages/products/ProductIndex.vue'),
        meta: { requiresAuth: true, permission: 'products.view' },
    },
    {
        path: '/admin/media',
        name: 'media.index',
        component: () => import('@/pages/media/MediaLibrary.vue'),
        meta: { requiresAuth: true, permission: 'media.view' },
    },
    {
        path: '/admin/activity',
        name: 'activity.index',
        component: () => import('@/pages/activity/ActivityLog.vue'),
        meta: { requiresAuth: true, permission: 'activity.view' },
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
    // Following a link should land at the top of the new page, not halfway down
    // it because that is where the last one was scrolled to.
    scrollBehavior: (to, from, saved) => saved ?? { top: 0 },
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

    // Cosmetic only — the API enforces the same rule and is the real gate.
    if (to.meta.permission && !auth.can(to.meta.permission)) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
