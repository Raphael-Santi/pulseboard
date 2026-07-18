import { defineStore } from 'pinia';
import { ref } from 'vue';

import { http } from '@/lib/http';
import type { MonitorSyncItem, PublicStatus, StatusPage, StatusPageInput } from '@/types/status';

export const useStatusPagesStore = defineStore('statusPages', () => {
    const pages = ref<StatusPage[]>([]);
    const loading = ref(false);

    async function fetchAll(): Promise<void> {
        loading.value = true;
        try {
            const { data } = await http.get<{ data: StatusPage[] }>('/api/status-pages');
            pages.value = data.data;
        } finally {
            loading.value = false;
        }
    }

    async function create(input: StatusPageInput): Promise<StatusPage> {
        const { data } = await http.post<{ data: StatusPage }>('/api/status-pages', input);
        pages.value.unshift(data.data);
        return data.data;
    }

    async function remove(id: number): Promise<void> {
        await http.delete(`/api/status-pages/${id}`);
        pages.value = pages.value.filter((page) => page.id !== id);
    }

    async function syncMonitors(id: number, monitors: MonitorSyncItem[]): Promise<StatusPage> {
        const { data } = await http.post<{ data: StatusPage }>(`/api/status-pages/${id}/monitors`, {
            monitors,
        });
        return data.data;
    }

    // Public, unauthenticated fetch used by the /status/:slug page.
    async function fetchPublic(slug: string): Promise<PublicStatus> {
        const { data } = await http.get<PublicStatus>(`/api/status/${slug}`);
        return data;
    }

    return { pages, loading, fetchAll, create, remove, syncMonitors, fetchPublic };
});
