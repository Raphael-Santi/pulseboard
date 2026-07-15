<template>
    <main class="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-slate-100">
        <div class="max-w-xl text-center">
            <p class="text-sm font-medium tracking-widest text-emerald-400 uppercase">Pulseboard</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-balance">
                Uptime monitoring with public status pages
            </h1>
            <p class="mt-4 text-lg text-slate-400">
                Monitors, automatic incidents, real-time dashboards and alerts — self-hosted.
            </p>
            <div class="mt-8 flex items-center justify-center gap-4">
                <RouterLink
                    to="/register"
                    class="rounded-md bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-500"
                >
                    Get started
                </RouterLink>
                <RouterLink
                    to="/login"
                    class="rounded-md border border-slate-700 px-5 py-2.5 font-medium text-slate-300 hover:bg-slate-900"
                >
                    Sign in
                </RouterLink>
            </div>
        </div>
    </main>
</template>
