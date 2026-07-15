<script setup lang="ts">
import { useRouter } from 'vue-router';

import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'home' });
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <span class="text-sm font-medium tracking-widest text-emerald-400">
                    PULSEBOARD
                </span>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-slate-400">{{ auth.user?.email }}</span>
                    <button
                        type="button"
                        class="rounded-md border border-slate-700 px-3 py-1.5 text-slate-300 hover:bg-slate-900"
                        @click="logout"
                    >
                        Sign out
                    </button>
                </div>
            </div>
        </header>

        <section class="mx-auto max-w-5xl px-6 py-10">
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="mt-2 text-slate-400">
                Welcome, {{ auth.user?.name }}. Monitors will appear here in the next module.
            </p>
        </section>
    </main>
</template>
