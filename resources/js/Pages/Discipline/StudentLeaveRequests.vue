<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppFlash from '@/Components/AppFlash.vue';

const props = defineProps({
    requests: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const form = useForm({
    request_date: new Date().toISOString().slice(0, 10),
    type: 'izin',
    reason: '',
});

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');

function submit() {
    form.post('/student/leave-requests', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('reason');
        },
    });
}

function statusClass(status) {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-700';
    if (status === 'rejected') return 'bg-rose-100 text-rose-700';
    return 'bg-amber-100 text-amber-700';
}
</script>

<template>
    <Head title="Pengajuan Izin" />

    <DashboardLayout>
        <div class="grid gap-6 lg:grid-cols-[420px,1fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                <h1 class="text-2xl font-semibold text-slate-900">Pengajuan Izin</h1>
                <p class="mt-1 text-sm text-slate-600">Ajukan izin/sakit/dispensasi langsung dari akun siswa.</p>

                <div class="mt-4 space-y-3">
                    <AppFlash :message="flashSuccess" type="success" />
                    <AppFlash :message="flashError" type="error" />
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                        <input
                            v-model="form.request_date"
                            type="date"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                        <p v-if="form.errors.request_date" class="mt-1 text-xs text-rose-600">{{ form.errors.request_date }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Jenis</label>
                        <select
                            v-model="form.type"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        >
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="dispensasi">Dispensasi</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-xs text-rose-600">{{ form.errors.type }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Alasan</label>
                        <textarea
                            v-model="form.reason"
                            rows="4"
                            maxlength="500"
                            placeholder="Contoh: Sakit demam dan istirahat di rumah."
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                        <p v-if="form.errors.reason" class="mt-1 text-xs text-rose-600">{{ form.errors.reason }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-teal-600 px-5 text-base font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Mengirim...' : 'Kirim Pengajuan' }}
                    </button>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Riwayat Pengajuan</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Jenis</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="requests.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada pengajuan.</td>
                            </tr>
                            <tr v-for="item in requests" :key="item.id">
                                <td class="px-4 py-3 text-slate-700">{{ item.request_date }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ item.type }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(item.status)">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <p>{{ item.reason }}</p>
                                    <p v-if="item.decision_note" class="mt-1 text-xs text-slate-500">Review: {{ item.decision_note }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
