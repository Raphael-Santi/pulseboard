import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import { http } from '@/lib/http';

export interface User {
    id: number;
    name: string;
    email: string;
}

interface LoginPayload {
    email: string;
    password: string;
}

interface RegisterPayload {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);

    // True once the initial /api/user probe has completed (either way), so
    // router guards do not redirect before the session state is known.
    const isResolved = ref(false);

    const isAuthenticated = computed(() => user.value !== null);

    async function fetchUser(): Promise<void> {
        try {
            const { data } = await http.get<User>('/api/user');
            user.value = data;
        } catch {
            user.value = null;
        } finally {
            isResolved.value = true;
        }
    }

    async function login(payload: LoginPayload): Promise<void> {
        await http.get('/sanctum/csrf-cookie');
        await http.post('/login', payload);
        await fetchUser();
    }

    async function register(payload: RegisterPayload): Promise<void> {
        await http.get('/sanctum/csrf-cookie');
        await http.post('/register', payload);
        await fetchUser();
    }

    async function logout(): Promise<void> {
        await http.post('/logout');
        user.value = null;
    }

    return { user, isResolved, isAuthenticated, fetchUser, login, register, logout };
});
