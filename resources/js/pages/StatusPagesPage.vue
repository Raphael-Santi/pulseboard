<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';

import AppShell from '@/components/AppShell.vue';
import { validationErrors } from '@/lib/http';
import { monitorStatus } from '@/lib/status';
import { useMonitorsStore } from '@/stores/monitors';
import { useStatusPagesStore } from '@/stores/statusPages';
import { useUiStore } from '@/stores/ui';
import type { StatusPage, StatusPageInput } from '@/types/status';

const store = useStatusPagesStore();
const monitors = useMonitorsStore();
const ui = useUiStore();

const form = reactive<StatusPageInput>({ slug: '', title: '' });
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);

// Selected monitor ids per page, keyed by page id.
const selection = reactive<Record<number, Set<number>>>({});

onMounted(async () => {
    await Promise.all([store.fetchAll(), monitors.fetchAll()]);
    for (const page of store.pages) {
        selection[page.id] = new Set((page.monitors ?? []).map((monitor) => monitor.id));
    }
});

async function create(): Promise<void> {
    processing.value = true;
    errors.value = {};
    try {
        const page = await store.create({ ...form });
        selection[page.id] = new Set();
        form.slug = '';
        form.title = '';
        ui.notify('Статус-страница создана', 'up');
    } catch (error) {
        errors.value = validationErrors(error);
    } finally {
        processing.value = false;
    }
}

function toggle(pageId: number, monitorId: number): void {
    const set = selection[pageId] ?? new Set<number>();
    if (set.has(monitorId)) {
        set.delete(monitorId);
    } else {
        set.add(monitorId);
    }
    selection[pageId] = set;
}

async function save(page: StatusPage): Promise<void> {
    const ids = [...(selection[page.id] ?? [])];
    await store.syncMonitors(
        page.id,
        ids.map((id, index) => ({ id, sort_order: index })),
    );
    ui.notify('Компоненты сохранены');
}

async function remove(page: StatusPage): Promise<void> {
    const confirmed = await ui.confirm({
        title: 'Удалить статус-страницу?',
        body: `«${page.title}» перестанет быть доступна по публичной ссылке.`,
    });
    if (confirmed) {
        await store.remove(page.id);
        ui.notify('Страница удалена', 'down');
    }
}

function pageDot(page: StatusPage): string {
    const ids = new Set((page.monitors ?? []).map((monitor) => monitor.id));
    const attached = monitors.monitors.filter((monitor) => ids.has(monitor.id));
    if (attached.some((monitor) => monitorStatus(monitor) === 'down')) {
        return 'bg-down';
    }
    return 'bg-up';
}
</script>

<template>
    <AppShell>
        <h1 class="text-[26px] font-semibold tracking-tight">Статус-страницы</h1>
        <p class="mt-1.5 text-sm text-fg-muted">
            Публичные страницы, которые видят ваши клиенты. Соберите на них нужные мониторы под
            понятными названиями.
        </p>

        <!-- Existing pages -->
        <div class="mt-6 space-y-4">
            <div
                v-for="page in store.pages"
                :key="page.id"
                class="rounded-2xl border border-border bg-surface p-5"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full" :class="pageDot(page)" />
                            <span class="text-base font-semibold">{{ page.title }}</span>
                        </div>
                        <a
                            :href="`/status/${page.slug}`"
                            target="_blank"
                            class="mt-1 inline-block font-mono text-[12.5px] text-accent hover:underline"
                        >
                            /status/{{ page.slug }} →
                        </a>
                    </div>
                    <div class="flex gap-2">
                        <a
                            :href="`/status/${page.slug}`"
                            target="_blank"
                            class="rounded-[9px] border border-border-strong bg-surface-2 px-3.5 py-2 text-[13.5px] font-semibold text-fg"
                        >
                            Открыть
                        </a>
                        <button
                            type="button"
                            class="rounded-[9px] border border-border bg-surface-2 px-3.5 py-2 text-[13.5px] font-semibold text-down"
                            @click="remove(page)"
                        >
                            Удалить
                        </button>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="text-[12px] font-semibold tracking-wide text-fg-subtle uppercase">
                        Компоненты
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <label
                            v-for="monitor in monitors.monitors"
                            :key="monitor.id"
                            class="flex cursor-pointer items-center gap-1.5 rounded-[10px] border px-2.5 py-1.5 text-xs"
                            :class="
                                selection[page.id]?.has(monitor.id)
                                    ? 'border-accent bg-accent-soft text-fg'
                                    : 'border-border bg-bg-2 text-fg-muted'
                            "
                        >
                            <input
                                type="checkbox"
                                class="accent-accent"
                                :checked="selection[page.id]?.has(monitor.id)"
                                @change="toggle(page.id, monitor.id)"
                            />
                            {{ monitor.name }}
                        </label>
                    </div>
                    <button
                        type="button"
                        class="mt-3 rounded-[10px] border border-border-strong bg-surface-2 px-3.5 py-2 text-[13px] font-semibold text-fg"
                        @click="save(page)"
                    >
                        Сохранить компоненты
                    </button>
                </div>
            </div>
        </div>

        <!-- Create -->
        <div class="mt-6 max-w-2xl rounded-2xl border border-border bg-surface p-6">
            <h3 class="text-[17px] font-semibold">Новая статус-страница</h3>
            <form class="mt-4" @submit.prevent="create">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="sp-title"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Заголовок
                        </label>
                        <input
                            id="sp-title"
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Напр., Acme — статус"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 text-sm focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.title" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.title[0] }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="sp-slug"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Slug (адрес)
                        </label>
                        <div
                            class="flex items-center overflow-hidden rounded-[10px] border border-border-strong bg-bg-2"
                        >
                            <span class="pl-3.5 font-mono text-[13px] text-fg-subtle"
                                >/status/</span
                            >
                            <input
                                id="sp-slug"
                                v-model="form.slug"
                                type="text"
                                required
                                placeholder="acme"
                                class="flex-1 bg-transparent py-2.5 pr-3.5 pl-0.5 font-mono text-[13px] focus:outline-none"
                            />
                        </div>
                        <p v-if="errors.slug" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.slug[0] }}
                        </p>
                    </div>
                </div>
                <button
                    type="submit"
                    :disabled="processing"
                    class="mt-4 rounded-[11px] bg-accent px-6 py-3 text-[15px] font-semibold text-white disabled:opacity-75"
                >
                    Создать страницу
                </button>
            </form>
            <p class="mt-3 text-[12.5px] text-fg-subtle">
                После создания добавьте на страницу мониторы галочками выше.
            </p>
        </div>
    </AppShell>
</template>
