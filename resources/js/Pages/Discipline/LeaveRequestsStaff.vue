<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppFlash from '@/Components/AppFlash.vue';

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    status_filter: {
        type: String,
        default: 'pending',
    },
    counts: {
        type: Object,
        default: () => ({ pending: 0, approved: 0, rejected: 0 }),
    },
});

const page = usePage();
const status = ref(props.status_filter);
const processingId = ref(null);

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');

function applyFilter() {
    router.get('/discipline/leave-requests', { status: status.value }, { preserveState: true, replace: true });
}

function decide(item, decision) {
    const note = window.prompt(`Catatan ${decision === 'approved' ? 'approve' : 'reject'} (opsional):`, '') ?? '';
    processingId.value = item.id;
    router.post(
        `/discipline/leave-requests/${item.id}/decision`,
        {
            decision,
            decision_note: note,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processingId.value = null;
            },
        }
    );
}

function statusClass(value) {
    if (value === 'approved') return 'bg-emerald-100 text-emerald-700';
    if (value === 'rejected') return 'bg-rose-100 text-rose-700';
    return 'bg-amber-100 text-amber-700';
}
</script>

<template>
    <Head title="Approval Izin" />

    <DashboardLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 md:text-3xl">Approval Izin Siswa</h1>
                <p class="mt-1 text-sm text-slate-600">Proses pengajuan izin dengan alur cepat dan jelas.</p>
            </div>

            <AppFlash :message="flashSuccess" type="success" />
            <AppFlash :message="flashError" type="error" />

            <section class="grid gap-4 sm:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ counts.pending }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Approved</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ counts.approved }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rejected</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ counts.rejected }}</p>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Filter status</label>
                        <select
                            v-model="status"
                            class="h-12 min-w-[220px] rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        >
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="all">Semua</option>
                        </select>
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

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Siswa</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Jenis</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Alasan</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada data pengajuan.</td>
                            </tr>
                            <tr v-for="item in rows" :key="item.id">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ item.student_name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ item.request_date }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ item.type }}</td>
                                <td class="max-w-[280px] px-4 py-3 text-slate-600">
                                    <p class="break-words">{{ item.reason }}</p>
                                    <p v-if="item.decision_note" class="mt-1 text-xs text-slate-500">Catatan: {{ item.decision_note }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(item.status)">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="item.status === 'pending'" class="flex gap-2">
                                        <button
                                            type="button"
                                            :disabled="processingId === item.id"
                                            class="inline-flex h-9 items-center rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                                            @click="decide(item, 'approved')"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="processingId === item.id"
                                            class="inline-flex h-9 items-center rounded-lg bg-rose-600 px-3 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
                                            @click="decide(item, 'rejected')"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                    <p v-else class="text-xs text-slate-500">Selesai diproses</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
