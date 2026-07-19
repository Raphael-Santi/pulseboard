<script setup lang="ts">
import { useUiStore } from '@/stores/ui';

const ui = useUiStore();

const toneDot: Record<string, string> = {
    up: 'bg-up',
    down: 'bg-down',
    accent: 'bg-accent',
};
</script>

<template>
    <div
        class="pointer-events-none fixed inset-x-0 bottom-6 z-[120] flex flex-col items-center gap-2 px-4"
    >
        <div
            v-for="toast in ui.toasts"
            :key="toast.id"
            class="pb-toast pointer-events-auto flex max-w-[90vw] items-center gap-3 rounded-xl border border-border-strong bg-elev px-4 py-3 shadow-xl"
        >
            <span class="h-2.5 w-2.5 flex-none rounded-sm" :class="toneDot[toast.tone]" />
            <span class="text-sm font-medium text-fg">{{ toast.message }}</span>
        </div>
    </div>
</template>
