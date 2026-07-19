<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppShell from '@/components/AppShell.vue';
import { validationErrors } from '@/lib/http';
import { typeLabel } from '@/lib/status';
import { useMonitorsStore } from '@/stores/monitors';
import { useUiStore } from '@/stores/ui';
import { ACTIVE_MONITOR_TYPES, type MonitorInput } from '@/types/monitor';

const monitors = useMonitorsStore();
const ui = useUiStore();
const router = useRouter();
const route = useRoute();

const monitorId = computed(() =>
    typeof route.params.id === 'string' ? Number(route.params.id) : null,
);
const isEdit = computed(() => monitorId.value !== null);

const form = reactive<MonitorInput>({
    name: '',
    type: 'http',
    target: '',
    port: null,
    interval_sec: 60,
    timeout_sec: 10,
    failure_threshold: 3,
});

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);

const showPort = computed(() => form.type === 'tcp');
const targetLabel = computed(() =>
    form.type === 'dns' ? 'Домен' : form.type === 'http' ? 'URL' : 'Хост',
);
const targetPlaceholder = computed(() =>
    form.type === 'http'
        ? 'https://example.com'
        : form.type === 'dns'
          ? 'example.com'
          : 'host.internal',
);

onMounted(async () => {
    if (!isEdit.value) {
        return;
    }

    if (!monitors.loaded) {
        await monitors.fetchAll();
    }

    const existing = monitors.find(monitorId.value as number);
    if (existing) {
        Object.assign(form, {
            name: existing.name,
            type: existing.type,
            target: existing.target ?? '',
            port: existing.port,
            interval_sec: existing.interval_sec,
            timeout_sec: existing.timeout_sec,
            failure_threshold: existing.failure_threshold,
        });
    }
});

async function submit(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        if (isEdit.value) {
            await monitors.update(monitorId.value as number, { ...form });
            ui.notify('Изменения сохранены');
        } else {
            await monitors.create({ ...form });
            ui.notify('Монитор создан', 'up');
        }
        await router.push({ name: 'dashboard' });
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
            <h1 class="text-[26px] font-semibold tracking-tight">
                {{ isEdit ? 'Редактирование монитора' : 'Новый монитор' }}
            </h1>
            <p class="mt-1.5 text-sm text-fg-muted">
                Активная проверка: Pulseboard сам обращается к цели по расписанию.
            </p>

            <form class="mt-6 space-y-5" @submit.prevent="submit">
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
                        placeholder="Напр., API-сервер"
                        class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 text-sm focus:border-accent focus:outline-none"
                    />
                    <p v-if="errors.name" class="mt-1.5 text-[12.5px] text-down">
                        {{ errors.name[0] }}
                    </p>
                </div>

                <div>
                    <span class="mb-2 block text-[12.5px] font-semibold text-fg-muted"
                        >Тип проверки</span
                    >
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="type in ACTIVE_MONITOR_TYPES"
                            :key="type"
                            type="button"
                            class="rounded-[10px] border px-4 py-2 text-[13.5px] font-semibold"
                            :class="
                                form.type === type
                                    ? 'border-accent bg-accent text-white'
                                    : 'border-border bg-surface-2 text-fg-muted'
                            "
                            @click="form.type = type"
                        >
                            {{ typeLabel(type) }}
                        </button>
                    </div>
                    <p v-if="errors.type" class="mt-1.5 text-[12.5px] text-down">
                        {{ errors.type[0] }}
                    </p>
                </div>

                <div class="grid gap-4" :class="showPort ? 'grid-cols-[1fr_140px]' : 'grid-cols-1'">
                    <div>
                        <label
                            for="target"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            {{ targetLabel }}
                        </label>
                        <input
                            id="target"
                            v-model="form.target"
                            type="text"
                            required
                            :placeholder="targetPlaceholder"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[13px] focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.target" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.target[0] }}
                        </p>
                    </div>
                    <div v-if="showPort">
                        <label
                            for="port"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Порт
                        </label>
                        <input
                            id="port"
                            v-model.number="form.port"
                            type="number"
                            min="1"
                            max="65535"
                            placeholder="5432"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[13px] focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.port" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.port[0] }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label
                            for="interval_sec"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Интервал, сек
                        </label>
                        <input
                            id="interval_sec"
                            v-model.number="form.interval_sec"
                            type="number"
                            min="30"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[13px] focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.interval_sec" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.interval_sec[0] }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="timeout_sec"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Таймаут, сек
                        </label>
                        <input
                            id="timeout_sec"
                            v-model.number="form.timeout_sec"
                            type="number"
                            min="1"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[13px] focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.timeout_sec" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.timeout_sec[0] }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="failure_threshold"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Порог отказов
                        </label>
                        <input
                            id="failure_threshold"
                            v-model.number="form.failure_threshold"
                            type="number"
                            min="1"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 font-mono text-[13px] focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.failure_threshold" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.failure_threshold[0] }}
                        </p>
                    </div>
                </div>
                <p class="text-[12.5px] text-fg-subtle">
                    Порог отказов — сколько неудачных проверок подряд до открытия инцидента.
                </p>

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
                        {{ isEdit ? 'Сохранить' : 'Создать монитор' }}
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
