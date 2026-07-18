<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';

import { validationErrors } from '@/lib/http';
import { useMonitorsStore } from '@/stores/monitors';
import { useStatusPagesStore } from '@/stores/statusPages';
import type { StatusPage, StatusPageInput } from '@/types/status';

const store = useStatusPagesStore();
const monitors = useMonitorsStore();

const form = reactive<StatusPageInput>({ slug: '', title: '' });
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);

// Which monitor ids are selected for each page, keyed by page id.
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
}

async function remove(page: StatusPage): Promise<void> {
    if (window.confirm(`Delete status page “${page.title}”?`)) {
        await store.remove(page.id);
    }
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
                <RouterLink
                    :to="{ name: 'dashboard' }"
                    class="text-sm text-slate-400 hover:text-emerald-400"
                >
                    ← Monitors
                </RouterLink>
            </div>
        </header>

        <section class="mx-auto max-w-4xl px-6 py-10">
            <h1 class="text-2xl font-semibold">Status pages</h1>

            <form
                class="mt-6 flex flex-wrap items-end gap-3 rounded-lg border border-slate-800 p-4"
                @submit.prevent="create"
            >
                <div class="grow">
                    <label for="title" class="block text-sm text-slate-400">Title</label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        required
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                    />
                </div>
                <div class="grow">
                    <label for="slug" class="block text-sm text-slate-400">Slug</label>
                    <input
                        id="slug"
                        v-model="form.slug"
                        type="text"
                        required
                        placeholder="acme-status"
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                    />
                </div>
                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
                >
                    Create
                </button>
            </form>
            <p v-if="errors.slug" class="mt-2 text-sm text-red-400">{{ errors.slug[0] }}</p>

            <div class="mt-8 space-y-4">
                <div
                    v-for="page in store.pages"
                    :key="page.id"
                    class="rounded-lg border border-slate-800 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ page.title }}</p>
                            <a
                                :href="`/status/${page.slug}`"
                                target="_blank"
                                class="text-xs text-emerald-400 hover:underline"
                            >
                                /status/{{ page.slug }}
                            </a>
                        </div>
                        <button
                            type="button"
                            class="text-xs text-slate-400 hover:text-red-400"
                            @click="remove(page)"
                        >
                            Delete
                        </button>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs tracking-wide text-slate-500 uppercase">Components</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <label
                                v-for="monitor in monitors.monitors"
                                :key="monitor.id"
                                class="flex items-center gap-1.5 rounded-md border border-slate-700 px-2 py-1 text-xs"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selection[page.id]?.has(monitor.id)"
                                    @change="toggle(page.id, monitor.id)"
                                />
                                {{ monitor.name }}
                            </label>
                        </div>
                        <button
                            type="button"
                            class="mt-3 rounded-md border border-slate-700 px-3 py-1.5 text-xs text-slate-200 hover:bg-slate-900"
                            @click="save(page)"
                        >
                            Save components
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
