import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Mock } from 'vitest';

import { http } from '@/lib/http';
import { useStatusPagesStore } from '@/stores/statusPages';

vi.mock('@/lib/http', () => ({
    http: { get: vi.fn(), post: vi.fn(), delete: vi.fn() },
}));

const mockedGet = http.get as Mock;
const mockedPost = http.post as Mock;

describe('status pages store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('creates a status page and prepends it', async () => {
        mockedPost.mockResolvedValue({
            data: { data: { id: 1, slug: 'acme', title: 'Acme', is_public: true, created_at: '' } },
        });

        const store = useStatusPagesStore();
        await store.create({ slug: 'acme', title: 'Acme' });

        expect(mockedPost).toHaveBeenCalledWith('/api/status-pages', {
            slug: 'acme',
            title: 'Acme',
        });
        expect(store.pages[0]?.slug).toBe('acme');
    });

    it('syncs monitors onto a page', async () => {
        mockedPost.mockResolvedValue({ data: { data: { id: 1 } } });

        const store = useStatusPagesStore();
        await store.syncMonitors(1, [{ id: 5, sort_order: 0 }]);

        expect(mockedPost).toHaveBeenCalledWith('/api/status-pages/1/monitors', {
            monitors: [{ id: 5, sort_order: 0 }],
        });
    });

    it('fetches a public status page', async () => {
        mockedGet.mockResolvedValue({
            data: { title: 'Acme', overall_status: 'operational', components: [], incidents: [] },
        });

        const store = useStatusPagesStore();
        const result = await store.fetchPublic('acme');

        expect(mockedGet).toHaveBeenCalledWith('/api/status/acme');
        expect(result.overall_status).toBe('operational');
    });
});
