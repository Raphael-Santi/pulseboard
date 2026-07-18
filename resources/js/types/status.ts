export type ComponentStatus = 'operational' | 'down' | 'unknown';
export type OverallStatus = 'operational' | 'degraded' | 'down' | 'unknown';

export interface UptimeDay {
    date: string;
    uptime: number | null;
}

export interface StatusComponent {
    name: string;
    status: ComponentStatus;
    uptime: UptimeDay[];
}

export interface StatusIncidentUpdate {
    status: string;
    message: string;
    created_at: string | null;
}

export interface StatusIncident {
    cause: string;
    opened_at: string;
    closed_at: string | null;
    updates: StatusIncidentUpdate[];
}

export interface PublicStatus {
    title: string;
    overall_status: OverallStatus;
    components: StatusComponent[];
    incidents: StatusIncident[];
}

export interface StatusPageMonitorRef {
    id: number;
    name: string;
    display_name: string | null;
    sort_order: number | null;
}

export interface StatusPage {
    id: number;
    slug: string;
    title: string;
    is_public: boolean;
    monitors?: StatusPageMonitorRef[];
    created_at: string;
}

export interface StatusPageInput {
    slug: string;
    title: string;
    is_public?: boolean;
}

export interface MonitorSyncItem {
    id: number;
    display_name?: string | null;
    sort_order?: number | null;
}
