<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import { validationErrors } from '@/lib/http';
import { useMonitorsStore } from '@/stores/monitors';
import type { HeartbeatInput } from '@/types/monitor';

const monitors = useMonitorsStore();
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
        // The detail page shows the generated ping URL.
        await router.push({ name: 'monitors.show', params: { id: monitor.id } });
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
            <h1 class="mt-3 text-2xl font-semibold">New heartbeat monitor</h1>
            <p class="mt-1 text-sm text-slate-400">
                Pulseboard waits for a periodic ping from your cron. If it does not arrive within
                the grace period, an incident is opened.
            </p>

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

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="interval_sec" class="block text-sm text-slate-400">
                            Expected interval (s)
                        </label>
                        <input
                            id="interval_sec"
                            v-model.number="form.interval_sec"
                            type="number"
                            min="60"
                            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                        />
                        <p v-if="errors.interval_sec" class="mt-1 text-sm text-red-400">
                            {{ errors.interval_sec[0] }}
                        </p>
                    </div>
                    <div>
                        <label for="grace_sec" class="block text-sm text-slate-400">
                            Grace period (s)
                        </label>
                        <input
                            id="grace_sec"
                            v-model.number="form.grace_sec"
                            type="number"
                            min="30"
                            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 focus:border-emerald-500 focus:outline-none"
                        />
                        <p v-if="errors.grace_sec" class="mt-1 text-sm text-red-400">
                            {{ errors.grace_sec[0] }}
                        </p>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="w-full rounded-md bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
                >
                    {{ processing ? 'Creating…' : 'Create heartbeat' }}
                </button>
            </form>
        </div>
    </main>
</template>
