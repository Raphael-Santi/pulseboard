import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Mock } from 'vitest';

import { http } from '@/lib/http';
import { useMonitorsStore } from '@/stores/monitors';
import type { Monitor } from '@/types/monitor';

vi.mock('@/lib/http', () => ({
    http: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
}));

const mockedGet = http.get as Mock;
const mockedPost = http.post as Mock;
const mockedPut = http.put as Mock;
const mockedDelete = http.delete as Mock;

function monitor(overrides: Partial<Monitor> = {}): Monitor {
    return {
        id: 1,
        name: 'Site',
        type: 'http',
        target: 'https://example.com',
        port: null,
        interval_sec: 60,
        timeout_sec: 10,
        failure_threshold: 3,
        is_paused: false,
        created_at: '',
        updated_at: '',
        ...overrides,
    };
}

describe('monitors store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('loads monitors from the API', async () => {
        mockedGet.mockResolvedValue({ data: { data: [monitor()] } });

        const store = useMonitorsStore();
        await store.fetchAll();

        expect(store.monitors).toHaveLength(1);
        expect(store.loaded).toBe(true);
    });

    it('prepends a created monitor', async () => {
        mockedPost.mockResolvedValue({ data: { data: monitor({ id: 2, name: 'New' }) } });

        const store = useMonitorsStore();
        store.monitors.push(monitor({ id: 1 }));
        await store.create({
            name: 'New',
            type: 'http',
            target: 'https://new.example.com',
            port: null,
            interval_sec: 60,
            timeout_sec: 10,
            failure_threshold: 3,
        });

        expect(store.monitors[0]?.id).toBe(2);
    });

    it('replaces a monitor after toggling pause', async () => {
        mockedPost.mockResolvedValue({ data: { data: monitor({ is_paused: true }) } });

        const store = useMonitorsStore();
        store.monitors.push(monitor({ is_paused: false }));
        await store.togglePause(1);

        expect(store.monitors[0]?.is_paused).toBe(true);
        expect(mockedPost).toHaveBeenCalledWith('/api/monitors/1/toggle-pause');
    });

    it('updates a monitor in place', async () => {
        mockedPut.mockResolvedValue({ data: { data: monitor({ name: 'Renamed' }) } });

        const store = useMonitorsStore();
        store.monitors.push(monitor({ name: 'Old' }));
        await store.update(1, {
            name: 'Renamed',
            type: 'http',
            target: 'https://example.com',
            port: null,
            interval_sec: 60,
            timeout_sec: 10,
            failure_threshold: 3,
        });

        expect(store.monitors[0]?.name).toBe('Renamed');
    });

    it('removes a monitor', async () => {
        mockedDelete.mockResolvedValue({ data: null });

        const store = useMonitorsStore();
        store.monitors.push(monitor({ id: 1 }), monitor({ id: 2 }));
        await store.remove(1);

        expect(store.monitors.map((item) => item.id)).toEqual([2]);
    });
});
