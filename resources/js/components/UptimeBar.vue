<script setup lang="ts">
import type { UptimeDay } from '@/types/status';

defineProps<{ days: UptimeDay[] }>();

function barClass(day: UptimeDay): string {
    if (day.uptime === null) {
        return 'bg-border';
    }
    if (day.uptime >= 99) {
        return 'bg-up';
    }
    if (day.uptime >= 90) {
        return 'bg-warn';
    }
    return 'bg-down';
}

function title(day: UptimeDay): string {
    const value = day.uptime === null ? 'нет данных' : `${day.uptime}%`;
    return `${day.date}: ${value}`;
}
</script>

<template>
    <div class="flex h-8 items-stretch gap-[2px]">
        <span
            v-for="day in days"
            :key="day.date"
            class="min-w-[2px] flex-1 rounded-[2px] transition hover:brightness-125"
            :class="barClass(day)"
            :title="title(day)"
        />
    </div>
</template>
