export type MetricsWindow = '24h' | '7d';

export interface LatencyPoint {
    t: string;
    avg_ms: number;
}

export interface MonitorMetrics {
    uptime: {
        '24h': number | null;
        '7d': number | null;
        '30d': number | null;
    };
    latency: {
        window: MetricsWindow;
        points: LatencyPoint[];
    };
}

export type IncidentStatus = 'open' | 'acknowledged' | 'closed';

export interface IncidentUpdate {
    id: number;
    status: string;
    message: string;
    created_at: string;
}

export interface Incident {
    id: number;
    monitor_id: number;
    status: IncidentStatus;
    cause: string;
    opened_at: string;
    acknowledged_at: string | null;
    closed_at: string | null;
    updates: IncidentUpdate[];
}
