<script setup lang="ts">
withDefaults(defineProps<{ size?: number }>(), { size: 20 });
</script>

<template>
    <span
        class="inline-flex flex-none items-center justify-center rounded-[9px] bg-accent-soft"
        :style="{ width: `${size + 14}px`, height: `${size + 14}px` }"
    >
        <svg
            :width="size"
            :height="size"
            viewBox="0 0 24 24"
            fill="none"
            stroke="var(--color-accent)"
            stroke-width="2.4"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M2 12h4l2.5-7 4 15L15.5 12H22" />
        </svg>
    </span>
</template>
