export type MonitorType = 'http' | 'tcp' | 'dns' | 'ping' | 'heartbeat';

export type CheckStatus = 'ok' | 'failed';

/** Types a user can create through the standard monitor form. */
export const ACTIVE_MONITOR_TYPES: Exclude<MonitorType, 'heartbeat'>[] = [
    'http',
    'tcp',
    'dns',
    'ping',
];

export interface Monitor {
    id: number;
    name: string;
    type: MonitorType;
    target: string | null;
    port: number | null;
    interval_sec: number;
    timeout_sec: number;
    failure_threshold: number;
    is_paused: boolean;
    latest_status: CheckStatus | null;
    last_checked_at: string | null;
    has_open_incident: boolean;
    created_at: string;
    updated_at: string;
}

export interface MonitorInput {
    name: string;
    type: MonitorType;
    target: string;
    port: number | null;
    interval_sec: number;
    timeout_sec: number;
    failure_threshold: number;
}

/** Payload broadcast on the `.check.recorded` event. */
export interface CheckRecordedEvent {
    monitor_id: number;
    status: CheckStatus;
    latency_ms: number | null;
    checked_at: string;
}

/** Payload broadcast on the `.incident.opened` / `.incident.closed` events. */
export interface IncidentEvent {
    monitor_id: number;
    incident_id: number;
}
