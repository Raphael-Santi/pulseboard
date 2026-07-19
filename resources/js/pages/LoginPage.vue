<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import BrandMark from '@/components/BrandMark.vue';
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
    <main class="grid min-h-screen place-items-center bg-bg px-6 text-fg">
        <div class="pb-fade w-full max-w-[420px]">
            <RouterLink to="/" class="mb-7 inline-flex items-center gap-2.5">
                <BrandMark :size="18" />
                <span class="text-[17px] font-bold">Pulseboard</span>
            </RouterLink>

            <div class="rounded-[18px] border border-border bg-surface p-8 shadow-xl">
                <h1 class="text-2xl font-semibold">С возвращением</h1>
                <p class="mt-1.5 text-sm text-fg-muted">Войдите, чтобы открыть свой дашборд.</p>

                <p
                    v-if="errors.form"
                    class="mt-5 rounded-[10px] border border-down bg-down-soft px-3.5 py-3 text-sm font-medium text-down"
                >
                    {{ errors.form[0] }}
                </p>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="you@company.com"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 text-sm text-fg focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.email" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.email[0] }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-[12.5px] font-semibold text-fg-muted"
                        >
                            Пароль
                        </label>
                        <input
                            id="password"
                            v-model="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full rounded-[10px] border border-border-strong bg-bg-2 px-3.5 py-2.5 text-sm text-fg focus:border-accent focus:outline-none"
                        />
                        <p v-if="errors.password" class="mt-1.5 text-[12.5px] text-down">
                            {{ errors.password[0] }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="flex w-full items-center justify-center gap-2 rounded-[11px] bg-accent px-4 py-3 text-[15px] font-semibold text-white disabled:opacity-75"
                    >
                        <span
                            v-if="processing"
                            class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"
                        />
                        {{ processing ? 'Подождите…' : 'Войти' }}
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-fg-muted">
                    Нет аккаунта?
                    <RouterLink to="/register" class="font-semibold text-accent">
                        Зарегистрироваться
                    </RouterLink>
                </p>
            </div>
        </div>
    </main>
</template>
