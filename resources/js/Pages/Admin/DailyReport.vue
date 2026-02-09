<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppFlash from '@/Components/AppFlash.vue';

const props = defineProps({
    date: {
        type: String,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const selectedDate = ref(props.date);

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');

const totals = computed(() => {
    return props.rows.reduce(
        (acc, row) => {
            acc.students += row.total_students || 0;
            acc.hadir += row.hadir || 0;
            acc.terlambat += row.terlambat || 0;
            acc.alpha += row.alpha || 0;
            return acc;
        },
        { students: 0, hadir: 0, terlambat: 0, alpha: 0 }
    );
});

const overallRate = computed(() => {
    const present = totals.value.hadir + totals.value.terlambat;
    return totals.value.students > 0 ? ((present / totals.value.students) * 100).toFixed(1) : '0.0';
});

function applyFilter() {
    router.get('/admin/reports/daily', { date: selectedDate.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Rekap Harian" />

    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 md:text-3xl">Rekap Harian Kehadiran</h1>
                    <p class="mt-1 text-sm text-slate-600">Laporan ringkas per kelas dengan export 1 klik.</p>
                </div>
                <div class="flex gap-2">
                    <a
                        :href="`/admin/reports/daily/export?date=${selectedDate}&format=xlsx`"
                        class="inline-flex h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Export Excel (CSV)
                    </a>
                    <a
                        :href="`/admin/reports/daily/export?date=${selectedDate}&format=pdf`"
                        class="inline-flex h-11 items-center rounded-xl bg-teal-600 px-4 text-sm font-semibold text-white hover:bg-teal-700"
                    >
                        Export PDF
                    </a>
                </div>
            </div>

            <AppFlash :message="flashSuccess" type="success" />
            <AppFlash :message="flashError" type="error" />

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="h-12 rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-12 items-center rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white hover:bg-teal-700"
                        @click="applyFilter"
                    >
                        Tampilkan
                    </button>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Siswa</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ totals.students }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hadir</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ totals.hadir }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Terlambat</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ totals.terlambat }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rate Kehadiran</p>
                    <p class="mt-2 text-2xl font-bold text-teal-700">{{ overallRate }}%</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Kelas</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Total Siswa</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Hadir</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Terlambat</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Alpha</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada data pada tanggal ini.</td>
                            </tr>
                            <tr v-for="item in rows" :key="item.class_id">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ item.class_name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ item.total_students }}</td>
                                <td class="px-4 py-3 text-emerald-700">{{ item.hadir }}</td>
                                <td class="px-4 py-3 text-amber-700">{{ item.terlambat }}</td>
                                <td class="px-4 py-3 text-rose-700">{{ item.alpha }}</td>
                                <td class="px-4 py-3 font-semibold text-teal-700">{{ item.attendance_rate }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
