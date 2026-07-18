export type MonitorType = 'http' | 'tcp' | 'dns' | 'ping' | 'heartbeat';

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
