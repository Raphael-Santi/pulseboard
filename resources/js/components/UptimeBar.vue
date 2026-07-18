<script setup lang="ts">
import type { UptimeDay } from '@/types/status';

defineProps<{ days: UptimeDay[] }>();

function barClass(day: UptimeDay): string {
    if (day.uptime === null) {
        return 'bg-slate-800';
    }
    if (day.uptime >= 99) {
        return 'bg-emerald-500';
    }
    if (day.uptime >= 90) {
        return 'bg-amber-500';
    }
    return 'bg-red-500';
}

function title(day: UptimeDay): string {
    const value = day.uptime === null ? 'no data' : `${day.uptime}%`;
    return `${day.date}: ${value}`;
}
</script>

<template>
    <div class="flex items-end gap-[2px]">
        <span
            v-for="day in days"
            :key="day.date"
            class="h-8 w-[3px] rounded-sm"
            :class="barClass(day)"
            :title="title(day)"
        />
    </div>
</template>
