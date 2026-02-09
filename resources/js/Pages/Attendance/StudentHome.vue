<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
</script>

<template>
    <Head title="Beranda Siswa" />

    <DashboardLayout>
        <div class="mx-auto w-full max-w-4xl space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">SmartAttendance</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">Beranda Siswa</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Saat guru menampilkan QR absensi, scan lalu kirim kehadiran dari HP Anda.
                </p>
                <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Jika belum ada QR, tunggu sesi dibuka oleh guru.
                </p>
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <Link
                    href="/student/id"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-teal-200 hover:bg-teal-50"
                >
                    <p class="text-lg font-semibold text-slate-900">Student ID Digital</p>
                    <p class="mt-2 text-sm text-slate-600">Buka QR identitas siswa untuk verifikasi guru/admin.</p>
                </Link>

                <Link
                    href="/student/leave-requests"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-teal-200 hover:bg-teal-50"
                >
                    <p class="text-lg font-semibold text-slate-900">Pengajuan Izin</p>
                    <p class="mt-2 text-sm text-slate-600">Kirim izin/sakit/dispensasi dan pantau status approval.</p>
                </Link>
            </section>
        </div>
    </DashboardLayout>
</template>
