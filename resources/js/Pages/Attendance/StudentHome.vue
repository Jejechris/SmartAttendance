<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Beranda Siswa" />

    <div class="min-h-screen bg-slate-50 px-4 py-8">
        <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">SmartAttendance</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Beranda Siswa</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Untuk absensi, scan QR dari layar guru lalu kirim kehadiran dari halaman scan.
            </p>
            <p class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Jika belum ada QR, tunggu guru membuka sesi absensi.
            </p>
        </div>
    </div>
</template>
