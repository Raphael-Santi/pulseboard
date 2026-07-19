import type { Monitor } from '@/types/monitor';

export type DisplayStatus = 'up' | 'down' | 'paused' | 'unknown';

export interface StatusMeta {
    key: DisplayStatus;
    label: string;
    glyph: string;
    /** Background class for a small status dot. */
    dot: string;
    /** Soft background + text classes for a status pill. */
    pill: string;
    /** Border-left accent color (CSS var) for cards/rows. */
    accent: string;
}

export const STATUS_META: Record<DisplayStatus, StatusMeta> = {
    up: {
        key: 'up',
        label: 'Работает',
        glyph: '✓',
        dot: 'bg-up',
        pill: 'bg-up-soft text-up',
        accent: 'var(--color-up)',
    },
    down: {
        key: 'down',
        label: 'Не работает',
        glyph: '!',
        dot: 'bg-down',
        pill: 'bg-down-soft text-down',
        accent: 'var(--color-down)',
    },
    paused: {
        key: 'paused',
        label: 'На паузе',
        glyph: '⏸',
        dot: 'bg-unknown',
        pill: 'bg-unknown-soft text-fg-muted',
        accent: 'var(--color-unknown)',
    },
    unknown: {
        key: 'unknown',
        label: 'Неизвестно',
        glyph: '?',
        dot: 'bg-unknown',
        pill: 'bg-unknown-soft text-fg-subtle',
        accent: 'var(--color-unknown)',
    },
};

/** Derive a display status from a monitor's live fields. */
export function monitorStatus(monitor: Monitor): DisplayStatus {
    if (monitor.is_paused) {
        return 'paused';
    }
    if (monitor.has_open_incident || monitor.latest_status === 'failed') {
        return 'down';
    }
    if (monitor.latest_status === 'ok') {
        return 'up';
    }
    return 'unknown';
}

export function monitorStatusMeta(monitor: Monitor): StatusMeta {
    return STATUS_META[monitorStatus(monitor)];
}

const TYPE_LABELS: Record<string, string> = {
    http: 'HTTP',
    tcp: 'TCP',
    dns: 'DNS',
    ping: 'Ping',
    heartbeat: 'Heartbeat',
};

export function typeLabel(type: string): string {
    return TYPE_LABELS[type] ?? type.toUpperCase();
}
