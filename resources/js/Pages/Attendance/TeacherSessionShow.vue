<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import StatusBadge from '@/Components/Attendance/StatusBadge.vue';
import BigActionButton from '@/Components/Attendance/BigActionButton.vue';
import AttendanceTable from '@/Components/Attendance/AttendanceTable.vue';
import SummaryCard from '@/Components/Attendance/SummaryCard.vue';
import AppFlash from '@/Components/AppFlash.vue';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const status = ref(props.session.status);
const qrImage = ref('');
const qrExpiresAt = ref('-');
const qrLoading = ref(false);
const listLoading = ref(false);
const actionLoading = ref(false);
const summary = ref({ hadir: 0, terlambat: 0, alpha: 0, total: 0 });
const records = ref([]);

let qrInterval = null;
let listInterval = null;

const sessionId = props.session.id;
const isOpen = computed(() => status.value === 'open');

function isoToLocalLabel(iso) {
    if (!iso) return '-';
    const date = new Date(iso);
    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

async function refreshQr() {
    if (!isOpen.value) {
        qrImage.value = '';
        qrExpiresAt.value = '-';
        return;
    }

    qrLoading.value = true;
    try {
        const response = await fetch(`/teacher/attendance/sessions/${sessionId}/qr`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) return;

        const payload = await response.json();
        const scanUrl = payload.data?.scan_url;
        qrExpiresAt.value = isoToLocalLabel(payload.data?.expires_at);

        if (scanUrl) {
            qrImage.value = await QRCode.toDataURL(scanUrl, {
                width: 300,
                margin: 1,
                errorCorrectionLevel: 'M',
            });
        }
    } finally {
        qrLoading.value = false;
    }
}

async function refreshRealtime() {
    listLoading.value = true;
    try {
        const response = await fetch(`/teacher/attendance/sessions/${sessionId}/realtime`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) return;

        const payload = await response.json();
        summary.value = payload.data?.summary ?? summary.value;
        records.value = payload.data?.records ?? [];
    } finally {
        listLoading.value = false;
    }
}

async function runAction(action) {
    actionLoading.value = true;
    try {
        const response = await fetch(`/teacher/attendance/sessions/${sessionId}/${action}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({}),
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            window.alert(payload.message || 'Proses gagal.');
            return;
        }

        status.value = payload.data?.status ?? status.value;
        await refreshQr();
        await refreshRealtime();
    } finally {
        actionLoading.value = false;
    }
}

function openSession() {
    runAction('open');
}

function closeSession() {
    runAction('close');
}

onMounted(() => {
    refreshQr();
    refreshRealtime();
    qrInterval = setInterval(refreshQr, 5000);
    listInterval = setInterval(refreshRealtime, 5000);
});

onBeforeUnmount(() => {
    clearInterval(qrInterval);
    clearInterval(listInterval);
});
</script>

<template>
    <Head :title="`Sesi #${session.id}`" />

    <DashboardLayout>
        <div class="space-y-6">
            <AppFlash :message="flash.success" type="success" />
            <AppFlash :message="flash.error" type="error" />

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="mb-2 flex items-center gap-3">
                            <Link href="/teacher/attendance/sessions" class="text-sm font-medium text-teal-700 hover:text-teal-800">&lt; Kembali</Link>
                        </div>
                        <h1 class="text-2xl font-semibold text-slate-900">Absensi QR Sesi #{{ session.id }}</h1>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ session.school_class?.name }} - {{ session.subject?.name }}
                        </p>
                    </div>
                    <StatusBadge :status="status" />
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-[340px,1fr]">
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:p-5">
                        <p class="text-sm font-semibold text-slate-800">QR Live</p>
                        <p class="mt-1 text-xs text-slate-500">Token otomatis berubah setiap {{ session.qr_rotate_seconds }} detik.</p>

                        <div class="mt-4 grid min-h-[320px] place-items-center rounded-xl border border-slate-200 bg-white">
                            <div v-if="qrLoading" class="h-64 w-64 animate-pulse rounded bg-slate-100" />
                            <img v-else-if="qrImage" :src="qrImage" alt="QR absensi" class="h-64 w-64" />
                            <p v-else class="px-4 text-center text-sm text-slate-500">Buka sesi untuk menampilkan QR.</p>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">Kadaluarsa token: {{ qrExpiresAt }}</p>

                        <div class="mt-4 grid gap-3">
                            <BigActionButton :loading="actionLoading" :disabled="isOpen" variant="primary" @click="openSession">
                                Buka Sesi
                            </BigActionButton>
                            <BigActionButton :loading="actionLoading" :disabled="!isOpen" variant="danger" @click="closeSession">
                                Tutup Sesi
                            </BigActionButton>
                        </div>

                        <div class="mt-4 grid gap-2">
                            <a :href="`/teacher/attendance/sessions/${session.id}/export?format=xlsx`" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                Export Excel (CSV)
                            </a>
                            <a :href="`/teacher/attendance/sessions/${session.id}/export?format=pdf`" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                Export PDF
                            </a>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <SummaryCard title="Hadir" :value="summary.hadir" />
                            <SummaryCard title="Terlambat" :value="summary.terlambat" />
                            <SummaryCard title="Alpha" :value="summary.alpha" />
                            <SummaryCard title="Total" :value="summary.total" />
                        </div>
                        <AttendanceTable :rows="records" :loading="listLoading" />
                    </section>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

