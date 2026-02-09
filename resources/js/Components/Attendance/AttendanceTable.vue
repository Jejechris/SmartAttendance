<script setup>
defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Siswa</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Waktu</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Terlambat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">Memuat data absensi...</td>
                    </tr>
                    <tr v-else-if="rows.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada data scan.</td>
                    </tr>
                    <tr v-for="item in rows" :key="`${item.student}-${item.scanned_at}-${item.status}`">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ item.student ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="{
                                    'bg-emerald-100 text-emerald-700': item.status === 'hadir',
                                    'bg-amber-100 text-amber-700': item.status === 'terlambat',
                                    'bg-rose-100 text-rose-700': item.status === 'alpha',
                                }"
                            >
                                {{ item.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ item.scanned_at ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ item.late_minutes ?? 0 }} menit</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
