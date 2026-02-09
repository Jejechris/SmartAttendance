<script setup>
import { computed, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppFlash from '@/Components/AppFlash.vue';
import StatusBadge from '@/Components/Attendance/StatusBadge.vue';
import BigActionButton from '@/Components/Attendance/BigActionButton.vue';

const props = defineProps({
    sessions: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    subjects: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const form = useForm({
    class_id: '',
    subject_id: '',
    started_at: '',
    ended_at: '',
    late_tolerance_minutes: 10,
    qr_rotate_seconds: 30,
    qr_dynamic: true,
    location_validation: false,
    center_lat: '',
    center_lng: '',
    radius_meters: 80,
});

watch(
    () => form.location_validation,
    (enabled) => {
        if (!enabled) {
            form.center_lat = '';
            form.center_lng = '';
        }
    }
);

function submit() {
    form.post('/teacher/attendance/sessions', {
        preserveScroll: true,
    });
}

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');
</script>

<template>
    <Head title="Sesi Absensi" />

    <DashboardLayout>
        <div class="space-y-6">
            <div class="space-y-3">
                <h1 class="text-2xl font-semibold text-slate-900 md:text-3xl">Manajemen Absensi QR</h1>
                <p class="max-w-3xl text-sm text-slate-600 md:text-base">
                    Buat sesi absensi, tampilkan QR dinamis, lalu pantau scan siswa secara realtime.
                </p>
            </div>

            <AppFlash :message="flashSuccess" type="success" />
            <AppFlash :message="flashError" type="error" />

            <div class="grid gap-6 lg:grid-cols-[420px,1fr]">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Buat Sesi Baru</h2>
                    <p class="mt-1 text-sm text-slate-500">Isi data pertemuan lalu simpan.</p>

                    <form class="mt-5 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Kelas</label>
                            <select v-model="form.class_id" class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100">
                                <option value="">Pilih kelas</option>
                                <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.class_id" class="mt-1 text-xs text-rose-600">{{ form.errors.class_id }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Mata Pelajaran</label>
                            <select v-model="form.subject_id" class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100">
                                <option value="">Pilih mapel</option>
                                <option v-for="item in subjects" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.subject_id" class="mt-1 text-xs text-rose-600">{{ form.errors.subject_id }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Mulai</label>
                                <input v-model="form.started_at" type="datetime-local" class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100" />
                                <p v-if="form.errors.started_at" class="mt-1 text-xs text-rose-600">{{ form.errors.started_at }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Berakhir</label>
                                <input v-model="form.ended_at" type="datetime-local" class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100" />
                                <p v-if="form.errors.ended_at" class="mt-1 text-xs text-rose-600">{{ form.errors.ended_at }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Toleransi terlambat</label>
                                <input v-model.number="form.late_tolerance_minutes" type="number" min="0" max="240" class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Rotasi QR (detik)</label>
                                <input v-model.number="form.qr_rotate_seconds" type="number" min="15" max="120" class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100" />
                            </div>
                        </div>

                        <div class="space-y-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <input v-model="form.qr_dynamic" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                                Gunakan QR dinamis
                            </label>
                            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <input v-model="form.location_validation" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                                Aktifkan validasi lokasi
                            </label>

                            <div v-if="form.location_validation" class="grid gap-3 sm:grid-cols-2">
                                <input v-model="form.center_lat" type="number" step="0.0000001" placeholder="Latitude" class="h-11 rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100" />
                                <input v-model="form.center_lng" type="number" step="0.0000001" placeholder="Longitude" class="h-11 rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100" />
                                <input v-model.number="form.radius_meters" type="number" min="10" max="500" placeholder="Radius (meter)" class="h-11 rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100 sm:col-span-2" />
                            </div>
                        </div>

                        <BigActionButton :loading="form.processing" class="w-full">Simpan Sesi</BigActionButton>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">Daftar Sesi Terbaru</h2>
                        <p class="text-xs text-slate-500">Maks 100 sesi</p>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">ID</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Kelas</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Mapel</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Mulai</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-if="sessions.length === 0">
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada sesi absensi.</td>
                                    </tr>
                                    <tr v-for="item in sessions" :key="item.id">
                                        <td class="px-4 py-3 font-medium text-slate-800">#{{ item.id }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ item.school_class?.name }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ item.subject?.name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ item.started_at_label }}</td>
                                        <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                                        <td class="px-4 py-3">
                                            <Link :href="`/teacher/attendance/sessions/${item.id}`" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                                                Kelola
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </DashboardLayout>
</template>
