<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ from: '', to: '', class_id: null }),
    },
    classes: {
        type: Array,
        default: () => [],
    },
});

const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');
const classId = ref(props.filters.class_id || '');

function applyFilters() {
    router.get(
        '/discipline/late-history',
        {
            from: from.value || undefined,
            to: to.value || undefined,
            class_id: classId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Riwayat Keterlambatan" />

    <DashboardLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 md:text-3xl">Riwayat Keterlambatan</h1>
                <p class="mt-1 text-sm text-slate-600">Lacak siswa yang terlambat untuk tindak lanjut pembinaan.</p>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-3 md:grid-cols-[1fr,1fr,1fr,auto]">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Dari</label>
                        <input
                            v-model="from"
                            type="date"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Sampai</label>
                        <input
                            v-model="to"
                            type="date"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Kelas</label>
                        <select
                            v-model="classId"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        >
                            <option value="">Semua kelas</option>
                            <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white hover:bg-teal-700"
                        @click="applyFilters"
                    >
                        Tampilkan
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Waktu Scan</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Siswa</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Kelas</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Mulai Sesi</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Terlambat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="rows.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data keterlambatan pada rentang ini.</td>
                            </tr>
                            <tr v-for="item in rows" :key="item.id">
                                <td class="px-4 py-3 text-slate-700">{{ item.scanned_at }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ item.student_name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ item.class_name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ item.session_started_at }}</td>
                                <td class="px-4 py-3 font-semibold text-amber-700">{{ item.late_minutes }} menit</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
