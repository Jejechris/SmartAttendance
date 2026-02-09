<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    weekly: {
        type: Array,
        default: () => [],
    },
    top_classes: {
        type: Array,
        default: () => [],
    },
});

const maxWeeklyTotal = computed(() => {
    const totals = props.weekly.map((item) => (item.hadir || 0) + (item.terlambat || 0) + (item.alpha || 0));
    return Math.max(...totals, 1);
});
</script>

<template>
    <Head title="Dashboard Sekolah" />

    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 md:text-3xl">Dashboard Kepala Sekolah</h1>
                    <p class="mt-1 text-sm text-slate-600">Ringkasan kehadiran dan disiplin sekolah minggu ini.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin/reports/daily" class="inline-flex h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Rekap Harian
                    </Link>
                    <Link href="/admin/activity-logs" class="inline-flex h-11 items-center rounded-xl bg-teal-600 px-4 text-sm font-semibold text-white hover:bg-teal-700">
                        Activity Log
                    </Link>
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kehadiran Hari Ini</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ summary.attendance_rate_today }}%</p>
                    <p class="mt-1 text-sm text-slate-600">{{ summary.present_today }} dari {{ summary.total_students }} siswa hadir</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Siswa Hadir</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-600">{{ summary.present_today }}</p>
                    <p class="mt-1 text-sm text-slate-600">Jumlah siswa hadir hari ini</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Siswa Aktif</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ summary.total_students }}</p>
                    <p class="mt-1 text-sm text-slate-600">Data kelas aktif saat ini</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Izin Menunggu</p>
                    <p class="mt-3 text-3xl font-bold text-amber-600">{{ summary.pending_permits }}</p>
                    <p class="mt-1 text-sm text-slate-600">Perlu review guru/admin</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Poin Pelanggaran Bulan Ini</p>
                    <p class="mt-3 text-3xl font-bold text-rose-600">{{ summary.violation_points_month }}</p>
                    <p class="mt-1 text-sm text-slate-600">Akumulasi semua kategori</p>
                </article>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.4fr,1fr]">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Tren Kehadiran 7 Hari</h2>
                    <p class="mt-1 text-sm text-slate-600">Grafik sederhana hadir, terlambat, dan alpha.</p>

                    <div class="mt-5 grid grid-cols-7 gap-3">
                        <div v-for="item in weekly" :key="item.date" class="flex flex-col items-center gap-2">
                            <div class="flex h-44 w-full max-w-[56px] items-end gap-[3px] rounded-lg bg-slate-50 p-2 ring-1 ring-slate-200">
                                <div
                                    class="w-1/3 rounded-sm bg-emerald-500"
                                    :style="{ height: `${Math.max((item.hadir / maxWeeklyTotal) * 100, 2)}%` }"
                                    :title="`Hadir: ${item.hadir}`"
                                />
                                <div
                                    class="w-1/3 rounded-sm bg-amber-500"
                                    :style="{ height: `${Math.max((item.terlambat / maxWeeklyTotal) * 100, 2)}%` }"
                                    :title="`Terlambat: ${item.terlambat}`"
                                />
                                <div
                                    class="w-1/3 rounded-sm bg-rose-500"
                                    :style="{ height: `${Math.max((item.alpha / maxWeeklyTotal) * 100, 2)}%` }"
                                    :title="`Alpha: ${item.alpha}`"
                                />
                            </div>
                            <p class="text-xs font-medium text-slate-600">{{ item.label }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Kelas Paling Disiplin</h2>
                    <p class="mt-1 text-sm text-slate-600">Berdasarkan persentase hadir minggu ini.</p>

                    <div class="mt-4 space-y-3">
                        <article
                            v-for="item in top_classes"
                            :key="item.class_id"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900">{{ item.class_name }}</p>
                                <p class="text-sm font-bold text-teal-700">{{ item.rate }}%</p>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ item.present_count }} dari {{ item.total_count }} scan hadir/terlambat</p>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-teal-600" :style="{ width: `${item.rate}%` }" />
                            </div>
                        </article>
                        <p v-if="top_classes.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                            Belum ada data kelas minggu ini.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </DashboardLayout>
</template>
