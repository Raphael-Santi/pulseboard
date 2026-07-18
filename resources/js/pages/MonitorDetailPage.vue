<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

import LatencyChart from '@/components/LatencyChart.vue';
import { useMonitorsStore } from '@/stores/monitors';
import type { Incident, MetricsWindow, MonitorMetrics } from '@/types/metrics';
import type { Monitor } from '@/types/monitor';

const route = useRoute();
const store = useMonitorsStore();

const monitorId = computed(() => Number(route.params.id));

const monitor = ref<Monitor | null>(null);
const metrics = ref<MonitorMetrics | null>(null);
const incidents = ref<Incident[]>([]);
const window = ref<MetricsWindow>('24h');
const loading = ref(true);

async function loadMetrics(): Promise<void> {
    metrics.value = await store.fetchMetrics(monitorId.value, window.value);
}

async function setWindow(next: MetricsWindow): Promise<void> {
    window.value = next;
    await loadMetrics();
}

async function acknowledge(incident: Incident): Promise<void> {
    const updated = await store.acknowledgeIncident(incident.id);
    incidents.value = incidents.value.map((item) => (item.id === updated.id ? updated : item));
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

function formatUptime(value: number | null): string {
    return value === null ? '—' : `${value}%`;
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
                <RouterLink
                    :to="{ name: 'dashboard' }"
                    class="text-sm text-slate-400 hover:text-emerald-400"
                >
                    ← Monitors
                </RouterLink>
            </div>
        </header>

        <section v-if="loading" class="mx-auto max-w-4xl px-6 py-10 text-slate-400">
            Loading…
        </section>

        <section v-else-if="monitor" class="mx-auto max-w-4xl px-6 py-10">
            <h1 class="text-2xl font-semibold">{{ monitor.name }}</h1>
            <p class="mt-1 text-sm text-slate-400">
                {{ monitor.type.toUpperCase() }} · {{ monitor.target
                }}<template v-if="monitor.port">:{{ monitor.port }}</template>
            </p>

            <div class="mt-8 grid grid-cols-3 gap-4">
                <div
                    v-for="key in ['24h', '7d', '30d'] as const"
                    :key="key"
                    class="rounded-lg border border-slate-800 bg-slate-900/40 p-4"
                >
                    <p class="text-xs tracking-wide text-slate-500 uppercase">Uptime {{ key }}</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-400">
                        {{ formatUptime(metrics?.uptime[key] ?? null) }}
                    </p>
                </div>
            </div>

            <div class="mt-8 rounded-lg border border-slate-800 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-300">Latency</h2>
                    <div class="flex gap-1 text-xs">
                        <button
                            v-for="option in ['24h', '7d'] as const"
                            :key="option"
                            type="button"
                            class="rounded-md px-2 py-1"
                            :class="
                                window === option
                                    ? 'bg-slate-800 text-emerald-400'
                                    : 'text-slate-400 hover:text-slate-200'
                            "
                            @click="setWindow(option)"
                        >
                            {{ option }}
                        </button>
                    </div>
                </div>
                <LatencyChart :points="metrics?.latency.points ?? []" />
            </div>

            <div class="mt-8">
                <h2 class="text-sm font-medium text-slate-300">Incidents</h2>
                <p v-if="incidents.length === 0" class="mt-3 text-sm text-slate-500">
                    No incidents recorded.
                </p>
                <ul v-else class="mt-3 space-y-3">
                    <li
                        v-for="incident in incidents"
                        :key="incident.id"
                        class="rounded-lg border border-slate-800 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="{
                                    'bg-red-950 text-red-400': incident.status === 'open',
                                    'bg-amber-950 text-amber-400':
                                        incident.status === 'acknowledged',
                                    'bg-slate-800 text-slate-400': incident.status === 'closed',
                                }"
                            >
                                {{ incident.status }}
                            </span>
                            <button
                                v-if="incident.status === 'open'"
                                type="button"
                                class="text-xs text-slate-400 hover:text-amber-400"
                                @click="acknowledge(incident)"
                            >
                                Acknowledge
                            </button>
                        </div>
                        <p class="mt-2 text-sm text-slate-300">{{ incident.cause }}</p>
                        <p class="mt-1 text-xs text-slate-500">Opened {{ incident.opened_at }}</p>
                    </li>
                </ul>
            </div>
        </section>
    </main>
</template>
