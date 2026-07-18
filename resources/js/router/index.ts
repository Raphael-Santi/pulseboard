import { createRouter, createWebHistory } from 'vue-router';

import HomePage from '@/pages/HomePage.vue';
import { useAuthStore } from '@/stores/auth';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: HomePage },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/LoginPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/pages/RegisterPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: () => import('@/pages/DashboardPage.vue'),
            meta: { auth: true },
        },
        {
            path: '/status-pages',
            name: 'status-pages',
            component: () => import('@/pages/StatusPagesPage.vue'),
            meta: { auth: true },
        },
        {
            // Public status page — no auth guard.
            path: '/status/:slug',
            name: 'status.public',
            component: () => import('@/pages/PublicStatusPage.vue'),
        },
        {
            path: '/monitors/new',
            name: 'monitors.create',
            component: () => import('@/pages/MonitorFormPage.vue'),
            meta: { auth: true },
        },
        {
            path: '/heartbeats/new',
            name: 'heartbeats.create',
            component: () => import('@/pages/HeartbeatFormPage.vue'),
            meta: { auth: true },
        },
        {
            path: '/monitors/:id',
            name: 'monitors.show',
            component: () => import('@/pages/MonitorDetailPage.vue'),
            meta: { auth: true },
        },
        {
            path: '/monitors/:id/edit',
            name: 'monitors.edit',
            component: () => import('@/pages/MonitorFormPage.vue'),
            meta: { auth: true },
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.isResolved) {
        await auth.fetchUser();
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});
