<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue';
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

import { useAuthStore } from '@/stores/auth';
import { useMonitorsStore } from '@/stores/monitors';
import type { CheckRecordedEvent, IncidentEvent, Monitor } from '@/types/monitor';

const auth = useAuthStore();
const monitors = useMonitorsStore();
const router = useRouter();

const channel = `monitors.${auth.user?.id ?? 0}`;

// Live updates: the executor records a check → the server broadcasts, and the
// dashboard flips the monitor's status without a refresh.
useEcho<CheckRecordedEvent>(channel, '.check.recorded', (event) =>
    monitors.applyCheckResult(event),
);
useEcho<IncidentEvent>(channel, '.incident.opened', (event) => monitors.applyIncidentOpened(event));
useEcho<IncidentEvent>(channel, '.incident.closed', (event) => monitors.applyIncidentClosed(event));

onMounted(() => {
    void monitors.fetchAll();
});

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'home' });
}

async function togglePause(monitor: Monitor): Promise<void> {
    await monitors.togglePause(monitor.id);
}

async function remove(monitor: Monitor): Promise<void> {
    if (window.confirm(`Delete monitor “${monitor.name}”?`)) {
        await monitors.remove(monitor.id);
    }
}

interface StatusBadge {
    text: string;
    classes: string;
}

function statusBadge(monitor: Monitor): StatusBadge {
    if (monitor.is_paused) {
        return { text: 'Paused', classes: 'bg-slate-800 text-slate-400' };
    }
    if (monitor.has_open_incident || monitor.latest_status === 'failed') {
        return { text: 'Down', classes: 'bg-red-950 text-red-400' };
    }
    if (monitor.latest_status === 'ok') {
        return { text: 'Up', classes: 'bg-emerald-950 text-emerald-400' };
    }
    return { text: 'Unknown', classes: 'bg-slate-800 text-slate-500' };
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <span class="text-sm font-medium tracking-widest text-emerald-400">PULSEBOARD</span>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-slate-400">{{ auth.user?.email }}</span>
                    <button
                        type="button"
                        class="rounded-md border border-slate-700 px-3 py-1.5 text-slate-300 hover:bg-slate-900"
                        @click="logout"
                    >
                        Sign out
                    </button>
                </div>
            </div>
        </header>

        <section class="mx-auto max-w-5xl px-6 py-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Monitors</h1>
                    <p class="mt-1 text-sm text-slate-400">
                        Live status — updates over WebSockets as checks run.
                    </p>
                </div>
                <div class="flex gap-2">
                    <RouterLink
                        :to="{ name: 'heartbeats.create' }"
                        class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-900"
                    >
                        New heartbeat
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'monitors.create' }"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
                    >
                        New monitor
                    </RouterLink>
                </div>
            </div>

            <p v-if="monitors.loading" class="mt-8 text-slate-400">Loading…</p>

            <div
                v-else-if="monitors.monitors.length === 0"
                class="mt-8 rounded-lg border border-dashed border-slate-800 p-10 text-center"
            >
                <p class="text-slate-300">No monitors yet.</p>
                <p class="mt-1 text-sm text-slate-500">
                    Create your first monitor to start tracking uptime.
                </p>
            </div>

            <div v-else class="mt-8 overflow-hidden rounded-lg border border-slate-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-900 text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Target</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <tr v-for="monitor in monitors.monitors" :key="monitor.id">
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="statusBadge(monitor).classes"
                                >
                                    {{ statusBadge(monitor).text }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium">
                                <RouterLink
                                    :to="{ name: 'monitors.show', params: { id: monitor.id } }"
                                    class="text-slate-100 hover:text-emerald-400"
                                >
                                    {{ monitor.name }}
                                </RouterLink>
                            </td>
                            <td class="px-4 py-3 text-slate-400 uppercase">{{ monitor.type }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-slate-400">
                                {{ monitor.target
                                }}<template v-if="monitor.port">:{{ monitor.port }}</template>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3 text-xs">
                                    <RouterLink
                                        :to="{ name: 'monitors.edit', params: { id: monitor.id } }"
                                        class="text-slate-300 hover:text-emerald-400"
                                    >
                                        Edit
                                    </RouterLink>
                                    <button
                                        type="button"
                                        class="text-slate-300 hover:text-amber-400"
                                        @click="togglePause(monitor)"
                                    >
                                        {{ monitor.is_paused ? 'Resume' : 'Pause' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="text-slate-300 hover:text-red-400"
                                        @click="remove(monitor)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>
