<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { validationErrors } from '@/lib/http';
import { useMonitorsStore } from '@/stores/monitors';
import { ACTIVE_MONITOR_TYPES, type MonitorInput } from '@/types/monitor';

const monitors = useMonitorsStore();
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
        } else {
            await monitors.create({ ...form });
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
    <main class="min-h-screen bg-slate-950 px-6 py-10 text-slate-100">
        <div class="mx-auto max-w-lg">
            <RouterLink
                :to="{ name: 'dashboard' }"
                class="text-sm text-slate-400 hover:text-emerald-400"
            >
                ← Back to monitors
            </RouterLink>
            <h1 class="mt-3 text-2xl font-semibold">
                {{ isEdit ? 'Edit monitor' : 'New monitor' }}
            </h1>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <p v-if="errors.form" class="rounded-md bg-red-950 p-3 text-sm text-red-300">
                    {{ errors.form[0] }}
                </p>

                <div>
                    <label for="name" class="block text-sm text-slate-400">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                    />
                    <p v-if="errors.name" class="mt-1 text-sm text-red-400">{{ errors.name[0] }}</p>
                </div>

                <div>
                    <label for="type" class="block text-sm text-slate-400">Type</label>
                    <select
                        id="type"
                        v-model="form.type"
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                    >
                        <option v-for="type in ACTIVE_MONITOR_TYPES" :key="type" :value="type">
                            {{ type.toUpperCase() }}
                        </option>
                    </select>
                    <p v-if="errors.type" class="mt-1 text-sm text-red-400">{{ errors.type[0] }}</p>
                </div>

                <div>
                    <label for="target" class="block text-sm text-slate-400">
                        Target (URL or host)
                    </label>
                    <input
                        id="target"
                        v-model="form.target"
                        type="text"
                        required
                        placeholder="https://example.com"
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                    />
                    <p v-if="errors.target" class="mt-1 text-sm text-red-400">
                        {{ errors.target[0] }}
                    </p>
                </div>

                <div v-if="form.type === 'tcp'">
                    <label for="port" class="block text-sm text-slate-400">Port</label>
                    <input
                        id="port"
                        v-model.number="form.port"
                        type="number"
                        min="1"
                        max="65535"
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                    />
                    <p v-if="errors.port" class="mt-1 text-sm text-red-400">{{ errors.port[0] }}</p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="interval_sec" class="block text-sm text-slate-400">
                            Interval (s)
                        </label>
                        <input
                            id="interval_sec"
                            v-model.number="form.interval_sec"
                            type="number"
                            min="30"
                            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                        />
                        <p v-if="errors.interval_sec" class="mt-1 text-sm text-red-400">
                            {{ errors.interval_sec[0] }}
                        </p>
                    </div>
                    <div>
                        <label for="timeout_sec" class="block text-sm text-slate-400">
                            Timeout (s)
                        </label>
                        <input
                            id="timeout_sec"
                            v-model.number="form.timeout_sec"
                            type="number"
                            min="1"
                            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                        />
                        <p v-if="errors.timeout_sec" class="mt-1 text-sm text-red-400">
                            {{ errors.timeout_sec[0] }}
                        </p>
                    </div>
                    <div>
                        <label for="failure_threshold" class="block text-sm text-slate-400">
                            Failures
                        </label>
                        <input
                            id="failure_threshold"
                            v-model.number="form.failure_threshold"
                            type="number"
                            min="1"
                            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                        />
                        <p v-if="errors.failure_threshold" class="mt-1 text-sm text-red-400">
                            {{ errors.failure_threshold[0] }}
                        </p>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="w-full rounded-md bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
                >
                    {{ processing ? 'Saving…' : isEdit ? 'Save changes' : 'Create monitor' }}
                </button>
            </form>
        </div>
    </main>
</template>
