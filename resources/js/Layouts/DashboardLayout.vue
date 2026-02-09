<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const school = computed(() => page.props.school ?? null);

const navItems = computed(() => {
    const items = [];

    if (!user.value) {
        return items;
    }

    if (user.value.role === 'student') {
        items.push(
            { href: '/student/home', label: 'Beranda Siswa' },
            { href: '/student/id', label: 'Student ID' },
            { href: '/student/leave-requests', label: 'Pengajuan Izin' }
        );
    }

    if (['teacher', 'school_admin'].includes(user.value.role)) {
        items.push(
            { href: '/teacher/attendance/sessions', label: 'Absensi QR' },
            { href: '/discipline/leave-requests', label: 'Approval Izin' },
            { href: '/discipline/violations', label: 'Poin Pelanggaran' },
            { href: '/discipline/late-history', label: 'Riwayat Telat' }
        );
    }

    if (user.value.role === 'school_admin') {
        items.unshift({ href: '/admin/dashboard', label: 'Dashboard Sekolah' });
        items.push(
            { href: '/admin/reports/daily', label: 'Rekap Harian' },
            { href: '/admin/settings/branding', label: 'Branding' },
            { href: '/admin/settings/users', label: 'Role User' },
            { href: '/admin/activity-logs', label: 'Activity Log' }
        );
    }

    return items;
});

function isActive(href) {
    return page.url === href || page.url.startsWith(`${href}?`) || page.url.startsWith(`${href}/`);
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 md:px-6">
                <div class="flex items-center gap-3">
                    <img
                        v-if="school?.logo_url"
                        :src="school.logo_url"
                        alt="Logo sekolah"
                        class="h-9 w-9 rounded-xl border border-slate-200 object-cover"
                    />
                    <div
                        v-else
                        class="grid h-9 w-9 place-items-center rounded-xl bg-teal-600 text-sm font-bold text-white"
                    >
                        SA
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ school?.display_name || school?.name || 'SmartAttendance' }}
                        </p>
                        <p class="text-xs text-slate-500">Dashboard Sekolah</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <p class="hidden text-sm text-slate-600 sm:block">
                        {{ user?.name }}
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

            <div class="mx-auto w-full max-w-7xl px-4 pb-3 md:px-6">
                <nav class="flex gap-2 overflow-x-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="whitespace-nowrap rounded-lg border px-3 py-2 text-sm font-medium transition"
                        :class="isActive(item.href)
                            ? 'border-teal-600 bg-teal-600 text-white'
                            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-6 md:px-6 md:py-8">
            <slot />
        </main>
    </div>
</template>
