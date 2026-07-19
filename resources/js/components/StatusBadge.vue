<script setup lang="ts">
import type { StatusMeta } from '@/lib/status';

withDefaults(defineProps<{ meta: StatusMeta; withGlyph?: boolean }>(), {
    withGlyph: false,
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold"
        :class="meta.pill"
    >
        <span v-if="withGlyph">{{ meta.glyph }}</span>
        {{ meta.label }}
    </span>
</template>
