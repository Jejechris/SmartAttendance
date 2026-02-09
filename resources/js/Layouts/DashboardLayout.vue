<script setup>
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 md:px-6">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-teal-600 text-sm font-bold text-white">
                        SA
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">SmartAttendance</p>
                        <p class="text-xs text-slate-500">QR Attendance Dashboard</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <p class="hidden text-sm text-slate-600 sm:block">
                        {{ page.props.auth?.user?.name }}
                    </p>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                        @click="logout"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-6 md:px-6 md:py-8">
            <slot />
        </main>
    </div>
</template>
