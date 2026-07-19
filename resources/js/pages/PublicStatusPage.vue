<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

import BrandMark from '@/components/BrandMark.vue';
import UptimeBar from '@/components/UptimeBar.vue';
import { useTheme } from '@/composables/useTheme';
import { formatDateTime } from '@/lib/time';
import { useStatusPagesStore } from '@/stores/statusPages';
import type { OverallStatus, PublicStatus, StatusComponent } from '@/types/status';

const route = useRoute();
const store = useStatusPagesStore();
const { theme, toggle } = useTheme();

const status = ref<PublicStatus | null>(null);
const loading = ref(true);
const notFound = ref(false);

const overallMeta: Record<OverallStatus, { label: string; pill: string; glyph: string }> = {
    operational: { label: 'Все системы работают', pill: 'bg-up-soft text-up', glyph: '✓' },
    degraded: { label: 'Частичный сбой', pill: 'bg-warn-soft text-warn', glyph: '!' },
    down: { label: 'Крупный сбой', pill: 'bg-down-soft text-down', glyph: '!' },
    unknown: { label: 'Статус неизвестен', pill: 'bg-unknown-soft text-fg-muted', glyph: '?' },
};

const componentLabel: Record<string, string> = {
    operational: 'Работает',
    down: 'Не работает',
    unknown: 'Неизвестно',
};

function componentDot(componentStatus: string): string {
    return (
        {
            operational: 'bg-up',
            down: 'bg-down',
            unknown: 'bg-unknown',
        }[componentStatus] ?? 'bg-unknown'
    );
}

function uptime90(component: StatusComponent): string {
    const values = component.uptime.map((day) => day.uptime).filter((v): v is number => v !== null);
    if (values.length === 0) {
        return '—';
    }
    const avg = values.reduce((sum, value) => sum + value, 0) / values.length;
    return `${avg.toFixed(2)}%`;
}

onMounted(async () => {
    try {
        status.value = await store.fetchPublic(String(route.params.slug));
    } catch {
        notFound.value = true;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <main class="min-h-screen bg-bg text-fg">
        <div class="mx-auto max-w-[760px] px-5 py-[clamp(32px,6vw,64px)]">
            <div class="mb-[clamp(28px,5vw,44px)] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <BrandMark :size="19" />
                    <span class="text-lg font-bold">{{ status?.title ?? 'Pulseboard' }}</span>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-border bg-surface text-fg-muted"
                    @click="toggle"
                >
                    {{ theme === 'dark' ? '☾' : '☀' }}
                </button>
            </div>

            <p v-if="loading" class="text-fg-muted">Загрузка…</p>
            <p v-else-if="notFound" class="text-fg-muted">Такой статус-страницы не существует.</p>

            <template v-else-if="status">
                <!-- Overall banner -->
                <div
                    class="mb-4 flex items-center gap-4 rounded-2xl p-[clamp(20px,4vw,28px)]"
                    :class="overallMeta[status.overall_status].pill"
                >
                    <div
                        class="flex h-13 w-13 flex-none items-center justify-center rounded-2xl text-2xl font-bold"
                        :class="
                            componentDot(
                                status.overall_status === 'operational'
                                    ? 'operational'
                                    : status.overall_status === 'down'
                                      ? 'down'
                                      : 'unknown',
                            )
                        "
                    >
                        <span class="text-white">{{
                            overallMeta[status.overall_status].glyph
                        }}</span>
                    </div>
                    <h1 class="text-[clamp(20px,3.5vw,26px)] font-bold tracking-tight">
                        {{ overallMeta[status.overall_status].label }}
                    </h1>
                </div>

                <!-- Components -->
                <div class="mb-7 overflow-hidden rounded-2xl border border-border bg-surface">
                    <div
                        v-for="component in status.components"
                        :key="component.name"
                        class="border-b border-border p-5 last:border-b-0"
                    >
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <span class="font-semibold">{{ component.name }}</span>
                            <span class="flex items-center gap-2 text-[13px] font-semibold">
                                <span
                                    class="h-2 w-2 rounded-full"
                                    :class="componentDot(component.status)"
                                />
                                {{ componentLabel[component.status] ?? component.status }}
                            </span>
                        </div>
                        <UptimeBar :days="component.uptime" />
                        <div class="mt-2 flex justify-between text-[11.5px] text-fg-subtle">
                            <span>90 дней назад</span>
                            <span>аптайм {{ uptime90(component) }}</span>
                            <span>сегодня</span>
                        </div>
                    </div>
                </div>

                <!-- Incidents -->
                <h2 class="mb-3.5 text-[17px] font-semibold">Недавние инциденты</h2>
                <div
                    v-if="status.incidents.length === 0"
                    class="rounded-2xl border border-border bg-surface p-7 text-center text-sm text-fg-subtle"
                >
                    За последние 90 дней инцидентов не было.
                </div>
                <div v-else class="flex flex-col gap-3">
                    <div
                        v-for="(incident, index) in status.incidents"
                        :key="index"
                        class="rounded-2xl border border-border bg-surface p-4"
                        :style="{
                            borderLeft: `3px solid ${incident.closed_at ? 'var(--color-unknown)' : 'var(--color-down)'}`,
                        }"
                    >
                        <div class="mb-1.5 flex flex-wrap items-center gap-2.5">
                            <span
                                class="rounded-md px-2 py-0.5 text-[11.5px] font-bold"
                                :class="
                                    incident.closed_at
                                        ? 'bg-unknown-soft text-fg-muted'
                                        : 'bg-down-soft text-down'
                                "
                            >
                                {{ incident.closed_at ? 'Решён' : 'Идёт сейчас' }}
                            </span>
                            <span class="text-[14.5px] font-semibold">{{ incident.cause }}</span>
                        </div>
                        <div class="text-[13px] text-fg-muted">
                            {{ formatDateTime(incident.opened_at) }}
                            <template v-if="incident.closed_at">
                                — {{ formatDateTime(incident.closed_at) }}
                            </template>
                        </div>
                    </div>
                </div>

                <footer class="mt-10 text-center text-[12.5px] text-fg-subtle">
                    Работает на
                    <RouterLink to="/" class="font-semibold text-accent">Pulseboard</RouterLink>
                </footer>
            </template>
        </div>
    </main>
</template>
