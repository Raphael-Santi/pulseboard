import { defineStore } from 'pinia';
import { ref } from 'vue';

import { http } from '@/lib/http';
import type { Monitor, MonitorInput } from '@/types/monitor';

export const useMonitorsStore = defineStore('monitors', () => {
    const monitors = ref<Monitor[]>([]);
    const loading = ref(false);
    const loaded = ref(false);

    async function fetchAll(): Promise<void> {
        loading.value = true;
        try {
            const { data } = await http.get<{ data: Monitor[] }>('/api/monitors');
            monitors.value = data.data;
            loaded.value = true;
        } finally {
            loading.value = false;
        }
    }

    async function create(input: MonitorInput): Promise<Monitor> {
        const { data } = await http.post<{ data: Monitor }>('/api/monitors', input);
        monitors.value.unshift(data.data);
        return data.data;
    }

    async function update(id: number, input: MonitorInput): Promise<Monitor> {
        const { data } = await http.put<{ data: Monitor }>(`/api/monitors/${id}`, input);
        replace(data.data);
        return data.data;
    }

    async function togglePause(id: number): Promise<void> {
        const { data } = await http.post<{ data: Monitor }>(`/api/monitors/${id}/toggle-pause`);
        replace(data.data);
    }

    async function remove(id: number): Promise<void> {
        await http.delete(`/api/monitors/${id}`);
        monitors.value = monitors.value.filter((monitor) => monitor.id !== id);
    }

    function find(id: number): Monitor | null {
        return monitors.value.find((monitor) => monitor.id === id) ?? null;
    }

    function replace(monitor: Monitor): void {
        const index = monitors.value.findIndex((item) => item.id === monitor.id);
        if (index !== -1) {
            monitors.value[index] = monitor;
        }
    }

    return { monitors, loading, loaded, fetchAll, create, update, togglePause, remove, find };
});
