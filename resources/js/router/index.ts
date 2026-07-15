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
