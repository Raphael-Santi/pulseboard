import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Mock } from 'vitest';

import { http } from '@/lib/http';
import { useAuthStore } from '@/stores/auth';

vi.mock('@/lib/http', () => ({
    http: {
        get: vi.fn(),
        post: vi.fn(),
    },
}));

const mockedGet = http.get as Mock;
const mockedPost = http.post as Mock;

describe('auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('performs the Sanctum handshake on login and loads the user', async () => {
        mockedGet.mockImplementation(async (url: string) => {
            if (url === '/api/user') {
                return { data: { id: 1, name: 'Rafl', email: 'rafl@example.com' } };
            }

            return { data: null };
        });
        mockedPost.mockResolvedValue({ data: null });

        const auth = useAuthStore();
        await auth.login({ email: 'rafl@example.com', password: 'secret' });

        expect(mockedGet).toHaveBeenCalledWith('/sanctum/csrf-cookie');
        expect(mockedPost).toHaveBeenCalledWith('/login', {
            email: 'rafl@example.com',
            password: 'secret',
        });
        expect(auth.isAuthenticated).toBe(true);
        expect(auth.user?.name).toBe('Rafl');
    });

    it('marks the state resolved even when the probe fails', async () => {
        mockedGet.mockRejectedValue(new Error('401'));

        const auth = useAuthStore();
        await auth.fetchUser();

        expect(auth.isResolved).toBe(true);
        expect(auth.isAuthenticated).toBe(false);
    });

    it('clears the user on logout', async () => {
        mockedGet.mockResolvedValue({ data: { id: 1, name: 'Rafl', email: 'r@e.com' } });
        mockedPost.mockResolvedValue({ data: null });

        const auth = useAuthStore();
        await auth.fetchUser();
        expect(auth.isAuthenticated).toBe(true);

        await auth.logout();

        expect(mockedPost).toHaveBeenCalledWith('/logout');
        expect(auth.isAuthenticated).toBe(false);
    });
});
