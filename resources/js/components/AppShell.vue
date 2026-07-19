<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import BrandMark from '@/components/BrandMark.vue';
import { useTheme } from '@/composables/useTheme';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const { theme, toggle } = useTheme();

const dashboardNames = [
    'dashboard',
    'monitors.show',
    'monitors.create',
    'monitors.edit',
    'heartbeats.create',
];

const dashboardActive = computed(() => dashboardNames.includes(String(route.name)));
const statusPagesActive = computed(() => route.name === 'status-pages');

const initials = computed(() => {
    const name = auth.user?.name ?? auth.user?.email ?? '?';
    return name
        .split(/\s+/)
        .map((part) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'home' });
}
</script>

<template>
    <div class="min-h-screen bg-bg text-fg">
        <!-- Desktop sidebar -->
        <aside
            class="fixed top-0 left-0 hidden h-screen w-60 flex-col border-r border-border bg-surface p-4 md:flex"
        >
            <RouterLink
                :to="{ name: 'dashboard' }"
                class="flex items-center gap-3 px-2 pt-1.5 pb-5"
            >
                <BrandMark :size="18" />
                <span class="text-[17px] font-bold text-fg">Pulseboard</span>
            </RouterLink>

            <nav class="flex flex-col gap-1">
                <RouterLink
                    :to="{ name: 'dashboard' }"
                    class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold"
                    :class="
                        dashboardActive
                            ? 'bg-accent-soft text-accent'
                            : 'text-fg-muted hover:bg-surface-2'
                    "
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <rect x="3" y="3" width="7" height="9" rx="1.5" />
                        <rect x="14" y="3" width="7" height="5" rx="1.5" />
                        <rect x="14" y="12" width="7" height="9" rx="1.5" />
                        <rect x="3" y="16" width="7" height="5" rx="1.5" />
                    </svg>
                    Дашборд
                </RouterLink>
                <RouterLink
                    :to="{ name: 'status-pages' }"
                    class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold"
                    :class="
                        statusPagesActive
                            ? 'bg-accent-soft text-accent'
                            : 'text-fg-muted hover:bg-surface-2'
                    "
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18" />
                    </svg>
                    Статус-страницы
                </RouterLink>
            </nav>

            <div class="mt-auto flex flex-col gap-2">
                <button
                    type="button"
                    class="flex items-center gap-3 rounded-[10px] border border-border bg-surface-2 px-3 py-2.5 text-[13px] font-medium text-fg-muted"
                    @click="toggle"
                >
                    <span>{{ theme === 'dark' ? '☾' : '☀' }}</span>
                    {{ theme === 'dark' ? 'Тёмная тема' : 'Светлая тема' }}
                </button>
                <div class="mt-1 flex items-center gap-2.5 border-t border-border px-1 pt-3">
                    <span
                        class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-accent text-xs font-bold text-white"
                    >
                        {{ initials }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[13px] font-semibold text-fg">
                            {{ auth.user?.name ?? 'Аккаунт' }}
                        </div>
                        <div class="truncate text-[11.5px] text-fg-subtle">
                            {{ auth.user?.email }}
                        </div>
                    </div>
                    <button
                        type="button"
                        title="Выйти"
                        class="flex h-8 w-8 flex-none items-center justify-center rounded-lg border border-border bg-surface text-fg-subtle hover:text-fg"
                        @click="logout"
                    >
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile top bar -->
        <header
            class="flex items-center justify-between border-b border-border bg-surface px-5 py-3 md:hidden"
        >
            <RouterLink :to="{ name: 'dashboard' }" class="flex items-center gap-2.5">
                <BrandMark :size="16" />
                <span class="text-base font-bold">Pulseboard</span>
            </RouterLink>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-surface-2 text-fg-muted"
                    @click="toggle"
                >
                    {{ theme === 'dark' ? '☾' : '☀' }}
                </button>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-surface-2 text-fg-subtle"
                    @click="logout"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Content -->
        <main class="pb-24 md:pb-0 md:pl-60">
            <div class="mx-auto w-full max-w-[1240px] px-5 py-6 md:px-11 md:py-9">
                <slot />
            </div>
        </main>

        <!-- Mobile bottom nav -->
        <nav
            class="fixed inset-x-0 bottom-0 z-[60] flex items-center justify-around border-t border-border bg-surface px-4 py-2 md:hidden"
        >
            <RouterLink
                :to="{ name: 'dashboard' }"
                class="rounded-lg px-4 py-2 text-sm font-semibold"
                :class="dashboardActive ? 'text-accent' : 'text-fg-muted'"
            >
                Дашборд
            </RouterLink>
            <RouterLink
                :to="{ name: 'status-pages' }"
                class="rounded-lg px-4 py-2 text-sm font-semibold"
                :class="statusPagesActive ? 'text-accent' : 'text-fg-muted'"
            >
                Статус-страницы
            </RouterLink>
        </nav>
    </div>
</template>
