import { defineStore } from 'pinia';
import { ref } from 'vue';

import { http } from '@/lib/http';
import type { Incident, MetricsWindow, MonitorMetrics } from '@/types/metrics';
import type { CheckRecordedEvent, IncidentEvent, Monitor, MonitorInput } from '@/types/monitor';

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

    async function fetchOne(id: number): Promise<Monitor> {
        const { data } = await http.get<{ data: Monitor }>(`/api/monitors/${id}`);
        if (find(id)) {
            replace(data.data);
        } else {
            monitors.value.push(data.data);
        }
        return data.data;
    }

    async function fetchMetrics(
        id: number,
        window: MetricsWindow = '24h',
    ): Promise<MonitorMetrics> {
        const { data } = await http.get<MonitorMetrics>(`/api/monitors/${id}/metrics`, {
            params: { window },
        });
        return data;
    }

    async function fetchIncidents(id: number): Promise<Incident[]> {
        const { data } = await http.get<{ data: Incident[] }>(`/api/monitors/${id}/incidents`);
        return data.data;
    }

    async function acknowledgeIncident(incidentId: number): Promise<Incident> {
        const { data } = await http.post<{ data: Incident }>(
            `/api/incidents/${incidentId}/acknowledge`,
        );
        return data.data;
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

    // --- Real-time mutations, applied from broadcast events. ---

    function applyCheckResult(event: CheckRecordedEvent): void {
        const monitor = find(event.monitor_id);
        if (monitor) {
            monitor.latest_status = event.status;
            monitor.last_checked_at = event.checked_at;
        }
    }

    function applyIncidentOpened(event: IncidentEvent): void {
        const monitor = find(event.monitor_id);
        if (monitor) {
            monitor.has_open_incident = true;
        }
    }

    function applyIncidentClosed(event: IncidentEvent): void {
        const monitor = find(event.monitor_id);
        if (monitor) {
            monitor.has_open_incident = false;
        }
    }

    return {
        monitors,
        loading,
        loaded,
        fetchAll,
        fetchOne,
        fetchMetrics,
        fetchIncidents,
        acknowledgeIncident,
        create,
        update,
        togglePause,
        remove,
        find,
        applyCheckResult,
        applyIncidentOpened,
        applyIncidentClosed,
    };
});
