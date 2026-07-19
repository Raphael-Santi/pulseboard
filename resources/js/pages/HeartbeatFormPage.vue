<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import AppShell from '@/components/AppShell.vue';
import { validationErrors } from '@/lib/http';
import { useMonitorsStore } from '@/stores/monitors';
import { useUiStore } from '@/stores/ui';
import type { HeartbeatInput } from '@/types/monitor';

const monitors = useMonitorsStore();
const ui = useUiStore();
const router = useRouter();

const form = reactive<HeartbeatInput>({
    name: '',
    interval_sec: 3600,
    grace_sec: 300,
});

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);

async function submit(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        const monitor = await monitors.createHeartbeat({ ...form });
        ui.notify('Heartbeat создан — скопируйте URL для пинга', 'up');
        await router.push({ name: 'monitors.show', params: { id: monitor.id } });
    } catch (error) {
        errors.value = validationErrors(error);
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <AppShell>
        <div class="max-w-2xl">
            <RouterLink
                :to="{ name: 'dashboard' }"
                class="mb-4 inline-flex items-center gap-1.5 text-[13.5px] font-medium text-fg-muted hover:text-accent"
            >
                ← Отмена
            </RouterLink>
            <h1 class="text-[26px] font-semibold tracking-tight">Новый heartbeat-монитор</h1>

            <div class="mt-4 mb-6 flex gap-3.5 rounded-2xl border border-accent bg-accent-soft p-4">
                <div class="flex-none text-xl leading-none">◔</div>
                <p class="text-[13.5px] text-fg text-pretty">
                    В отличие от обычных проверок, здесь
                    <b>ваш сервис сам пингует Pulseboard</b> по расписанию (например, из cron). Если
                    пинг не приходит вовремя — мы открываем инцидент. Тишина = проблема.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <p
                    v-if="errors.form"
                    class="rounded-[10px] border border-down bg-down-soft px-3.5 py-3 text-sm font-medium text-down"
                >
                    {{ errors.form[0] }}
                </p>

                <div>
                    <label
                        for="name"
                        class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                    >
                        Название
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Напр., Ночной бэкап БД"
                        class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 text-sm focus:border-accent focus:outline-none"
                    />
                    <p v-if="errors.name" class="mt-1.5 text-[12.5px] text-down">
                        {{ errors.name[0] }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            for="interval_sec"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Ожидаемый интервал, сек
                        </label>
                        <input
                            id="interval_sec"
                            v-model.number="form.interval_sec"
                            type="number"
                            min="60"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[13px] focus:border-accent focus:outline-none"
                        />
                        <p class="mt-1.5 text-[12px] text-fg-subtle">Как часто ждём пинг.</p>
                        <p v-if="errors.interval_sec" class="mt-1 text-[12.5px] text-down">
                            {{ errors.interval_sec[0] }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="grace_sec"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Льготный период, сек
                        </label>
                        <input
                            id="grace_sec"
                            v-model.number="form.grace_sec"
                            type="number"
                            min="30"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[13px] focus:border-accent focus:outline-none"
                        />
                        <p class="mt-1.5 text-[12px] text-fg-subtle">Запас перед инцидентом.</p>
                        <p v-if="errors.grace_sec" class="mt-1 text-[12.5px] text-down">
                            {{ errors.grace_sec[0] }}
                        </p>
                    </div>
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="flex items-center gap-2 rounded-[11px] bg-accent px-6 py-3 text-[15px] font-semibold text-white disabled:opacity-75"
                    >
                        <span
                            v-if="processing"
                            class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"
                        />
                        Создать и получить URL
                    </button>
                    <RouterLink
                        :to="{ name: 'dashboard' }"
                        class="rounded-[11px] border border-border bg-surface-2 px-6 py-3 text-[15px] font-semibold text-fg"
                    >
                        Отмена
                    </RouterLink>
                </div>
            </form>
        </div>
    </AppShell>
</template>
