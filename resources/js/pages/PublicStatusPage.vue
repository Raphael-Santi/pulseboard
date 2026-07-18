<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

import UptimeBar from '@/components/UptimeBar.vue';
import { useStatusPagesStore } from '@/stores/statusPages';
import type { OverallStatus, PublicStatus } from '@/types/status';

const route = useRoute();
const store = useStatusPagesStore();

const status = ref<PublicStatus | null>(null);
const loading = ref(true);
const notFound = ref(false);

const overall = computed<Record<OverallStatus, { text: string; classes: string }>>(() => ({
    operational: { text: 'All systems operational', classes: 'bg-emerald-950 text-emerald-400' },
    degraded: { text: 'Partial outage', classes: 'bg-amber-950 text-amber-400' },
    down: { text: 'Major outage', classes: 'bg-red-950 text-red-400' },
    unknown: { text: 'Status unknown', classes: 'bg-slate-800 text-slate-400' },
}));

onMounted(async () => {
    try {
        status.value = await store.fetchPublic(String(route.params.slug));
    } catch {
        notFound.value = true;
    } finally {
        loading.value = false;
    }
});

function componentDot(componentStatus: string): string {
    return (
        {
            operational: 'bg-emerald-500',
            down: 'bg-red-500',
            unknown: 'bg-slate-600',
        }[componentStatus] ?? 'bg-slate-600'
    );
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 px-6 py-12 text-slate-100">
        <div class="mx-auto max-w-3xl">
            <p v-if="loading" class="text-slate-400">Loading…</p>

            <p v-else-if="notFound" class="text-slate-400">This status page does not exist.</p>

            <template v-else-if="status">
                <h1 class="text-3xl font-semibold">{{ status.title }}</h1>
                <div
                    class="mt-4 inline-flex rounded-md px-3 py-1.5 text-sm font-medium"
                    :class="overall[status.overall_status].classes"
                >
                    {{ overall[status.overall_status].text }}
                </div>

                <section class="mt-10 space-y-4">
                    <div
                        v-for="component in status.components"
                        :key="component.name"
                        class="rounded-lg border border-slate-800 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 rounded-full"
                                    :class="componentDot(component.status)"
                                />
                                <span class="font-medium">{{ component.name }}</span>
                            </div>
                            <span class="text-xs text-slate-500 capitalize">
                                {{ component.status }}
                            </span>
                        </div>
                        <div class="mt-3 overflow-x-auto">
                            <UptimeBar :days="component.uptime" />
                        </div>
                        <p class="mt-1 text-xs text-slate-600">90-day history</p>
                    </div>
                </section>

                <section class="mt-10">
                    <h2 class="text-sm font-medium text-slate-300">Recent incidents</h2>
                    <p v-if="status.incidents.length === 0" class="mt-3 text-sm text-slate-500">
                        No incidents reported.
                    </p>
                    <ul v-else class="mt-3 space-y-3">
                        <li
                            v-for="(incident, index) in status.incidents"
                            :key="index"
                            class="rounded-lg border border-slate-800 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-200">
                                    {{ incident.cause }}
                                </span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs"
                                    :class="
                                        incident.closed_at
                                            ? 'bg-slate-800 text-slate-400'
                                            : 'bg-red-950 text-red-400'
                                    "
                                >
                                    {{ incident.closed_at ? 'Resolved' : 'Ongoing' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ incident.opened_at }}</p>
                        </li>
                    </ul>
                </section>

                <footer class="mt-12 text-center text-xs text-slate-600">
                    Powered by Pulseboard
                </footer>
            </template>
        </div>
    </main>
</template>
