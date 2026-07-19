<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import AppShell from '@/components/AppShell.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { monitorStatus, STATUS_META, typeLabel } from '@/lib/status';
import { relativeTime } from '@/lib/time';
import { useAuthStore } from '@/stores/auth';
import { useMonitorsStore } from '@/stores/monitors';
import { useUiStore } from '@/stores/ui';
import type { CheckRecordedEvent, IncidentEvent, Monitor } from '@/types/monitor';

const auth = useAuthStore();
const monitors = useMonitorsStore();
const ui = useUiStore();
const router = useRouter();

const view = ref<'list' | 'cards'>('list');
const flashId = ref<number | null>(null);

const channel = `monitors.${auth.user?.id ?? 0}`;

function flash(id: number): void {
    flashId.value = id;
    setTimeout(() => {
        if (flashId.value === id) {
            flashId.value = null;
        }
    }, 1600);
}

useEcho<CheckRecordedEvent>(channel, '.check.recorded', (event) => {
    monitors.applyCheckResult(event);
    flash(event.monitor_id);
});
useEcho<IncidentEvent>(channel, '.incident.opened', (event) => {
    monitors.applyIncidentOpened(event);
    ui.notify(`${monitors.find(event.monitor_id)?.name ?? 'Монитор'} — открыт инцидент`, 'down');
});
useEcho<IncidentEvent>(channel, '.incident.closed', (event) => {
    monitors.applyIncidentClosed(event);
    ui.notify(`${monitors.find(event.monitor_id)?.name ?? 'Монитор'} восстановлен`, 'up');
});

onMounted(() => {
    void monitors.fetchAll();
});

const summary = computed(() => {
    const list = monitors.monitors;
    return [
        {
            label: 'Работают',
            value: list.filter((m) => monitorStatus(m) === 'up').length,
            dot: 'bg-up',
        },
        {
            label: 'Не работают',
            value: list.filter((m) => monitorStatus(m) === 'down').length,
            dot: 'bg-down',
        },
        { label: 'На паузе', value: list.filter((m) => m.is_paused).length, dot: 'bg-unknown' },
        { label: 'Всего', value: list.length, dot: 'bg-fg-subtle' },
    ];
});

function statusMeta(monitor: Monitor) {
    return STATUS_META[monitorStatus(monitor)];
}

function latency(monitor: Monitor): string {
    return monitor.latest_latency_ms === null ? '—' : `${monitor.latest_latency_ms} мс`;
}

function open(monitor: Monitor): void {
    void router.push({ name: 'monitors.show', params: { id: monitor.id } });
}

function edit(monitor: Monitor): void {
    void router.push({ name: 'monitors.edit', params: { id: monitor.id } });
}

async function togglePause(monitor: Monitor): Promise<void> {
    await monitors.togglePause(monitor.id);
    ui.notify(monitor.is_paused ? 'Монитор снят с паузы' : 'Монитор поставлен на паузу');
}

async function remove(monitor: Monitor): Promise<void> {
    const confirmed = await ui.confirm({
        title: 'Удалить монитор?',
        body: `«${monitor.name}» и вся его история будут удалены безвозвратно.`,
    });
    if (confirmed) {
        await monitors.remove(monitor.id);
        ui.notify('Монитор удалён', 'down');
    }
}
</script>

<template>
    <AppShell>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-[26px] font-semibold tracking-tight">Дашборд</h1>
                <div class="mt-1.5 flex items-center gap-2 text-[13px] text-fg-subtle">
                    <span class="pb-blink h-1.5 w-1.5 rounded-full bg-up" />
                    <span class="font-mono">обновления в реальном времени</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <RouterLink
                    :to="{ name: 'heartbeats.create' }"
                    class="rounded-[10px] border border-border-strong bg-surface px-4 py-2.5 text-sm font-semibold text-fg"
                >
                    + Heartbeat
                </RouterLink>
                <RouterLink
                    :to="{ name: 'monitors.create' }"
                    class="rounded-[10px] bg-accent px-4 py-2.5 text-sm font-semibold text-white"
                >
                    + Новый монитор
                </RouterLink>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="monitors.loading"
            class="mt-6 overflow-hidden rounded-2xl border border-border bg-surface"
        >
            <div
                v-for="n in 6"
                :key="n"
                class="pb-skeleton flex items-center gap-4 border-b border-border px-5 py-4 last:border-b-0"
            >
                <span class="h-6 w-6 flex-none rounded-lg bg-surface-2" />
                <span class="h-3 w-2/5 rounded bg-surface-2" />
                <span class="ml-auto h-3 w-16 rounded bg-surface-2" />
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="monitors.monitors.length === 0"
            class="mt-6 rounded-2xl border border-dashed border-border-strong bg-surface p-16 text-center"
        >
            <h3 class="text-lg font-semibold">Пока нет мониторов</h3>
            <p class="mx-auto mt-2 max-w-sm text-sm text-fg-muted">
                Добавьте первый монитор, чтобы Pulseboard начал следить за доступностью ваших
                сервисов.
            </p>
            <div class="mt-5 flex flex-wrap justify-center gap-2.5">
                <RouterLink
                    :to="{ name: 'monitors.create' }"
                    class="rounded-[10px] bg-accent px-5 py-2.5 text-sm font-semibold text-white"
                >
                    Создать монитор
                </RouterLink>
                <RouterLink
                    :to="{ name: 'heartbeats.create' }"
                    class="rounded-[10px] border border-border-strong bg-surface-2 px-5 py-2.5 text-sm font-semibold text-fg"
                >
                    Создать heartbeat
                </RouterLink>
            </div>
        </div>

        <!-- Data -->
        <template v-else>
            <div class="mt-6 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
                <div
                    v-for="card in summary"
                    :key="card.label"
                    class="rounded-2xl border border-border bg-surface p-4"
                >
                    <div class="flex items-center gap-2 text-[12.5px] font-semibold text-fg-subtle">
                        <span class="h-2.5 w-2.5 rounded-full" :class="card.dot" />
                        {{ card.label }}
                    </div>
                    <div class="mt-2.5 font-mono text-3xl font-bold tracking-tight">
                        {{ card.value }}
                    </div>
                </div>
            </div>

            <div class="mt-6 mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="text-[13.5px] text-fg-muted">
                    {{ monitors.monitors.length }} под наблюдением
                </div>
                <div class="flex gap-1.5 rounded-[10px] border border-border bg-surface-2 p-1">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-[13px] font-semibold"
                        :class="view === 'list' ? 'bg-surface text-fg' : 'text-fg-subtle'"
                        @click="view = 'list'"
                    >
                        Список
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-[13px] font-semibold"
                        :class="view === 'cards' ? 'bg-surface text-fg' : 'text-fg-subtle'"
                        @click="view = 'cards'"
                    >
                        Карточки
                    </button>
                </div>
            </div>

            <!-- List view -->
            <div
                v-if="view === 'list'"
                class="overflow-hidden rounded-2xl border border-border bg-surface"
            >
                <div
                    v-for="monitor in monitors.monitors"
                    :key="monitor.id"
                    class="flex cursor-pointer items-center gap-4 border-b border-border px-5 py-3.5 last:border-b-0 hover:bg-surface-2"
                    :class="{ 'pb-flash': flashId === monitor.id }"
                    :style="{ borderLeft: `3px solid ${statusMeta(monitor).accent}` }"
                    @click="open(monitor)"
                >
                    <span
                        class="flex h-7 w-7 flex-none items-center justify-center rounded-lg text-xs font-bold"
                        :class="statusMeta(monitor).pill"
                        :title="statusMeta(monitor).label"
                    >
                        {{ statusMeta(monitor).glyph }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold">{{ monitor.name }}</div>
                        <div class="truncate font-mono text-[11.5px] text-fg-subtle">
                            {{ monitor.target ?? 'heartbeat' }}
                        </div>
                    </div>
                    <span
                        class="hidden rounded-md border border-border bg-surface-2 px-2 py-0.5 text-[11.5px] font-semibold text-fg-muted sm:inline-block"
                    >
                        {{ typeLabel(monitor.type) }}
                    </span>
                    <span class="hidden w-20 font-mono text-[13px] text-fg-muted md:inline-block">
                        {{ latency(monitor) }}
                    </span>
                    <span class="hidden w-32 text-[12.5px] text-fg-subtle lg:inline-block">
                        {{ relativeTime(monitor.last_checked_at) }}
                    </span>
                    <div class="flex flex-none gap-1.5" @click.stop>
                        <button
                            type="button"
                            :title="monitor.is_paused ? 'Снять с паузы' : 'Пауза'"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-surface-2 text-fg-muted"
                            @click="togglePause(monitor)"
                        >
                            {{ monitor.is_paused ? '▶' : '⏸' }}
                        </button>
                        <button
                            type="button"
                            title="Редактировать"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-surface-2 text-fg-muted"
                            @click="edit(monitor)"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            title="Удалить"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-surface-2 text-down"
                            @click="remove(monitor)"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card view -->
            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="monitor in monitors.monitors"
                    :key="monitor.id"
                    class="cursor-pointer rounded-2xl border border-border bg-surface p-5 hover:border-border-strong"
                    :class="{ 'pb-flash': flashId === monitor.id }"
                    :style="{ borderTop: `3px solid ${statusMeta(monitor).accent}` }"
                    @click="open(monitor)"
                >
                    <div class="mb-3.5 flex items-center justify-between">
                        <StatusBadge :meta="statusMeta(monitor)" with-glyph />
                        <span class="text-[11.5px] font-semibold text-fg-subtle">
                            {{ typeLabel(monitor.type) }}
                        </span>
                    </div>
                    <div class="text-[15px] font-semibold">{{ monitor.name }}</div>
                    <div class="mt-0.5 truncate font-mono text-[11.5px] text-fg-subtle">
                        {{ monitor.target ?? 'heartbeat' }}
                    </div>
                    <div class="mt-3.5 flex justify-between text-[12.5px] text-fg-subtle">
                        <span
                            >Задержка
                            <b class="font-mono text-fg-muted">{{ latency(monitor) }}</b></span
                        >
                        <span>{{ relativeTime(monitor.last_checked_at) }}</span>
                    </div>
                </div>
            </div>
        </template>
    </AppShell>
</template>
