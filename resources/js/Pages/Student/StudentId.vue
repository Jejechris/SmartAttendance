<script setup>
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    student: {
        type: Object,
        required: true,
    },
    verify_url: {
        type: String,
        required: true,
    },
    expires_at: {
        type: String,
        required: true,
    },
});

const qrImage = ref('');

onMounted(async () => {
    qrImage.value = await QRCode.toDataURL(props.verify_url, {
        width: 320,
        margin: 1,
        errorCorrectionLevel: 'M',
    });
});
</script>

<template>
    <Head title="Student ID Digital" />

    <DashboardLayout>
        <div class="mx-auto grid w-full max-w-4xl gap-6 md:grid-cols-[1fr,340px]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Student ID</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">Kartu Siswa Digital</h1>
                <p class="mt-2 text-sm text-slate-600">Tunjukkan QR ini saat diminta guru/admin untuk verifikasi identitas.</p>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="grid gap-3 text-sm">
                        <p><span class="font-semibold text-slate-700">Nama:</span> <span class="text-slate-900">{{ student.name }}</span></p>
                        <p><span class="font-semibold text-slate-700">Email:</span> <span class="text-slate-900">{{ student.email }}</span></p>
                        <p><span class="font-semibold text-slate-700">Kelas Aktif:</span> <span class="text-slate-900">{{ student.class_name || '-' }}</span></p>
                        <p><span class="font-semibold text-slate-700">Berlaku sampai:</span> <span class="text-slate-900">{{ expires_at }}</span></p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">QR Verifikasi</p>
                <div class="mt-4 grid min-h-[300px] place-items-center rounded-xl border border-slate-200 bg-slate-50">
                    <img v-if="qrImage" :src="qrImage" alt="QR Student ID" class="h-72 w-72" />
                    <div v-else class="h-52 w-52 animate-pulse rounded bg-slate-200" />
                </div>
                <p class="mt-3 break-all text-xs text-slate-500">{{ verify_url }}</p>
            </section>
        </div>
    </DashboardLayout>
</template>
