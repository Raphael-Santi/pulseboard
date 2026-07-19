<script setup lang="ts">
import BrandMark from '@/components/BrandMark.vue';
import { useTheme } from '@/composables/useTheme';

const { theme, toggle } = useTheme();

const features = [
    {
        glyph: '⚡',
        title: 'Пять типов проверок',
        body: 'HTTP, TCP, DNS, Ping и пассивный Heartbeat для фоновых задач и cron.',
    },
    {
        glyph: '!',
        title: 'Инциденты и оповещения',
        body: 'Автоматически открываем инцидент при падении и шлём уведомление на email или в Telegram.',
    },
    {
        glyph: '◷',
        title: 'Публичные статус-страницы',
        body: 'Честная страница для клиентов: аптайм за 90 дней и живая история инцидентов.',
    },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-bg text-fg">
        <header class="mx-auto flex w-full max-w-[1200px] items-center justify-between px-6 py-5">
            <div class="flex items-center gap-3">
                <BrandMark :size="20" />
                <span class="text-lg font-bold">Pulseboard</span>
            </div>
            <div class="flex items-center gap-2.5">
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-border bg-surface text-fg-muted"
                    @click="toggle"
                >
                    {{ theme === 'dark' ? '☾' : '☀' }}
                </button>
                <RouterLink
                    to="/login"
                    class="rounded-[10px] border border-border bg-surface px-4 py-2 text-sm font-semibold text-fg"
                >
                    Войти
                </RouterLink>
                <RouterLink
                    to="/register"
                    class="rounded-[10px] bg-accent px-5 py-2 text-sm font-semibold text-white"
                >
                    Начать
                </RouterLink>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1200px] flex-1 px-6 py-[clamp(40px,7vw,96px)]">
            <div class="flex max-w-[760px] flex-col items-start">
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full bg-up-soft px-3 py-1.5 text-[12.5px] font-semibold text-up"
                >
                    <span class="pb-blink h-2 w-2 rounded-full bg-up" />
                    Все системы под наблюдением
                </div>
                <h1
                    class="text-[clamp(38px,6vw,66px)] leading-[1.03] font-bold tracking-tight text-balance"
                >
                    Узнавайте о сбоях раньше своих клиентов.
                </h1>
                <p
                    class="mt-5 max-w-[620px] text-[clamp(17px,2.4vw,21px)] text-fg-muted text-pretty"
                >
                    Pulseboard проверяет ваши сайты, API и серверы по расписанию, открывает инцидент
                    при падении и мгновенно оповещает команду. А клиентам показывает честную
                    публичную статус-страницу.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <RouterLink
                        to="/register"
                        class="rounded-xl bg-accent px-6 py-3.5 text-base font-semibold text-white"
                    >
                        Начать бесплатно
                    </RouterLink>
                    <RouterLink
                        to="/status/pulseboard-demo"
                        class="rounded-xl border border-border-strong bg-surface px-6 py-3.5 text-base font-semibold text-fg"
                    >
                        Открыть демо статус-страницу →
                    </RouterLink>
                </div>
            </div>

            <div class="mt-[clamp(56px,8vw,96px)] grid gap-4 md:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-2xl border border-border bg-surface p-6"
                >
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-[11px] bg-accent-soft text-lg font-bold text-accent"
                    >
                        {{ feature.glyph }}
                    </div>
                    <h3 class="text-[17px] font-semibold">{{ feature.title }}</h3>
                    <p class="mt-2 text-sm text-fg-muted text-pretty">{{ feature.body }}</p>
                </div>
            </div>
        </main>

        <footer
            class="mx-auto flex w-full max-w-[1200px] flex-wrap justify-between gap-3 border-t border-border px-6 py-8 text-[13px] text-fg-subtle"
        >
            <span>© 2026 Pulseboard</span>
            <span class="font-mono">мониторинг доступности · инциденты · статус-страницы</span>
        </footer>
    </div>
</template>
