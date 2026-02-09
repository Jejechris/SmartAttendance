<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineProps({
    valid: {
        type: Boolean,
        required: true,
    },
    reason: {
        type: String,
        default: '',
    },
    student: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Verifikasi Student ID" />

    <DashboardLayout>
        <div class="mx-auto w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Student ID Verification</p>

            <div
                class="mt-4 rounded-xl border px-4 py-3 text-sm font-medium"
                :class="valid ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
            >
                {{ valid ? 'Valid: data siswa ditemukan.' : reason || 'Token tidak valid.' }}
            </div>

            <div v-if="valid && student" class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <p><span class="font-semibold text-slate-700">Nama:</span> <span class="text-slate-900">{{ student.name }}</span></p>
                <p class="mt-1"><span class="font-semibold text-slate-700">Email:</span> <span class="text-slate-900">{{ student.email }}</span></p>
                <p class="mt-1"><span class="font-semibold text-slate-700">Kelas:</span> <span class="text-slate-900">{{ student.class_name || '-' }}</span></p>
                <p class="mt-1"><span class="font-semibold text-slate-700">ID:</span> <span class="text-slate-900">#{{ student.id }}</span></p>
            </div>

            <div class="mt-6">
                <Link href="/" class="inline-flex h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
