<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { validationErrors } from '@/lib/http';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);

async function submit(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        await auth.login({ email: email.value, password: password.value });

        const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null;
        await router.push(redirect ?? { name: 'dashboard' });
    } catch (error) {
        errors.value = validationErrors(error);
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-slate-100">
        <div class="w-full max-w-sm">
            <RouterLink to="/" class="text-sm font-medium tracking-widest text-emerald-400">
                PULSEBOARD
            </RouterLink>
            <h1 class="mt-4 text-2xl font-semibold">Sign in</h1>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <p v-if="errors.form" class="rounded-md bg-red-950 p-3 text-sm text-red-300">
                    {{ errors.form[0] }}
                </p>

                <div>
                    <label for="email" class="block text-sm text-slate-400">Email</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        required
                        autocomplete="email"
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none"
                    />
                    <p v-if="errors.email" class="mt-1 text-sm text-red-400">
                        {{ errors.email[0] }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm text-slate-400">Password</label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none"
                    />
                    <p v-if="errors.password" class="mt-1 text-sm text-red-400">
                        {{ errors.password[0] }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="w-full rounded-md bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
                >
                    {{ processing ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>

            <p class="mt-6 text-sm text-slate-400">
                No account?
                <RouterLink to="/register" class="text-emerald-400 hover:underline">
                    Create one
                </RouterLink>
            </p>
        </div>
    </main>
</template>
