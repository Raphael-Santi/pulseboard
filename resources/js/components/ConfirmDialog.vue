<script setup lang="ts">
import { useUiStore } from '@/stores/ui';

const ui = useUiStore();
</script>

<template>
    <div
        v-if="ui.request"
        class="fixed inset-0 z-[110] flex items-center justify-center bg-black/55 p-5 backdrop-blur-sm"
        @click.self="ui.resolve(false)"
    >
        <div
            class="pb-fade w-full max-w-md rounded-2xl border border-border-strong bg-surface p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-down-soft text-lg font-bold text-down"
            >
                !
            </div>
            <h3 class="text-lg font-semibold text-fg">{{ ui.request.title }}</h3>
            <p class="mt-2 text-sm text-fg-muted">{{ ui.request.body }}</p>
            <div class="mt-6 flex justify-end gap-2.5">
                <button
                    type="button"
                    class="rounded-lg border border-border bg-surface-2 px-4 py-2.5 text-sm font-semibold text-fg"
                    @click="ui.resolve(false)"
                >
                    Отмена
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-down px-4 py-2.5 text-sm font-semibold text-white"
                    @click="ui.resolve(true)"
                >
                    {{ ui.request.confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
