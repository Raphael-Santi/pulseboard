<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppShell from '@/components/AppShell.vue';
import LatencyChart from '@/components/LatencyChart.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { monitorStatusMeta, typeLabel } from '@/lib/status';
import { formatDateTime, relativeTime } from '@/lib/time';
import { useMonitorsStore } from '@/stores/monitors';
import { useUiStore } from '@/stores/ui';
import type { Incident, MetricsWindow, MonitorMetrics } from '@/types/metrics';
import type { Monitor } from '@/types/monitor';

const route = useRoute();
const router = useRouter();
const store = useMonitorsStore();
const ui = useUiStore();

const monitorId = computed(() => Number(route.params.id));

const monitor = ref<Monitor | null>(null);
const metrics = ref<MonitorMetrics | null>(null);
const incidents = ref<Incident[]>([]);
const activeWindow = ref<MetricsWindow>('24h');
const loading = ref(true);

const pingUrl = computed(() =>
    monitor.value?.token ? `${globalThis.location.origin}/api/hb/${monitor.value.token}` : null,
);

const uptimeCards = computed(() => [
    { range: '24 часа', value: metrics.value?.uptime['24h'] ?? null },
    { range: '7 дней', value: metrics.value?.uptime['7d'] ?? null },
    { range: '30 дней', value: metrics.value?.uptime['30d'] ?? null },
]);

const latencyStats = computed(() => {
    const points = metrics.value?.latency.points ?? [];
    if (points.length === 0) {
        return { avg: '—', max: '—' };
    }
    const values = points.map((point) => point.avg_ms);
    const avg = Math.round(values.reduce((sum, value) => sum + value, 0) / values.length);
    return { avg: `${avg} мс`, max: `${Math.max(...values)} мс` };
});

const incidentMeta: Record<string, { label: string; pill: string }> = {
    open: { label: 'Открыт', pill: 'bg-down-soft text-down' },
    acknowledged: { label: 'Принят в работу', pill: 'bg-warn-soft text-warn' },
    closed: { label: 'Решён', pill: 'bg-unknown-soft text-fg-muted' },
};

async function loadMetrics(): Promise<void> {
    metrics.value = await store.fetchMetrics(monitorId.value, activeWindow.value);
}

async function setWindow(next: MetricsWindow): Promise<void> {
    activeWindow.value = next;
    await loadMetrics();
}

async function togglePause(): Promise<void> {
    if (!monitor.value) {
        return;
    }
    const wasPaused = monitor.value.is_paused;
    await store.togglePause(monitor.value.id);
    monitor.value = store.find(monitorId.value) ?? monitor.value;
    ui.notify(wasPaused ? 'Монитор снят с паузы' : 'Монитор поставлен на паузу');
}

async function remove(): Promise<void> {
    if (!monitor.value) {
        return;
    }
    const confirmed = await ui.confirm({
        title: 'Удалить монитор?',
        body: `«${monitor.value.name}» и вся его история будут удалены безвозвратно.`,
    });
    if (confirmed) {
        await store.remove(monitor.value.id);
        ui.notify('Монитор удалён', 'down');
        await router.push({ name: 'dashboard' });
    }
}

async function acknowledge(incident: Incident): Promise<void> {
    const updated = await store.acknowledgeIncident(incident.id);
    incidents.value = incidents.value.map((item) => (item.id === updated.id ? updated : item));
    ui.notify('Инцидент принят в работу');
}

function copyPing(): void {
    if (pingUrl.value) {
        void navigator.clipboard?.writeText(pingUrl.value);
        ui.notify('URL скопирован');
    }
}

onMounted(async () => {
    try {
        monitor.value = store.find(monitorId.value) ?? (await store.fetchOne(monitorId.value));
        [, incidents.value] = await Promise.all([
            loadMetrics(),
            store.fetchIncidents(monitorId.value),
        ]);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <AppShell>
        <RouterLink
            :to="{ name: 'dashboard' }"
            class="mb-4 inline-flex items-center gap-1.5 text-[13.5px] font-medium text-fg-muted hover:text-accent"
        >
            ← Назад к дашборду
        </RouterLink>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col gap-4">
            <div class="pb-skeleton h-20 rounded-2xl bg-surface" />
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="pb-skeleton h-24 rounded-2xl bg-surface" />
                <div class="pb-skeleton h-24 rounded-2xl bg-surface" />
                <div class="pb-skeleton h-24 rounded-2xl bg-surface" />
            </div>
            <div class="pb-skeleton h-56 rounded-2xl bg-surface" />
        </div>

        <template v-else-if="monitor">
            <!-- Header -->
            <div
                class="flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-border bg-surface p-6"
                :style="{ borderLeft: `4px solid ${monitorStatusMeta(monitor).accent}` }"
            >
                <div class="min-w-0">
                    <div class="mb-2 flex flex-wrap items-center gap-3">
                        <StatusBadge :meta="monitorStatusMeta(monitor)" with-glyph />
                        <span
                            class="rounded-md border border-border bg-surface-2 px-2.5 py-1 text-xs font-semibold text-fg-muted"
                        >
                            {{ typeLabel(monitor.type) }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ monitor.name }}</h1>
                    <div class="mt-1 font-mono text-[13px] text-fg-subtle">
                        {{ monitor.target ?? 'heartbeat' }}
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-[9px] border border-border-strong bg-surface-2 px-3.5 py-2 text-[13.5px] font-semibold text-fg"
                        @click="togglePause"
                    >
                        {{ monitor.is_paused ? 'Снять с паузы' : 'Пауза' }}
                    </button>
                    <RouterLink
                        :to="{ name: 'monitors.edit', params: { id: monitor.id } }"
                        class="rounded-[9px] border border-border-strong bg-surface-2 px-3.5 py-2 text-[13.5px] font-semibold text-fg"
                    >
                        Редактировать
                    </RouterLink>
                    <button
                        type="button"
                        class="rounded-[9px] border border-border bg-surface-2 px-3.5 py-2 text-[13.5px] font-semibold text-down"
                        @click="remove"
                    >
                        Удалить
                    </button>
                </div>
            </div>

            <!-- Heartbeat ping box -->
            <div
                v-if="monitor.type === 'heartbeat' && pingUrl"
                class="mt-4 rounded-2xl border border-accent bg-accent-soft p-6"
            >
                <div class="text-sm font-semibold text-fg">Секретный URL для пинга</div>
                <p class="mt-1 mb-3.5 text-[13px] text-fg-muted">
                    Вставьте этот адрес в свой cron. Если Pulseboard не получит запрос вовремя —
                    откроется инцидент.
                </p>
                <div class="flex flex-wrap items-center gap-2.5">
                    <code
                        class="flex-1 overflow-x-auto rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[12.5px] whitespace-nowrap"
                    >
                        POST {{ pingUrl }}
                    </code>
                    <button
                        type="button"
                        class="rounded-[10px] bg-accent px-4 py-2.5 text-[13.5px] font-semibold whitespace-nowrap text-white"
                        @click="copyPing"
                    >
                        Копировать
                    </button>
                </div>
                <div class="mt-3 text-[12.5px] text-fg-muted">
                    Последний пинг:
                    <b class="font-mono">{{ relativeTime(monitor.last_ping_at) }}</b>
                </div>
            </div>

            <!-- Uptime cards -->
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div
                    v-for="card in uptimeCards"
                    :key="card.range"
                    class="rounded-2xl border border-border bg-surface p-5"
                >
                    <div class="text-[12.5px] font-semibold text-fg-subtle">
                        Аптайм · {{ card.range }}
                    </div>
                    <div class="mt-2 font-mono text-3xl font-bold tracking-tight text-up">
                        {{ card.value === null ? '—' : `${card.value}%` }}
                    </div>
                </div>
            </div>

            <!-- Latency chart -->
            <div class="mt-4 rounded-2xl border border-border bg-surface p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold">Задержка ответа</h2>
                        <div class="text-[12.5px] text-fg-subtle">
                            среднее <b class="font-mono text-fg-muted">{{ latencyStats.avg }}</b> ·
                            пик <b class="font-mono text-fg-muted">{{ latencyStats.max }}</b>
                        </div>
                    </div>
                    <div class="flex gap-1.5 rounded-[10px] border border-border bg-surface-2 p-1">
                        <button
                            type="button"
                            class="rounded-md px-3.5 py-1.5 text-[13px] font-semibold"
                            :class="
                                activeWindow === '24h' ? 'bg-surface text-fg' : 'text-fg-subtle'
                            "
                            @click="setWindow('24h')"
                        >
                            24ч
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3.5 py-1.5 text-[13px] font-semibold"
                            :class="activeWindow === '7d' ? 'bg-surface text-fg' : 'text-fg-subtle'"
                            @click="setWindow('7d')"
                        >
                            7д
                        </button>
                    </div>
                </div>
                <LatencyChart :points="metrics?.latency.points ?? []" />
            </div>

            <!-- Incidents -->
            <div class="mt-4 rounded-2xl border border-border bg-surface p-6">
                <h2 class="mb-4 text-base font-semibold">История инцидентов</h2>
                <div v-if="incidents.length === 0" class="py-8 text-center text-sm text-fg-subtle">
                    <div class="mb-2 text-xl text-up">✓</div>
                    Инцидентов не было. Монитор работает стабильно.
                </div>
                <div v-else class="flex flex-col gap-3">
                    <div
                        v-for="incident in incidents"
                        :key="incident.id"
                        class="flex gap-4 rounded-xl border border-border bg-bg-2 p-4"
                        :style="{
                            borderLeft: `3px solid ${incident.status === 'closed' ? 'var(--color-unknown)' : incident.status === 'acknowledged' ? 'var(--color-warn)' : 'var(--color-down)'}`,
                        }"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="mb-1.5 flex flex-wrap items-center gap-2.5">
                                <span
                                    class="rounded-md px-2 py-0.5 text-[11.5px] font-bold"
                                    :class="incidentMeta[incident.status]?.pill"
                                >
                                    {{ incidentMeta[incident.status]?.label }}
                                </span>
                                <span class="text-sm font-semibold">{{ incident.cause }}</span>
                            </div>
                            <div class="text-[12.5px] text-fg-subtle">
                                Начало {{ formatDateTime(incident.opened_at) }}
                                <template v-if="incident.closed_at">
                                    · решён {{ formatDateTime(incident.closed_at) }}
                                </template>
                                <template v-else>· длится сейчас</template>
                            </div>
                        </div>
                        <button
                            v-if="incident.status === 'open'"
                            type="button"
                            class="self-center rounded-[9px] border border-border-strong bg-surface px-3 py-2 text-[13px] font-semibold whitespace-nowrap text-fg"
                            @click="acknowledge(incident)"
                        >
                            Принять в работу
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </AppShell>
</template>
