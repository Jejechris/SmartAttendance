<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppFlash from '@/Components/AppFlash.vue';

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    students: {
        type: Array,
        default: () => [],
    },
    leaderboard: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const form = useForm({
    student_id: '',
    points: 5,
    category: '',
    notes: '',
    occurred_on: new Date().toISOString().slice(0, 10),
});

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');

function submit() {
    form.post('/discipline/violations', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('category', 'notes');
            form.points = 5;
            form.student_id = '';
        },
    });
}
</script>

<template>
    <Head title="Poin Pelanggaran" />

    <DashboardLayout>
        <div class="grid gap-6 xl:grid-cols-[420px,1fr]">
            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Poin Pelanggaran</h1>
                    <p class="mt-1 text-sm text-slate-600">Catat kejadian disiplin siswa dengan cepat.</p>
                </div>

                <AppFlash :message="flashSuccess" type="success" />
                <AppFlash :message="flashError" type="error" />

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Siswa</label>
                        <select
                            v-model="form.student_id"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        >
                            <option value="">Pilih siswa</option>
                            <option v-for="item in students" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                        <p v-if="form.errors.student_id" class="mt-1 text-xs text-rose-600">{{ form.errors.student_id }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Poin</label>
                            <input
                                v-model.number="form.points"
                                type="number"
                                min="1"
                                max="100"
                                class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal Kejadian</label>
                            <input
                                v-model="form.occurred_on"
                                type="date"
                                class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                        <input
                            v-model="form.category"
                            type="text"
                            maxlength="80"
                            placeholder="Contoh: Terlambat upacara"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                        <p v-if="form.errors.category" class="mt-1 text-xs text-rose-600">{{ form.errors.category }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            maxlength="500"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-teal-600 px-5 text-base font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pelanggaran' }}
                    </button>
                </form>
            </section>

            <section class="space-y-6">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Top Poin Tertinggi</h2>
                    <p class="mt-1 text-sm text-slate-600">5 siswa dengan akumulasi poin tertinggi.</p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(item, idx) in leaderboard"
                            :key="`${item.student_name}-${idx}`"
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                        >
                            <p class="font-medium text-slate-800">{{ item.student_name }}</p>
                            <p class="text-sm font-bold text-rose-700">{{ item.total_points }} poin</p>
                        </div>
                        <p v-if="leaderboard.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                            Belum ada data poin.
                        </p>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Riwayat Pelanggaran</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tanggal</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Siswa</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Kategori</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Poin</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Dicatat Oleh</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="rows.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada pelanggaran tercatat.</td>
                                </tr>
                                <tr v-for="item in rows" :key="item.id">
                                    <td class="px-4 py-3 text-slate-700">{{ item.occurred_on }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ item.student_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ item.category }}</td>
                                    <td class="px-4 py-3 font-semibold text-rose-700">{{ item.points }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ item.creator_name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        </div>
    </DashboardLayout>
</template>
