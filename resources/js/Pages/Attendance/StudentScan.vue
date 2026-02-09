<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const loading = ref(false);
const state = ref('idle');
const message = ref('');

function getLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Browser tidak mendukung lokasi.'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                resolve({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                });
            },
            () => reject(new Error('Lokasi diperlukan untuk absensi sesi ini.')),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
}

async function submitAttendance() {
    loading.value = true;
    state.value = 'idle';
    message.value = '';

    try {
        let lat = null;
        let lng = null;

        if (props.session.location_validation) {
            const location = await getLocation();
            lat = location.lat;
            lng = location.lng;
        }

        const response = await fetch('/attendance/check-in', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({
                session_id: props.session.id,
                token: props.token,
                lat,
                lng,
            }),
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload.message || 'Absensi gagal diproses.');
        }

        state.value = 'success';
        message.value = `Absensi berhasil: ${payload.data?.status ?? 'hadir'}`;
    } catch (error) {
        state.value = 'error';
        message.value = error instanceof Error ? error.message : 'Terjadi kesalahan.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Scan Absensi" />

    <div class="min-h-screen bg-slate-50 px-4 py-6">
        <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Absensi QR</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Scan Kehadiran</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Sesi #{{ session.id }} - {{ session.location_validation ? 'Lokasi wajib aktif' : 'Tanpa validasi lokasi' }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-14 w-full items-center justify-center rounded-xl bg-teal-600 px-5 text-base font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-200 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="loading"
                @click="submitAttendance"
            >
                <svg
                    v-if="loading"
                    class="mr-2 h-5 w-5 animate-spin"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4" />
                    <path d="M22 12A10 10 0 0012 2" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                </svg>
                {{ loading ? 'Memproses absensi...' : 'Kirim Absensi' }}
            </button>

            <div
                v-if="message"
                class="mt-4 rounded-xl border px-4 py-3 text-sm font-medium"
                :class="{
                    'border-emerald-200 bg-emerald-50 text-emerald-700': state === 'success',
                    'border-rose-200 bg-rose-50 text-rose-700': state === 'error',
                }"
            >
                {{ message }}
            </div>

            <div class="mt-6 text-center">
                <Link href="/" class="text-sm font-medium text-slate-500 hover:text-slate-700">Kembali</Link>
            </div>
        </div>
    </div>
</template>

