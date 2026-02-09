<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppFlash from '@/Components/AppFlash.vue';

const props = defineProps({
    school: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const form = useForm({
    display_name: props.school.display_name || '',
    logo_url: props.school.logo_url || '',
    timezone: props.school.timezone || 'Asia/Jakarta',
});

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');

const timezones = ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'];

function submit() {
    form.post('/admin/settings/branding', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Branding Sekolah" />

    <DashboardLayout>
        <div class="grid gap-6 lg:grid-cols-[1.2fr,1fr]">
            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Branding Sekolah</h1>
                    <p class="mt-1 text-sm text-slate-600">Atur nama tampil, logo, dan timezone sekolah.</p>
                </div>

                <AppFlash :message="flashSuccess" type="success" />
                <AppFlash :message="flashError" type="error" />

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama Sistem (Default)</label>
                        <input
                            :value="school.name"
                            type="text"
                            disabled
                            class="h-12 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 text-sm text-slate-600"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama Tampil</label>
                        <input
                            v-model="form.display_name"
                            type="text"
                            maxlength="120"
                            placeholder="Contoh: SMK Negeri 1 Cerdas"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                        <p v-if="form.errors.display_name" class="mt-1 text-xs text-rose-600">{{ form.errors.display_name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Logo URL</label>
                        <input
                            v-model="form.logo_url"
                            type="url"
                            maxlength="500"
                            placeholder="https://..."
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        />
                        <p v-if="form.errors.logo_url" class="mt-1 text-xs text-rose-600">{{ form.errors.logo_url }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Timezone</label>
                        <select
                            v-model="form.timezone"
                            class="h-12 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100"
                        >
                            <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                        </select>
                        <p v-if="form.errors.timezone" class="mt-1 text-xs text-rose-600">{{ form.errors.timezone }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-teal-600 px-5 text-base font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Branding' }}
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                <h2 class="text-lg font-semibold text-slate-900">Preview Header</h2>
                <p class="mt-1 text-sm text-slate-600">Simulasi tampilan dashboard pengguna.</p>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="form.logo_url"
                            :src="form.logo_url"
                            alt="Preview logo"
                            class="h-12 w-12 rounded-xl border border-slate-200 object-cover"
                        />
                        <div v-else class="grid h-12 w-12 place-items-center rounded-xl bg-teal-600 text-sm font-bold text-white">
                            SA
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">{{ form.display_name || school.name }}</p>
                            <p class="text-sm text-slate-500">{{ form.timezone }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
