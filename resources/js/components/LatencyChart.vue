<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

import type { LatencyPoint } from '@/types/metrics';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip);

const props = defineProps<{ points: LatencyPoint[] }>();

const chartData = computed(() => ({
    labels: props.points.map((point) =>
        new Date(point.t).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    ),
    datasets: [
        {
            label: 'Latency (ms)',
            data: props.points.map((point) => point.avg_ms),
            borderColor: '#34d399',
            backgroundColor: 'rgba(52, 211, 153, 0.12)',
            fill: true,
            tension: 0.3,
            pointRadius: 0,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b', maxTicksLimit: 8 } },
        y: { grid: { color: '#1e293b' }, ticks: { color: '#64748b' }, beginAtZero: true },
    },
    plugins: { legend: { display: false } },
} as const;
</script>

<template>
    <div class="h-64">
        <Line v-if="points.length > 0" :data="chartData" :options="chartOptions" />
        <p v-else class="flex h-full items-center justify-center text-sm text-slate-500">
            No latency data yet.
        </p>
    </div>
</template>
